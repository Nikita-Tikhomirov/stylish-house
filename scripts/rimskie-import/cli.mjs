#!/usr/bin/env node

import { randomUUID } from 'node:crypto';
import { constants } from 'node:fs';
import {
    access as fsAccess,
    link as fsLink,
    mkdir as fsMkdir,
    readFile,
    realpath as fsRealpath,
    rename,
    unlink,
    writeFile,
} from 'node:fs/promises';
import { basename, join, posix, win32 } from 'node:path';
import { fileURLToPath } from 'node:url';
import { setTimeout as sleep } from 'node:timers/promises';

import { RunStore } from './lib/run-store.mjs';

const commands = new Set(['start', 'status', 'pause', 'resume', 'stop', 'export', 'dry-run']);
const valueOptions = new Set(['--run', '--data-root', '--chrome', '--max-requests', '--max-products']);

function positiveInteger(value, option) {
    const parsed = Number(value);
    if (!Number.isInteger(parsed) || parsed <= 0) throw new Error(`${option} must be a positive integer`);

    return parsed;
}

export function parseArguments(argv, environment = process.env) {
    const [command, ...tokens] = argv;
    if (!commands.has(command)) {
        throw new Error('Command must be start, status, pause, resume, stop, export, or dry-run');
    }

    const values = new Map();
    let json = false;
    for (let index = 0; index < tokens.length; index += 1) {
        const token = tokens[index];
        if (token === '--json') {
            json = true;
            continue;
        }
        if (!valueOptions.has(token)) throw new Error(`Unknown option: ${token}`);
        const value = tokens[index + 1];
        if (!value || value.startsWith('--')) throw new Error(`${token} requires a value`);
        values.set(token, value);
        index += 1;
    }

    const runId = values.get('--run');
    const dataRoot = values.get('--data-root') || environment.RIMSKIE_IMPORT_DATA_ROOT;
    if (!runId) throw new Error('--run is required');
    if (!dataRoot) throw new Error('--data-root or RIMSKIE_IMPORT_DATA_ROOT is required');

    return {
        command,
        runId,
        dataRoot,
        chrome: values.get('--chrome'),
        json,
        maxRequests: values.has('--max-requests')
            ? positiveInteger(values.get('--max-requests'), '--max-requests')
            : command === 'dry-run' ? 3 : Number.POSITIVE_INFINITY,
        maxProducts: values.has('--max-products')
            ? positiveInteger(values.get('--max-products'), '--max-products')
            : command === 'dry-run' ? 1 : Number.POSITIVE_INFINITY,
    };
}

function pathApi(platform) {
    return platform === 'win32' ? win32 : posix;
}

function assertAllowedRoot(value, platform) {
    const paths = pathApi(platform);
    if (!paths.isAbsolute(value)) throw new Error('Data root must be an absolute path');
    if (platform !== 'win32') return;

    const normalized = value.replaceAll('/', '\\');
    const namespacePatterns = [/^\\\\[?.]\\/, /^\\\\\?\?\\/, /^\\\?\?\\/];
    let ordinaryPath = normalized;
    let namespaced = false;
    for (const pattern of namespacePatterns) {
        if (!pattern.test(ordinaryPath)) continue;
        ordinaryPath = ordinaryPath.replace(pattern, '');
        namespaced = true;
        break;
    }
    const drive = ordinaryPath.match(/^([a-z]):\\/i)?.[1]?.toUpperCase();
    if (drive === 'C') throw new Error('Data root must not use Windows drive C:');
    if (namespaced || !drive) {
        throw new Error('Data root must use a regular Windows drive-letter path');
    }
}

async function resolveThroughExistingAncestor(value, platform, realpath) {
    const paths = pathApi(platform);
    let candidate = value;

    while (true) {
        try {
            const resolvedAncestor = await realpath(candidate);
            assertAllowedRoot(resolvedAncestor, platform);
            const resolvedTarget = paths.resolve(resolvedAncestor, paths.relative(candidate, value));
            assertAllowedRoot(resolvedTarget, platform);
            return resolvedTarget;
        } catch (error) {
            if (error?.code !== 'ENOENT') throw error;
            const parent = paths.dirname(candidate);
            if (parent === candidate) throw error;
            candidate = parent;
        }
    }
}

export async function resolveDataRoot(value, {
    platform = process.platform,
    mkdir = fsMkdir,
    access = fsAccess,
    realpath = fsRealpath,
} = {}) {
    assertAllowedRoot(value, platform);
    const safeTarget = await resolveThroughExistingAncestor(value, platform, realpath);
    await mkdir(safeTarget, { recursive: true });
    await access(safeTarget, constants.W_OK);
    const resolved = await realpath(safeTarget);
    assertAllowedRoot(resolved, platform);

    return resolved;
}

export async function assertRunDirectorySafe(dataRoot, runId, {
    platform = process.platform,
    realpath = fsRealpath,
} = {}) {
    if (typeof runId !== 'string' || !runId || runId === '.' || runId === '..'
        || runId.includes('/') || runId.includes('\\') || runId.includes('..')) {
        throw new Error('Run ID path traversal is not allowed');
    }

    const paths = pathApi(platform);
    const requestedRunDir = paths.join(dataRoot, runId);
    const resolvedRunDir = await resolveThroughExistingAncestor(requestedRunDir, platform, realpath);
    assertAllowedRoot(resolvedRunDir, platform);

    const comparableRequested = paths.resolve(requestedRunDir);
    const comparableResolved = paths.resolve(resolvedRunDir);
    const sameLocation = platform === 'win32'
        ? comparableRequested.toLowerCase() === comparableResolved.toLowerCase()
        : comparableRequested === comparableResolved;
    if (!sameLocation) {
        throw new Error('Run directory must stay inside the validated data root without junctions');
    }

    const childNames = [
        'sources',
        'products',
        'images',
        'profile',
        'state.json',
        'control.json',
        'control.json.lock',
        'memberships.ndjson',
        'events.ndjson',
        'export.json',
    ];
    for (const childName of childNames) {
        const childPath = paths.join(requestedRunDir, childName);
        let resolvedChild;
        try {
            resolvedChild = await realpath(childPath);
        } catch (error) {
            if (error?.code === 'ENOENT') continue;
            throw error;
        }
        assertAllowedRoot(resolvedChild, platform);
        const requestedChild = paths.resolve(childPath);
        const actualChild = paths.resolve(resolvedChild);
        const sameChild = platform === 'win32'
            ? requestedChild.toLowerCase() === actualChild.toLowerCase()
            : requestedChild === actualChild;
        if (!sameChild) {
            throw new Error(`Run child ${childName} must not use a junction or symbolic link`);
        }
    }

    return requestedRunDir;
}

export class ControlFile {
    constructor(path, { isLiveProcess: liveProcessCheck = isLiveProcess } = {}) {
        this.path = path;
        this.lockPath = `${path}.lock`;
        this.isLiveProcess = liveProcessCheck;
    }

    async read() {
        try {
            return JSON.parse(await readFile(this.path, 'utf8'));
        } catch (error) {
            if (error?.code === 'ENOENT') return { pause: false, stop: false };
            throw error;
        }
    }

    async write(value) {
        await this.#withLock(async () => {
            const current = await this.read();
            await this.#writeUnlocked(this.#merge(current, value));
        });
    }

    async update(changes) {
        await this.write(changes);
    }

    async claim(ownerPid, { resume = false } = {}) {
        await this.#withLock(async () => {
            const current = await this.read();
            if (current.stop) throw new Error('A stopped run cannot start or resume');
            if (current.ownerPid && current.ownerPid !== ownerPid && this.isLiveProcess(current.ownerPid)) {
                throw new Error(`Collector is already running with PID ${current.ownerPid}`);
            }
            if (current.pause && !resume) throw new Error('Run is paused; use the resume command');
            await this.#writeUnlocked({
                ...current,
                pause: resume ? false : Boolean(current.pause),
                stop: false,
                ownerPid,
            });
        });
    }

    async release(ownerPid) {
        await this.#withLock(async () => {
            const current = await this.read();
            if (current.ownerPid !== ownerPid) return;
            await this.#writeUnlocked({ ...current, ownerPid: null });
        });
    }

    #merge(current, changes) {
        const stop = Boolean(current.stop || changes.stop);
        return {
            ...current,
            ...changes,
            pause: stop ? false : Boolean(changes.pause ?? current.pause),
            stop,
        };
    }

    async #writeUnlocked(value) {
        const temporaryPath = `${this.path}.${process.pid}.tmp`;
        await writeFile(temporaryPath, `${JSON.stringify(value, null, 2)}\n`, 'utf8');
        await rename(temporaryPath, this.path);
    }

    async #withLock(operation) {
        for (let attempt = 0; attempt < 200; attempt += 1) {
            const token = await this.#tryClaimLock();
            if (token) {
                try {
                    return await operation();
                } finally {
                    await this.#releaseLock(token);
                }
            }

            const owner = await this.#readLockOwner();
            if (!owner) continue;
            if (!this.isLiveProcess(owner.pid)) {
                await this.#quarantineStaleLock(owner);
                continue;
            }
            await sleep(5);
        }

        throw new Error('Timed out waiting for control file lock');
    }

    async #tryClaimLock() {
        const token = randomUUID();
        const claimPath = `${this.lockPath}.claim.${process.pid}.${token}`;
        await writeFile(claimPath, `${JSON.stringify({
            pid: process.pid,
            token,
        })}\n`, { encoding: 'utf8', flag: 'wx' });
        try {
            try {
                await fsLink(claimPath, this.lockPath);
                return token;
            } catch (error) {
                if (error?.code !== 'EEXIST') throw error;
                return null;
            }
        } finally {
            await unlink(claimPath).catch((error) => {
                if (error?.code !== 'ENOENT') throw error;
            });
        }
    }

    async #readLockOwner() {
        try {
            const owner = JSON.parse(await readFile(this.lockPath, 'utf8'));
            if (!Number.isInteger(owner?.pid) || owner.pid <= 0
                || typeof owner?.token !== 'string' || !/^[a-z0-9-]+$/i.test(owner.token)) {
                throw new Error('invalid owner metadata');
            }
            return owner;
        } catch (error) {
            if (error?.code === 'ENOENT') return null;
            throw new Error(
                `Incomplete control lock at ${this.lockPath}; verify no collector is running and remove manually`,
                { cause: error },
            );
        }
    }

    async #quarantineStaleLock(owner) {
        const stalePath = `${this.lockPath}.stale.${owner.token}`;
        try {
            await fsLink(this.lockPath, stalePath);
        } catch (error) {
            if (['ENOENT', 'EEXIST'].includes(error?.code)) return false;
            throw error;
        }
        await unlink(this.lockPath);

        return true;
    }

    async #releaseLock(token) {
        const owner = await this.#readLockOwner();
        if (!owner || owner.pid !== process.pid || owner.token !== token) {
            throw new Error('Control lock ownership changed before release');
        }
        await unlink(this.lockPath);
    }
}

async function loadSources() {
    const sources = JSON.parse(await readFile(
        new URL('../../config/rimskie-import-sources.json', import.meta.url),
        'utf8',
    ));

    return sources
        .filter(({ enabled }) => enabled)
        .sort((left, right) => left.sort_order - right.sort_order)
        .map((source) => ({
            label: source.label,
            sourceSlug: source.target_slug,
            sourceUrl: source.source_url,
            nextPageUrl: source.source_url,
            enabled: source.enabled,
            sortOrder: source.sort_order,
            pendingProducts: [],
            completed: false,
            pages: 0,
        }));
}

async function initializeState(store) {
    const existing = await store.readState();
    if (existing) return existing;

    const state = {
        status: 'ready',
        requestCount: 0,
        completedProductIds: [],
        sources: await loadSources(),
    };
    await store.checkpoint(state);

    return state;
}

function isLiveProcess(processId) {
    if (!Number.isInteger(processId) || processId <= 0) return false;
    try {
        process.kill(processId, 0);
        return true;
    } catch {
        return false;
    }
}

async function waitForAuthorizedChallenge(control) {
    process.stderr.write(
        'Challenge paused. Complete only an explicitly authorized click in the visible browser, '
        + 'then run the resume command.\n',
    );
    while (true) {
        const flags = await control.read();
        if (flags.stop || !flags.pause) return flags;
        await sleep(1_000);
    }
}

function printableSnapshot(state, control) {
    return {
        ...state,
        uniqueProducts: state?.completedProductIds?.length || 0,
        control,
    };
}

async function print(value, json = false) {
    if (json || typeof value !== 'string') {
        process.stdout.write(`${JSON.stringify(value, null, 2)}\n`);
        return;
    }
    process.stdout.write(`${value}\n`);
}

async function runCollector(options, store, control) {
    const [
        { Collector },
        { PlaywrightTransport },
        { RequestPolicy },
    ] = await Promise.all([
        import('./lib/collector.mjs'),
        import('./lib/playwright-transport.mjs'),
        import('./lib/request-policy.mjs'),
    ]);
    const state = await initializeState(store);
    if (state.status === 'stopped') throw new Error('A stopped run cannot be resumed');
    if (state.status === 'completed') return printableSnapshot(state, await control.read());

    const currentControl = await control.read();
    if (options.command === 'resume' && currentControl.stop) {
        throw new Error('A stopped run cannot be resumed');
    }
    if (options.command === 'resume' && currentControl.ownerPid !== process.pid
        && isLiveProcess(currentControl.ownerPid)) {
        await control.update({ pause: false });
        return { status: 'resume-signaled', ownerPid: currentControl.ownerPid };
    }

    await control.claim(process.pid, { resume: options.command === 'resume' });
    let transport;
    let result;

    try {
        transport = await PlaywrightTransport.open({
            profileDir: join(store.runDir, 'profile'),
            headed: true,
            executablePath: options.chrome,
        });
        const policy = new RequestPolicy({ onEvent: (event) => store.appendEvent(event) });
        const collector = new Collector();
        while (true) {
            result = await collector.run({
                store,
                transport,
                policy,
                control,
                maxRequests: options.maxRequests,
                maxProducts: options.maxProducts,
                acknowledgeFailurePause: options.command === 'resume',
            });
            if (result.status !== 'paused' || result.pauseReason !== 'challenge') break;

            await control.update({ pause: true });
            const flags = await waitForAuthorizedChallenge(control);
            if (flags.stop) {
                result = await collector.run({ store, transport, policy, control });
                break;
            }
        }
    } finally {
        try {
            await transport?.close();
        } finally {
            await control.release(process.pid);
        }
    }

    return result;
}

export async function main(argv = process.argv.slice(2)) {
    const options = parseArguments(argv);
    const dataRoot = await resolveDataRoot(options.dataRoot);
    await assertRunDirectorySafe(dataRoot, options.runId);
    const store = await RunStore.open({ rootDir: dataRoot, runId: options.runId });
    const control = new ControlFile(join(store.runDir, 'control.json'));

    if (options.command === 'status') {
        await print(printableSnapshot(await store.readState(), await control.read()), options.json);
        return;
    }
    if (options.command === 'pause') {
        await control.update({ pause: true });
        await print(`Pause requested for ${options.runId}`);
        return;
    }
    if (options.command === 'stop') {
        await control.update({ pause: false, stop: true });
        await print(`Stop requested for ${options.runId}`);
        return;
    }
    if (options.command === 'export') {
        await store.exportManifest();
        await print(store.exportPath);
        return;
    }

    const result = await runCollector(options, store, control);
    await print(result, options.json);
}

const entryPath = process.argv[1] ? fileURLToPath(import.meta.url) : null;
if (entryPath && basename(process.argv[1]) === basename(entryPath)
    && process.argv[1].replaceAll('\\', '/').toLowerCase() === entryPath.replaceAll('\\', '/').toLowerCase()) {
    main().catch((error) => {
        process.stderr.write(`${error.message}\n`);
        process.exitCode = 1;
    });
}
