import { lstat, readdir } from 'node:fs/promises';
import { join } from 'node:path';

import { assertRunDirectorySafe, resolveDataRoot } from '../cli.mjs';
import { RunStore } from '../lib/run-store.mjs';
import { assertNumericExternalId, assertSafeIdentifier, readSafeFile } from '../lib/safe-filesystem.mjs';

const runIdPattern = /^[a-z0-9][a-z0-9-]{0,127}$/;
const numericJsonPattern = /^(\d+)\.json$/;
const numericWebpPattern = /^(\d+)\.webp$/;
const browserSeedFreshnessMs = 120_000;

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

async function isRegularFile(path) {
    try {
        const stats = await lstat(path);
        return stats.isFile() && !stats.isSymbolicLink();
    } catch (error) {
        if (error?.code === 'ENOENT') return false;
        throw error;
    }
}

async function listNumericFileIds(directory, pattern) {
    try {
        const stats = await lstat(directory);
        if (!stats.isDirectory() || stats.isSymbolicLink()) return new Set();
        return new Set((await readdir(directory, { withFileTypes: true }))
            .filter((entry) => entry.isFile() && pattern.test(entry.name))
            .map((entry) => entry.name.match(pattern)[1]));
    } catch (error) {
        if (error?.code === 'ENOENT') return new Set();
        throw error;
    }
}

async function readOptionalJson(runDir, path) {
    try {
        return JSON.parse(await readSafeFile(runDir, path, 'utf8'));
    } catch (error) {
        if (error?.code === 'ENOENT' || error instanceof SyntaxError) return null;
        throw error;
    }
}

function browserProgressSnapshot(progress, completeIds, nowMs) {
    if (!progress || progress.schemaVersion !== 1) return null;
    const updatedAt = Date.parse(progress.updatedAt);
    const nextActionAt = Date.parse(progress.nextActionAt);
    const active = progress.status === 'running'
        && Number.isFinite(updatedAt)
        && updatedAt >= nowMs - browserSeedFreshnessMs
        && updatedAt <= nowMs + 60_000;
    const currentProduct = progress.currentProduct
        && typeof progress.currentProduct.externalId === 'string'
        && typeof progress.currentProduct.url === 'string'
        ? {
            externalId: progress.currentProduct.externalId,
            url: progress.currentProduct.url,
        }
        : null;

    return {
        active,
        status: typeof progress.status === 'string' ? progress.status : 'unknown',
        stage: typeof progress.stage === 'string' ? progress.stage : null,
        targetProducts: Number.isInteger(progress.targetProducts) ? progress.targetProducts : null,
        completedProducts: completeIds.size,
        requestsLastHour: Number.isInteger(progress.requestsLastHour)
            ? progress.requestsLastHour
            : null,
        updatedAt: Number.isFinite(updatedAt) ? updatedAt : null,
        nextActionAt: Number.isFinite(nextActionAt) ? nextActionAt : null,
        currentProduct,
    };
}

async function readBrowserSeed(store, nowMs) {
    const root = join(store.runDir, 'browser-seed');
    const productsDir = join(root, 'products');
    const imagesDir = join(root, 'images');
    try {
        const rootStats = await lstat(root);
        if (!rootStats.isDirectory() || rootStats.isSymbolicLink()) {
            throw new Error('Browser seed path is not a safe regular directory');
        }
    } catch (error) {
        if (error?.code !== 'ENOENT') throw error;
        return {
            root,
            productsDir,
            imagesDir,
            productIds: new Set(),
            imageIds: new Set(),
            completeIds: new Set(),
            progress: null,
        };
    }
    const [productIds, imageIds, progress] = await Promise.all([
        listNumericFileIds(productsDir, numericJsonPattern),
        listNumericFileIds(imagesDir, numericWebpPattern),
        readOptionalJson(store.runDir, join(root, 'progress.json')),
    ]);
    const completeIds = new Set([...productIds].filter((externalId) => imageIds.has(externalId)));

    return {
        root,
        productsDir,
        imagesDir,
        productIds,
        imageIds,
        completeIds,
        progress: browserProgressSnapshot(progress, completeIds, nowMs),
    };
}

async function readBrowserSeedProduct(store, seed, externalId) {
    if (!seed.completeIds.has(externalId)) return null;
    return readOptionalJson(store.runDir, join(seed.productsDir, `${externalId}.json`));
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
        const nowMs = now();
        const [memberships, mainImageIds, events, exportStats, browserSeed] = await Promise.all([
            store.readMemberships(),
            listNumericFileIds(store.imagesDir, numericWebpPattern),
            readNdjsonTail(store.runDir, store.eventsPath, 100),
            lstat(store.exportPath).catch((error) => error?.code === 'ENOENT' ? null : Promise.reject(error)),
            readBrowserSeed(store, nowMs),
        ]);
        const completedCategories = state.sources.filter((source) => source.completed).length;
        const currentSource = state.sources.find((source) => !source.completed) || null;
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
        const browserActive = browserSeed.progress?.active === true;
        const browserNeedsConfirmation = browserSeed.progress?.status === 'needs_confirmation'
            && browserSeed.progress.stage === 'verification_required';
        const uniqueProductIds = new Set([
            ...(state.completedProductIds || []),
            ...browserSeed.completeIds,
        ]);
        const imageIds = new Set([...mainImageIds, ...browserSeed.imageIds]);
        return {
            id: runId,
            status: browserActive ? 'running' : browserNeedsConfirmation ? 'paused' : state.status,
            pauseReason: browserActive
                ? null
                : browserNeedsConfirmation ? 'challenge' : state.pauseReason || null,
            exportReady: Boolean(exportStats?.isFile()),
            limits: config.limits,
            currentSource: currentSource ? sourceSnapshot(currentSource) : null,
            sources: state.sources.map(sourceSnapshot),
            metrics: {
                categories: state.sources.length,
                completedCategories,
                pages: state.sources.reduce((sum, source) => sum + (source.pages || 0), 0),
                uniqueProducts: uniqueProductIds.size,
                images: imageIds.size,
                memberships: memberships.length,
                requests: state.requestCount,
                requestsLastHour: browserActive
                    ? browserSeed.progress.requestsLastHour ?? recentRequestTimes.length
                    : recentRequestTimes.length,
                hourlyLimit,
            },
            nextRequestAt: browserActive
                ? browserSeed.progress.nextActionAt || nextRequestAt
                : nextRequestAt,
            lastUrl: browserActive || browserNeedsConfirmation
                ? browserSeed.progress.currentProduct?.url || null
                : [...events].reverse().find((event) => typeof event.url === 'string')?.url || null,
            browserSeed: browserSeed.progress,
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
        const [mainProductIds, memberships, browserSeed] = await Promise.all([
            listNumericFileIds(store.productsDir, numericJsonPattern),
            store.readMemberships(),
            readBrowserSeed(store, now()),
        ]);
        const ids = [...new Set([...mainProductIds, ...browserSeed.completeIds])]
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
            const product = await store.readProduct(externalId)
                || await readBrowserSeedProduct(store, browserSeed, externalId);
            const categories = new Set(categoriesByProduct.get(externalId) || []);
            for (const sourceSlug of product?.sourceSlugs || []) categories.add(sourceSlug);
            items.push({
                externalId,
                name: product?.sourceTitle || product?.name || product?.title || `Товар ${externalId}`,
                sourcePrice: product?.sourcePrice || product?.source_price || product?.price || null,
                sourceUrl: product?.sourceUrl || product?.source_url || product?.url || null,
                imageUrl: `/api/runs/${encodeURIComponent(runId)}/images/${externalId}`,
                categories: [...categories],
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
        const candidates = [
            join(store.imagesDir, `${externalId}.webp`),
            join(store.runDir, 'browser-seed', 'images', `${externalId}.webp`),
        ];
        let missingError = null;
        for (const path of candidates) {
            try {
                const stats = await lstat(path);
                if (!stats.isFile() || stats.isSymbolicLink()) {
                    throw new Error('Image is not a safe regular file');
                }
                return path;
            } catch (error) {
                if (error?.code === 'ENOENT') {
                    missingError = error;
                    continue;
                }
                throw error;
            }
        }
        throw missingError || new Error(`Image not found for product ${externalId}`);
    }

    return {
        dataRoot: resolvedRoot,
        listRuns,
        getRunSnapshot,
        listProducts,
        getImagePath,
    };
}
