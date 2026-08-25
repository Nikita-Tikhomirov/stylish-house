#!/usr/bin/env node

import { constants } from 'node:fs';
import {
    access as fsAccess,
    mkdir as fsMkdir,
    readFile,
    realpath as fsRealpath,
    rename,
    writeFile,
} from 'node:fs/promises';
import { basename, join, posix, win32 } from 'node:path';
import { fileURLToPath } from 'node:url';
import { setTimeout as sleep } from 'node:timers/promises';

import { Collector } from './lib/collector.mjs';
import { PlaywrightTransport } from './lib/playwright-transport.mjs';
import { RequestPolicy } from './lib/request-policy.mjs';
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
    const driveComparablePath = platform === 'win32' ? value.replace(/^\\\\\?\\/, '') : value;
    if (platform === 'win32' && paths.parse(driveComparablePath).root.toUpperCase() === 'C:\\') {
        throw new Error('Data root must not use Windows drive C:');
    }
}

export async function resolveDataRoot(value, {
    platform = process.platform,
    mkdir = fsMkdir,
    access = fsAccess,
    realpath = fsRealpath,
} = {}) {
    assertAllowedRoot(value, platform);
    await mkdir(value, { recursive: true });
    await access(value, constants.W_OK);
    const resolved = await realpath(value);
    assertAllowedRoot(resolved, platform);

    return resolved;
}

export class ControlFile {
    constructor(path) {
        this.path = path;
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
        const temporaryPath = `${this.path}.${process.pid}.${Date.now()}.tmp`;
        await writeFile(temporaryPath, `${JSON.stringify(value, null, 2)}\n`, 'utf8');
        await rename(temporaryPath, this.path);
    }

    async update(changes) {
        await this.write({ ...await this.read(), ...changes });
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

    await control.write({ pause: false, stop: false, ownerPid: process.pid });
    const transport = await PlaywrightTransport.open({
        profileDir: join(store.runDir, 'profile'),
        headed: true,
        executablePath: options.chrome,
    });
    const policy = new RequestPolicy({ onEvent: (event) => store.appendEvent(event) });
    const collector = new Collector();
    let result;

    try {
        while (true) {
            result = await collector.run({
                store,
                transport,
                policy,
                control,
                maxRequests: options.maxRequests,
                maxProducts: options.maxProducts,
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
        await transport.close();
        const flags = await control.read();
        await control.write({ ...flags, ownerPid: null });
    }

    return result;
}

export async function main(argv = process.argv.slice(2)) {
    const options = parseArguments(argv);
    const dataRoot = await resolveDataRoot(options.dataRoot);
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
