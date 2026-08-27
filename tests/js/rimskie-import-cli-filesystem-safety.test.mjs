import assert from 'node:assert/strict';
import { execFile } from 'node:child_process';
import {
    link, lstat, mkdir, mkdtemp, readFile, readdir, rename, rm, unlink, writeFile,
} from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';
import { promisify } from 'node:util';
import { fileURLToPath } from 'node:url';

import {
    assertRunDirectorySafe,
    ControlFile,
    initializeRun,
    isLiveProcess,
    lookupProcessFingerprint,
    parseArguments,
    runCollector,
    savedPolicyOptions,
} from '../../scripts/rimskie-import/cli.mjs';
import {
    configDigest,
    configSchemaVersion,
    RunStore,
} from '../../scripts/rimskie-import/lib/run-store.mjs';

const execFileAsync = promisify(execFile);

test('Windows process fingerprint lookup uses hidden execFile with a validated integer PID', async () => {
    const calls = [];
    const fingerprint = await lookupProcessFingerprint(4242, {
        platform: 'win32',
        execFile: async (...args) => {
            calls.push(args);
            return { stdout: '638917281234567890\r\n', stderr: '' };
        },
    });

    assert.equal(fingerprint, 'win32:638917281234567890');
    assert.equal(calls.length, 1);
    assert.equal(calls[0][0], 'powershell.exe');
    assert.match(calls[0][1].join(' '), /Get-Process -Id 4242/);
    assert.deepEqual(calls[0][2], { encoding: 'utf8', windowsHide: true });
    assert.equal(await lookupProcessFingerprint('4242', {
        platform: 'win32',
        execFile: async () => { throw new Error('must not execute'); },
    }), null);
});

test('process liveness treats permission denial as live and fails closed', () => {
    assert.equal(isLiveProcess(4242, () => {
        throw Object.assign(new Error('access denied'), { code: 'EPERM' });
    }), true);
    assert.equal(isLiveProcess(4242, () => {
        throw Object.assign(new Error('missing'), { code: 'ESRCH' });
    }), false);
});

test('CLI rejects Windows reserved names and ADS syntax in run IDs', () => {
    for (const runId of ['CON', 'nul.json', 'run:stream', '..hidden', 'trailing.']) {
        assert.throws(() => parseArguments([
            'status', '--run', runId, '--data-root', 'G:\\stylish-house-data\\rimskie-imports',
        ]), /run ID.*safe/i);
    }
});

test('openExisting rejects a missing run without creating it', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-existing-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const runDir = join(rootDir, 'missing-run');

    await assert.rejects(RunStore.openExisting({ rootDir, runId: 'missing-run' }), /does not exist/i);
    await assert.rejects(readFile(join(runDir, 'state.json')), /ENOENT/);
});

test('concurrent run creators do not fail with EEXIST races', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-open-race-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));

    const stores = await Promise.all(Array.from(
        { length: 12 },
        () => RunStore.open({ rootDir, runId: 'run-001' }),
    ));

    assert.equal(stores.length, 12);
    assert.equal(new Set(stores.map(({ runDir }) => runDir)).size, 1);
});

test('non-start CLI commands fail on a missing run without creating it', async (t) => {
    const dataRoot = 'G:\\stylish-house-data\\rimskie-imports';
    const runId = `missing-cli-${process.pid}-${Date.now()}`;
    const runDir = join(dataRoot, runId);
    t.after(() => rm(runDir, { recursive: true, force: true }));
    const cliPath = fileURLToPath(new URL('../../scripts/rimskie-import/cli.mjs', import.meta.url));

    for (const command of ['status', 'pause', 'stop', 'export', 'resume']) {
        await assert.rejects(execFileAsync(process.execPath, [
            cliPath, command, '--run', runId, '--data-root', dataRoot,
        ]), /does not exist/i);
    }
    await assert.rejects(readFile(join(runDir, 'state.json')), /ENOENT/);
});

test('non-creator CLI commands reject an empty run scaffold', async (t) => {
    const dataRoot = 'G:\\stylish-house-data\\rimskie-imports';
    const runId = `empty-cli-${process.pid}-${Date.now()}`;
    const store = await RunStore.open({ rootDir: dataRoot, runId });
    t.after(() => rm(store.runDir, { recursive: true, force: true }));
    const cliPath = fileURLToPath(new URL('../../scripts/rimskie-import/cli.mjs', import.meta.url));

    for (const command of ['status', 'pause', 'stop', 'export', 'resume']) {
        await assert.rejects(execFileAsync(process.execPath, [
            cliPath, command, '--run', runId, '--data-root', dataRoot,
        ]), /initialized config.*state|config\.json.*state\.json/i);
    }
});

test('non-creator commands reject malformed initialized state', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-state-schema-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });
    await initializeRun(store, {
        maxRequests: 3, maxProducts: 1,
        maxRequestsExplicit: false, maxProductsExplicit: false,
    });
    const malformed = await store.readState();
    delete malformed.requestCount;
    await store.checkpoint(malformed);

    await assert.rejects(store.requireInitialized(), /initialized state|state schema/i);
});

test('run config is create-once and immutable', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-config-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });
    const config = {
        schema_version: configSchemaVersion,
        sources: [{
            label: 'White', sourceSlug: 'white',
            sourceUrl: 'https://rimskie.com/catalog/rimskie-shtory/white',
            nextPageUrl: 'https://rimskie.com/catalog/rimskie-shtory/white',
            enabled: true, sortOrder: 1, pendingProducts: [], completed: false, pages: 0,
        }],
        limits: {
            html_delay_ms: [20_000, 40_000], image_delay_ms: [10_000, 20_000],
            challenge_delay_ms: [10_000, 20_000],
            hourly_requests: 120, backoff_ms: [120_000, 300_000, 900_000],
            concurrency: 1, max_requests: 3, max_products: 1,
        },
    };

    await store.initializeConfig(config);
    assert.deepEqual(await store.readConfig(), config);
    await assert.rejects(
        store.initializeConfig({ ...config, limits: { ...config.limits, hourly_requests: 121 } }),
        /immutable/i,
    );
});

test('concurrent differing config initializers have exactly one winner', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-config-race-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });
    const base = {
        schema_version: configSchemaVersion,
        sources: [{
            label: 'White', sourceSlug: 'white',
            sourceUrl: 'https://rimskie.com/catalog/rimskie-shtory/white',
            nextPageUrl: 'https://rimskie.com/catalog/rimskie-shtory/white',
            enabled: true, sortOrder: 1, pendingProducts: [], completed: false, pages: 0,
        }],
        limits: {
            html_delay_ms: [20_000, 40_000], image_delay_ms: [10_000, 20_000],
            challenge_delay_ms: [10_000, 20_000],
            hourly_requests: 120, backoff_ms: [120_000, 300_000, 900_000],
            concurrency: 1, max_requests: 3, max_products: 1,
        },
    };
    const configs = [base, { ...base, limits: { ...base.limits, hourly_requests: 119 } }];

    const outcomes = await Promise.allSettled(configs.map((config) => store.initializeConfig(config)));
    const storedConfig = await store.readConfig();

    assert.equal(outcomes.filter(({ status }) => status === 'fulfilled').length, 1);
    assert.equal(outcomes.filter(({ status }) => status === 'rejected').length, 1);
    assert.equal(configs.some((config) => JSON.stringify(config) === JSON.stringify(storedConfig)), true);
});

test('run state stores and verifies the immutable config SHA-256 digest', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-config-digest-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });
    const options = {
        maxRequests: 3, maxProducts: 1,
        maxRequestsExplicit: false, maxProductsExplicit: false,
    };

    const initialized = await initializeRun(store, options);
    const durableState = await store.readState();

    assert.match(initialized.configDigest, /^[a-f0-9]{64}$/);
    assert.equal(durableState.configDigest, initialized.configDigest);

    const tampered = await store.readConfig();
    tampered.limits.hourly_requests = 119;
    await writeFile(store.configPath, `${JSON.stringify(tampered, null, 2)}\n`, 'utf8');

    await assert.rejects(initializeRun(store, options), /config.*digest/i);
});

test('resume rejects tampered immutable state source identity and order', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-state-source-identity-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });
    const options = {
        maxRequests: 3, maxProducts: 1,
        maxRequestsExplicit: false, maxProductsExplicit: false,
    };
    const initialized = await initializeRun(store, options);
    const mutations = [
        (state) => { state.sources[0].sourceUrl = 'https://rimskie.com/catalog/rimskie-shtory/black'; },
        (state) => { state.sources[0].sourceSlug = 'tampered'; },
        (state) => { state.sources[0].label = 'Tampered'; },
        (state) => { state.sources[0].enabled = false; },
        (state) => { state.sources[0].sortOrder = 999; },
        (state) => { state.sources.reverse(); },
    ];

    for (const mutate of mutations) {
        const tampered = structuredClone(initialized.state);
        mutate(tampered);
        await store.checkpoint(tampered);

        await assert.rejects(
            initializeRun(store, options),
            /state sources differ from immutable config/i,
        );
    }
});

test('resume allows mutable state source progress to differ from initial config', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-state-source-progress-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });
    const options = {
        maxRequests: 3, maxProducts: 1,
        maxRequestsExplicit: false, maxProductsExplicit: false,
    };
    const initialized = await initializeRun(store, options);
    const progressed = structuredClone(initialized.state);
    progressed.sources[0].nextPageUrl = `${progressed.sources[0].sourceUrl}?page=2`;
    progressed.sources[0].pendingProducts = [{
        externalId: '11889',
        url: 'https://rimskie.com/products/11889-example',
    }];
    progressed.sources[0].completed = false;
    progressed.sources[0].pages = 1;
    await store.checkpoint(progressed);

    const resumed = await initializeRun(store, options);

    assert.deepEqual(resumed.state.sources[0], progressed.sources[0]);
});

test('network resume rejects a run created with the obsolete aggressive request profile', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-obsolete-profile-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });
    const options = {
        maxRequests: 3, maxProducts: 1,
        maxRequestsExplicit: false, maxProductsExplicit: false,
    };
    const initialized = await initializeRun(store, options);
    const obsoleteConfig = structuredClone(initialized.config);
    obsoleteConfig.limits.html_delay_ms = [20_000, 40_000];
    obsoleteConfig.limits.image_delay_ms = [10_000, 20_000];
    obsoleteConfig.limits.hourly_requests = 120;
    await writeFile(store.configPath, `${JSON.stringify(obsoleteConfig, null, 2)}\n`, 'utf8');
    const obsoleteState = structuredClone(initialized.state);
    obsoleteState.configDigest = configDigest(obsoleteConfig);
    await store.checkpoint(obsoleteState);

    await assert.rejects(
        initializeRun(store, options),
        /obsolete aggressive request profile.*new run/i,
    );
});

test('config-only crash window rejects an obsolete aggressive request profile', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-obsolete-config-only-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });
    const options = {
        maxRequests: 3, maxProducts: 1,
        maxRequestsExplicit: false, maxProductsExplicit: false,
    };
    const initialized = await initializeRun(store, options);
    const obsoleteConfig = structuredClone(initialized.config);
    obsoleteConfig.limits.html_delay_ms = [20_000, 40_000];
    obsoleteConfig.limits.image_delay_ms = [10_000, 20_000];
    obsoleteConfig.limits.hourly_requests = 120;
    await rm(store.statePath);
    await writeFile(store.configPath, `${JSON.stringify(obsoleteConfig, null, 2)}\n`, 'utf8');

    await assert.rejects(
        initializeRun(store, options),
        /obsolete aggressive request profile.*new run/i,
    );
    await assert.rejects(readFile(store.statePath), /ENOENT/);
});

test('resumed request policy uses the validated saved limits', () => {
    const config = {
        limits: {
            html_delay_ms: [21_000, 22_000],
            image_delay_ms: [11_000, 12_000],
            challenge_delay_ms: [13_000, 14_000],
            hourly_requests: 17,
            backoff_ms: [1_000, 2_000, 3_000],
        },
    };

    assert.deepEqual(savedPolicyOptions(config), {
        htmlDelayMs: [21_000, 22_000],
        imageDelayMs: [11_000, 12_000],
        challengeDelayMs: [13_000, 14_000],
        hourlyLimit: 17,
        backoffMs: [1_000, 2_000, 3_000],
    });
});

test('challenge terminal path closes the collector browser exactly once', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-challenge-close-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });
    const controlCalls = [];
    const control = {
        read: async () => ({ pause: false, stop: false, ownerPid: null }),
        claim: async () => controlCalls.push('claim'),
        release: async () => controlCalls.push('release'),
    };
    let closeCalls = 0;
    const transport = { close: async () => { closeCalls += 1; } };
    class FakeTransport {
        static async open() { return transport; }
    }
    class FakePolicy {
        constructor() {}
    }
    class FakeCollector {
        async run() { return { status: 'paused', pauseReason: 'challenge' }; }
    }

    const result = await runCollector({
        command: 'start', chrome: 'chrome.exe',
        maxRequests: 3, maxProducts: 1,
        maxRequestsExplicit: false, maxProductsExplicit: false,
    }, store, control, {
        Collector: FakeCollector,
        PlaywrightTransport: FakeTransport,
        RequestPolicy: FakePolicy,
    });

    assert.deepEqual(result, { status: 'paused', pauseReason: 'challenge' });
    assert.equal(closeCalls, 1);
    assert.deepEqual(controlCalls, ['claim', 'release']);
});

test('resume imports a persisted browser seed before opening Playwright', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-browser-seed-resume-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });
    const options = {
        command: 'resume', chrome: 'chrome.exe',
        maxRequests: Number.POSITIVE_INFINITY, maxProducts: Number.POSITIVE_INFINITY,
        maxRequestsExplicit: false, maxProductsExplicit: false,
    };
    await initializeRun(store, { ...options, command: 'start' });
    const paused = await store.readState();
    paused.status = 'paused';
    paused.pauseReason = 'operator';
    await store.checkpoint(paused);
    await mkdir(join(store.runDir, 'browser-seed'));

    const calls = [];
    const control = {
        read: async () => ({ pause: true, stop: false, ownerPid: null }),
        ownerStatus: async () => 'dead',
        claim: async () => calls.push('claim'),
        release: async () => calls.push('release'),
    };
    class FakeTransport {
        static async open() {
            calls.push('transport');
            return { close: async () => calls.push('close') };
        }
    }
    class FakePolicy {
        constructor() {}
    }
    class FakeCollector {
        async run() {
            calls.push('collector');
            return { status: 'paused', pauseReason: 'operator' };
        }
    }

    await runCollector(options, store, control, {
        Collector: FakeCollector,
        PlaywrightTransport: FakeTransport,
        RequestPolicy: FakePolicy,
        importBrowserSeed: async () => {
            calls.push('import');
            return { imported: 1, skipped: 0, completedProducts: 1 };
        },
    });

    assert.deepEqual(calls, ['claim', 'import', 'transport', 'collector', 'close', 'release']);
});

test('existing run config rejects an incomplete request-policy schema', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-config-schema-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });
    await writeFile(store.configPath, `${JSON.stringify({
        schema_version: configSchemaVersion,
        sources: [{ sourceSlug: 'white', sourceUrl: 'https://rimskie.com/catalog/rimskie-shtory/white' }],
        limits: { html_delay_ms: [20_000, 40_000], hourly_requests: 120 },
    }, null, 2)}\n`, 'utf8');

    await assert.rejects(store.readConfig(), /config.*limits|request-policy schema/i);
});

test('legacy config stays readable offline but is rejected for network initialization', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-challenge-delay-schema-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });
    const initialized = await initializeRun(store, {
        maxRequests: 3, maxProducts: 1,
        maxRequestsExplicit: false, maxProductsExplicit: false,
    });
    const missingChallengeDelay = structuredClone(initialized.config);
    delete missingChallengeDelay.limits.challenge_delay_ms;
    await writeFile(
        store.configPath,
        `${JSON.stringify(missingChallengeDelay, null, 2)}\n`,
        'utf8',
    );
    const legacyState = await store.readState();
    legacyState.configDigest = configDigest(missingChallengeDelay);
    await store.checkpoint(legacyState);

    const legacyConfig = await store.readConfig();
    assert.equal(legacyConfig.limits.challenge_delay_ms, undefined);
    await assert.doesNotReject(store.requireInitialized());
    await assert.rejects(
        initializeRun(store, {
            maxRequests: 3, maxProducts: 1,
            maxRequestsExplicit: false, maxProductsExplicit: false,
        }),
        /obsolete aggressive request profile.*new run/i,
    );
});

test('existing run config rejects an incomplete immutable source schema', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-source-schema-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });
    await writeFile(store.configPath, `${JSON.stringify({
        schema_version: configSchemaVersion,
        sources: [{ sourceSlug: 'white', sourceUrl: 'https://rimskie.com/catalog/rimskie-shtory/white' }],
        limits: {
            html_delay_ms: [20_000, 40_000], image_delay_ms: [10_000, 20_000],
            challenge_delay_ms: [10_000, 20_000],
            hourly_requests: 120, backoff_ms: [120_000, 300_000, 900_000],
            concurrency: 1, max_requests: null, max_products: null,
        },
    }, null, 2)}\n`, 'utf8');

    await assert.rejects(store.readConfig(), /source schema/i);
});

test('stale-lock EEXIST crash window is recovered only from the same hard link', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-lock-crash-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const controlPath = join(rootDir, 'control.json');
    const lockPath = `${controlPath}.lock`;
    const stalePath = `${lockPath}.stale.dead-token`;
    await writeFile(lockPath, JSON.stringify({
        pid: 999999, token: 'dead-token', processFingerprint: 'dead-process-start',
    }), 'utf8');
    await link(lockPath, stalePath);
    const control = new ControlFile(controlPath, { isLiveProcess: () => false });

    await control.update({ pause: true });

    assert.deepEqual(await control.read(), { pause: true, stop: false });
    assert.equal(JSON.parse(await readFile(stalePath, 'utf8')).token, 'dead-token');
});

test('abandoned reclaim marker is identity-safely quarantined and recovered', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-reclaim-recovery-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const controlPath = join(rootDir, 'control.json');
    const lockPath = `${controlPath}.lock`;
    const reclaimPath = `${lockPath}.reclaim`;
    await writeFile(lockPath, JSON.stringify({
        pid: 999999, token: 'dead-lock', processFingerprint: 'dead-lock-start',
    }), 'utf8');
    await writeFile(reclaimPath, JSON.stringify({
        pid: 999998, token: 'abandoned-reclaim', identity: 'abandoned-instance',
        processFingerprint: 'abandoned-process-start',
    }), 'utf8');
    const control = new ControlFile(controlPath, {
        isLiveProcess: () => false, processIdentity: 'current-instance',
    });

    await control.update({ pause: true });

    assert.equal((await control.read()).pause, true);
    const recovered = (await readdir(rootDir))
        .filter((name) => name.startsWith('control.json.lock.reclaim.stale.abandoned-reclaim'));
    assert.equal(recovered.length, 1);
});

test('recursive run safety rejects a nested profile junction before writes', async () => {
    const dataRoot = 'G:\\stylish-house-data\\rimskie-imports';
    const runDir = `${dataRoot}\\run-001`;
    const profileDir = `${runDir}\\profile`;
    const nestedDir = `${profileDir}\\Cache`;
    const directories = new Map([
        [runDir, ['profile']],
        [profileDir, ['Cache']],
        [nestedDir, []],
    ]);
    const fakeDirectoryStats = { isDirectory: () => true, isSymbolicLink: () => false, isFile: () => false };

    await assert.rejects(assertRunDirectorySafe(dataRoot, 'run-001', {
        platform: 'win32',
        realpath: async (path) => path === nestedDir ? 'C:\\escaped-profile-cache' : path,
        lstat: async () => fakeDirectoryStats,
        readdir: async (path) => directories.get(path) || [],
    }), /nested run path|junction|drive C:/i);
});

test('recursive run safety rejects a real nested hard-linked file', async (t) => {
    const dataRoot = `G:\\stylish-house-data\\rimskie-imports\\hardlink-${process.pid}-${Date.now()}`;
    await mkdir(dataRoot, { recursive: true });
    t.after(() => rm(dataRoot, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir: dataRoot, runId: 'run-001' });
    const profileDir = join(store.runDir, 'profile');
    await mkdir(profileDir);
    const outsideFile = join(dataRoot, 'outside.bin');
    await writeFile(outsideFile, 'private');
    await link(outsideFile, join(profileDir, 'cache.bin'));

    await assert.rejects(
        assertRunDirectorySafe(dataRoot, 'run-001'),
        /hard-linked file/i,
    );
});

test('run safety recovers an exact published config create alias', async (t) => {
    const dataRoot = `G:\\stylish-house-data\\rimskie-imports\\config-publish-${process.pid}-${Date.now()}`;
    await mkdir(dataRoot, { recursive: true });
    t.after(() => rm(dataRoot, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir: dataRoot, runId: 'run-001' });
    await initializeRun(store, {
        maxRequests: 3, maxProducts: 1,
        maxRequestsExplicit: false, maxProductsExplicit: false,
    });
    const createPath = `${store.configPath}.11111111-1111-4111-8111-111111111111.create`;
    await link(store.configPath, createPath);
    assert.equal((await lstat(store.configPath)).nlink, 2);

    await assertRunDirectorySafe(dataRoot, 'run-001');

    assert.equal((await lstat(store.configPath)).nlink, 1);
    await assert.rejects(lstat(createPath), /ENOENT/);
    assert.equal((await store.readConfig()).schema_version, configSchemaVersion);
});

test('run safety leaves an unpublished config create file in place', async (t) => {
    const dataRoot = `G:\\stylish-house-data\\rimskie-imports\\config-prelink-${process.pid}-${Date.now()}`;
    await mkdir(dataRoot, { recursive: true });
    t.after(() => rm(dataRoot, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir: dataRoot, runId: 'run-001' });
    const createPath = `${store.configPath}.44444444-4444-4444-8444-444444444444.create`;
    await writeFile(createPath, 'not-yet-published', 'utf8');

    await assertRunDirectorySafe(dataRoot, 'run-001');

    assert.equal(await readFile(createPath, 'utf8'), 'not-yet-published');
    assert.equal((await lstat(createPath)).nlink, 1);
});

test('run safety tolerates creator cleanup racing exact alias recovery', async (t) => {
    const dataRoot = `G:\\stylish-house-data\\rimskie-imports\\config-cleanup-race-${process.pid}-${Date.now()}`;
    await mkdir(dataRoot, { recursive: true });
    t.after(() => rm(dataRoot, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir: dataRoot, runId: 'run-001' });
    await writeFile(store.configPath, '{}\n', 'utf8');
    const createPath = `${store.configPath}.88888888-8888-4888-8888-888888888888.create`;
    await link(store.configPath, createPath);

    await assertRunDirectorySafe(dataRoot, 'run-001', {
        unlink: async (path) => {
            await unlink(path);
            await unlink(path);
        },
    });

    assert.equal((await lstat(store.configPath)).nlink, 1);
});

test('run safety fails closed for a config create alias linked to a foreign node', async (t) => {
    const dataRoot = `G:\\stylish-house-data\\rimskie-imports\\config-foreign-${process.pid}-${Date.now()}`;
    await mkdir(dataRoot, { recursive: true });
    t.after(() => rm(dataRoot, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir: dataRoot, runId: 'run-001' });
    await writeFile(store.configPath, '{}\n', 'utf8');
    const foreignPath = join(dataRoot, 'foreign-config.json');
    const createPath = `${store.configPath}.55555555-5555-4555-8555-555555555555.create`;
    await writeFile(foreignPath, '{}\n', 'utf8');
    await link(foreignPath, createPath);

    await assert.rejects(assertRunDirectorySafe(dataRoot, 'run-001'), /hard-linked file/i);
    assert.equal((await lstat(createPath)).nlink, 2);
});

test('run safety recovers an exact published control claim alias', async (t) => {
    const dataRoot = `G:\\stylish-house-data\\rimskie-imports\\claim-publish-${process.pid}-${Date.now()}`;
    await mkdir(dataRoot, { recursive: true });
    t.after(() => rm(dataRoot, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir: dataRoot, runId: 'run-001' });
    const token = '22222222-2222-4222-8222-222222222222';
    const lockPath = `${join(store.runDir, 'control.json')}.lock`;
    const claimPath = `${lockPath}.claim.${process.pid}.${token}`;
    await writeFile(claimPath, `${JSON.stringify({
        pid: process.pid, token, processFingerprint: 'test-process-start',
    })}\n`, 'utf8');
    await link(claimPath, lockPath);

    await assertRunDirectorySafe(dataRoot, 'run-001');

    assert.equal((await lstat(lockPath)).nlink, 1);
    await assert.rejects(lstat(claimPath), /ENOENT/);
    assert.equal(JSON.parse(await readFile(lockPath, 'utf8')).token, token);
});

test('run safety recovers a claim alias linked to its exact stale-lock target', async (t) => {
    const dataRoot = `G:\\stylish-house-data\\rimskie-imports\\claim-stale-${process.pid}-${Date.now()}`;
    await mkdir(dataRoot, { recursive: true });
    t.after(() => rm(dataRoot, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir: dataRoot, runId: 'run-001' });
    const token = '33333333-3333-4333-8333-333333333333';
    const lockPath = `${join(store.runDir, 'control.json')}.lock`;
    const claimPath = `${lockPath}.claim.${process.pid}.${token}`;
    const stalePath = `${lockPath}.stale.${token}`;
    await writeFile(claimPath, `${JSON.stringify({
        pid: process.pid, token, processFingerprint: 'test-process-start',
    })}\n`, 'utf8');
    await link(claimPath, lockPath);
    await rename(lockPath, stalePath);

    await assertRunDirectorySafe(dataRoot, 'run-001');

    assert.equal((await lstat(stalePath)).nlink, 1);
    await assert.rejects(lstat(claimPath), /ENOENT/);
    assert.equal(JSON.parse(await readFile(stalePath, 'utf8')).token, token);
});

test('run safety allows only an inode- and token-matched stale-lock pair', async (t) => {
    const dataRoot = `G:\\stylish-house-data\\rimskie-imports\\lock-stale-${process.pid}-${Date.now()}`;
    await mkdir(dataRoot, { recursive: true });
    t.after(() => rm(dataRoot, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir: dataRoot, runId: 'run-001' });
    const token = '66666666-6666-4666-8666-666666666666';
    const lockPath = `${join(store.runDir, 'control.json')}.lock`;
    const stalePath = `${lockPath}.stale.${token}`;
    await writeFile(lockPath, `${JSON.stringify({ pid: process.pid, token })}\n`, 'utf8');
    await link(lockPath, stalePath);

    await assertRunDirectorySafe(dataRoot, 'run-001');

    const mismatchedPath = `${lockPath}.stale.77777777-7777-4777-8777-777777777777`;
    await rename(stalePath, mismatchedPath);
    await assert.rejects(assertRunDirectorySafe(dataRoot, 'run-001'), /hard-linked file/i);
});

test('stale-lock collision recovery converges to one token- and inode-matched alias', async (t) => {
    const dataRoot = `G:\\stylish-house-data\\rimskie-imports\\lock-stale-converge-${process.pid}-${Date.now()}`;
    await mkdir(dataRoot, { recursive: true });
    t.after(() => rm(dataRoot, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir: dataRoot, runId: 'run-001' });
    const token = '99999999-9999-4999-8999-999999999999';
    const controlPath = join(store.runDir, 'control.json');
    const lockPath = `${controlPath}.lock`;
    const stalePath = `${lockPath}.stale.${token}`;
    await writeFile(lockPath, `${JSON.stringify({
        pid: 999999, token, processFingerprint: 'dead-process-start',
    })}\n`, 'utf8');
    await link(lockPath, stalePath);
    const control = new ControlFile(controlPath, { isLiveProcess: () => false });

    await assertRunDirectorySafe(dataRoot, 'run-001');
    await control.update({ pause: true });
    await assertRunDirectorySafe(dataRoot, 'run-001');

    const staleNames = (await readdir(store.runDir)).filter((name) => name.startsWith(
        `control.json.lock.stale.${token}`,
    ));
    assert.deepEqual(staleNames, [`control.json.lock.stale.${token}`]);
    assert.equal((await lstat(stalePath)).nlink, 1);
});

test('numeric product IDs and safe source slugs are mandatory for persisted paths', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-identifiers-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });

    for (const externalId of ['abc', '11889:stream', 'CON', '../11889']) {
        await assert.rejects(store.saveProduct(externalId, {}), /product external ID.*safe/i);
    }
    for (const slug of ['White_With_Underscore', 'nul', 'bad:stream', '../white']) {
        await assert.rejects(store.saveSource(slug, {}), /source slug.*safe/i);
    }
});
