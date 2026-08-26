import { lstat, readdir } from 'node:fs/promises';
import { join } from 'node:path';

import { assertRunDirectorySafe, resolveDataRoot } from '../cli.mjs';
import { RunStore } from '../lib/run-store.mjs';
import { assertNumericExternalId, assertSafeIdentifier, readSafeFile } from '../lib/safe-filesystem.mjs';

const runIdPattern = /^[a-z0-9][a-z0-9-]{0,127}$/;

function validateRunId(runId) {
    assertSafeIdentifier(runId, 'run ID', { pattern: runIdPattern });
    return runId;
}

function positivePage(value, label, maximum = 100) {
    const number = Number(value);
    if (!Number.isInteger(number) || number < 1 || number > maximum) {
        throw new Error(`${label} must be an integer from 1 to ${maximum}`);
    }
    return number;
}

async function readNdjsonTail(runDir, path, limit) {
    try {
        const text = await readSafeFile(runDir, path, 'utf8');
        return text.split('\n').filter(Boolean).slice(-limit).map((line) => JSON.parse(line));
    } catch (error) {
        if (error?.code === 'ENOENT') return [];
        throw error;
    }
}

async function countFiles(directory, suffix) {
    try {
        return (await readdir(directory, { withFileTypes: true }))
            .filter((entry) => entry.isFile() && entry.name.endsWith(suffix)).length;
    } catch (error) {
        if (error?.code === 'ENOENT') return 0;
        throw error;
    }
}

async function isRegularFile(path) {
    try {
        const stats = await lstat(path);
        return stats.isFile() && !stats.isSymbolicLink();
    } catch (error) {
        if (error?.code === 'ENOENT') return false;
        throw error;
    }
}

function sourceSnapshot(source) {
    return {
        label: source.label,
        slug: source.sourceSlug,
        status: source.completed ? 'completed' : 'pending',
        pages: source.pages || 0,
        pendingProducts: source.pendingProducts?.length || 0,
        currentUrl: source.nextPageUrl || null,
    };
}

export async function createStatusService({ dataRoot, now = Date.now }) {
    const resolvedRoot = await resolveDataRoot(dataRoot, { create: true });
    const recursivelyValidatedRuns = new Set();

    async function openRun(runId) {
        validateRunId(runId);
        if (!recursivelyValidatedRuns.has(runId)) {
            await assertRunDirectorySafe(resolvedRoot, runId);
            recursivelyValidatedRuns.add(runId);
        }
        const store = await RunStore.openExisting({ rootDir: resolvedRoot, runId });
        const initialized = await store.requireInitialized();
        return { store, ...initialized };
    }

    async function getRunSnapshot(runId) {
        const { store, config, state } = await openRun(runId);
        const [memberships, images, events, exportStats] = await Promise.all([
            store.readMemberships(),
            countFiles(store.imagesDir, '.webp'),
            readNdjsonTail(store.runDir, store.eventsPath, 100),
            lstat(store.exportPath).catch((error) => error?.code === 'ENOENT' ? null : Promise.reject(error)),
        ]);
        const completedCategories = state.sources.filter((source) => source.completed).length;
        const currentSource = state.sources.find((source) => !source.completed) || null;
        const nowMs = now();
        const recentRequestTimes = (state.requestPolicy?.requestTimes || [])
            .filter((timestamp) => Number.isFinite(timestamp) && timestamp > nowMs - 3_600_000)
            .sort((left, right) => left - right);
        const hourlyLimit = config.limits.hourly_requests;
        const lastDelay = [...events].reverse().find((event) => event.type === 'delay'
            && Number.isFinite(event.at) && Number.isFinite(event.milliseconds));
        const candidates = [state.requestPolicy?.backoffUntil];
        if (lastDelay) candidates.push(lastDelay.at + lastDelay.milliseconds);
        if (recentRequestTimes.length >= hourlyLimit) candidates.push(recentRequestTimes[0] + 3_600_000);
        const nextRequestAt = candidates.filter((value) => Number.isFinite(value) && value > nowMs)
            .reduce((latest, value) => Math.max(latest, value), 0) || null;
        return {
            id: runId,
            status: state.status,
            pauseReason: state.pauseReason || null,
            exportReady: Boolean(exportStats?.isFile()),
            limits: config.limits,
            currentSource: currentSource ? sourceSnapshot(currentSource) : null,
            sources: state.sources.map(sourceSnapshot),
            metrics: {
                categories: state.sources.length,
                completedCategories,
                pages: state.sources.reduce((sum, source) => sum + (source.pages || 0), 0),
                uniqueProducts: state.completedProductIds.length,
                images,
                memberships: memberships.length,
                requests: state.requestCount,
                requestsLastHour: recentRequestTimes.length,
                hourlyLimit,
            },
            nextRequestAt,
            lastUrl: [...events].reverse().find((event) => typeof event.url === 'string')?.url || null,
            events,
        };
    }

    async function listRuns() {
        const entries = await readdir(resolvedRoot, { withFileTypes: true });
        const candidates = entries
            .filter((entry) => entry.isDirectory() && runIdPattern.test(entry.name))
            .map((entry) => entry.name)
            .sort((left, right) => right.localeCompare(left));
        const runIds = [];
        for (const runId of candidates) {
            const runDir = join(resolvedRoot, runId);
            if (await isRegularFile(join(runDir, 'config.json'))
                || await isRegularFile(join(runDir, 'state.json'))) runIds.push(runId);
        }
        const runs = [];
        for (const runId of runIds) {
            try {
                const snapshot = await getRunSnapshot(runId);
                runs.push({
                    id: snapshot.id,
                    status: snapshot.status,
                    pauseReason: snapshot.pauseReason,
                    exportReady: snapshot.exportReady,
                    metrics: snapshot.metrics,
                });
            } catch (error) {
                runs.push({ id: runId, status: 'invalid', error: error.message });
            }
        }
        return runs;
    }

    async function listProducts(runId, page = 1, perPage = 24) {
        const safePage = positivePage(page, 'page', 1_000_000);
        const safePerPage = positivePage(perPage, 'perPage', 100);
        const { store } = await openRun(runId);
        const [fileNames, memberships] = await Promise.all([
            readdir(store.productsDir),
            store.readMemberships(),
        ]);
        const ids = fileNames
            .filter((name) => /^\d+\.json$/.test(name))
            .map((name) => name.slice(0, -5))
            .sort((left, right) => Number(left) - Number(right));
        const categoriesByProduct = new Map();
        for (const membership of memberships) {
            const categories = categoriesByProduct.get(membership.externalId) || [];
            categories.push(membership.sourceSlug);
            categoriesByProduct.set(membership.externalId, categories);
        }
        const start = (safePage - 1) * safePerPage;
        const items = [];
        for (const externalId of ids.slice(start, start + safePerPage)) {
            const product = await store.readProduct(externalId);
            items.push({
                externalId,
                name: product?.name || product?.title || `Товар ${externalId}`,
                sourcePrice: product?.source_price || product?.price || null,
                sourceUrl: product?.source_url || product?.url || null,
                imageUrl: `/api/runs/${encodeURIComponent(runId)}/images/${externalId}`,
                categories: categoriesByProduct.get(externalId) || [],
            });
        }
        return {
            page: safePage,
            perPage: safePerPage,
            total: ids.length,
            pages: Math.max(1, Math.ceil(ids.length / safePerPage)),
            items,
        };
    }

    async function getImagePath(runId, externalId) {
        assertNumericExternalId(externalId);
        const { store } = await openRun(runId);
        const path = join(store.imagesDir, `${externalId}.webp`);
        const stats = await lstat(path);
        if (!stats.isFile() || stats.isSymbolicLink()) throw new Error('Image is not a safe regular file');
        return path;
    }

    return {
        dataRoot: resolvedRoot,
        listRuns,
        getRunSnapshot,
        listProducts,
        getImagePath,
    };
}
