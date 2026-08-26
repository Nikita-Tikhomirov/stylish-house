import assert from 'node:assert/strict';
import { EventEmitter } from 'node:events';
import { mkdir, mkdtemp, rm } from 'node:fs/promises';
import { join } from 'node:path';
import test from 'node:test';

import { initializeRun } from '../../scripts/rimskie-import/cli.mjs';
import { CollectorSupervisor } from '../../scripts/rimskie-import/gui/process-supervisor.mjs';
import { createStatusService } from '../../scripts/rimskie-import/gui/status-service.mjs';
import { RunStore } from '../../scripts/rimskie-import/lib/run-store.mjs';

async function createDataRoot(t) {
    const parent = 'G:\\stylish-house-data\\rimskie-imports\\gui-tests';
    await mkdir(parent, { recursive: true });
    const root = await mkdtemp(join(parent, 'service-'));
    t.after(() => rm(root, { recursive: true, force: true }));
    return root;
}

async function createRunFixture(t, runId = 'run-20260826-120000-test') {
    const dataRoot = await createDataRoot(t);
    const store = await RunStore.open({ rootDir: dataRoot, runId });
    const initialized = await initializeRun(store, {
        maxRequests: Number.POSITIVE_INFINITY,
        maxProducts: Number.POSITIVE_INFINITY,
        maxRequestsExplicit: false,
        maxProductsExplicit: false,
    });
    const state = structuredClone(initialized.state);
    state.status = 'paused';
    state.pauseReason = 'operator';
    state.requestCount = 7;
    state.requestPolicy = {
        requestTimes: [1_787_742_799_000, 1_787_739_000_000],
        consecutiveFailures: 0,
        pauseRequired: false,
        lastFailureKind: null,
        backoffUntil: null,
    };
    state.completedProductIds = ['11889'];
    state.sources[0].pages = 2;
    state.sources[0].pendingProducts = [];
    await store.checkpoint(state);
    await store.saveProduct('11889', {
        external_id: '11889',
        name: 'Римская штора тестовая',
        source_price: { amount: 4799, currency: 'RUB' },
        source_url: 'https://rimskie.com/products/11889-test',
        first_image_path: 'images/11889.webp',
    });
    await store.appendMembership({
        sourceSlug: state.sources[0].sourceSlug,
        externalId: '11889',
    });
    await store.appendEvent({
        at: '2026-08-26T09:00:00.000Z',
        type: 'pause',
        reason: 'operator',
    });
    return { dataRoot, runId, store };
}

function fakeChild() {
    const child = new EventEmitter();
    child.pid = 4242;
    child.stdout = new EventEmitter();
    child.stderr = new EventEmitter();
    return child;
}

test('status service restores persisted run, products and events after restart', async (t) => {
    const { dataRoot, runId } = await createRunFixture(t);
    await mkdir(join(dataRoot, 'downloads'));

    const firstService = await createStatusService({ dataRoot, now: () => 1_787_742_800_000 });
    const firstSnapshot = await firstService.getRunSnapshot(runId);
    const secondService = await createStatusService({ dataRoot, now: () => 1_787_742_800_000 });
    const runs = await secondService.listRuns();
    const products = await secondService.listProducts(runId, 1, 20);

    assert.equal(firstSnapshot.status, 'paused');
    assert.equal(firstSnapshot.pauseReason, 'operator');
    assert.equal(firstSnapshot.metrics.uniqueProducts, 1);
    assert.equal(firstSnapshot.metrics.memberships, 1);
    assert.equal(firstSnapshot.metrics.requestsLastHour, 1);
    assert.equal(firstSnapshot.metrics.hourlyLimit, 20);
    assert.equal(firstSnapshot.events.at(-1).reason, 'operator');
    assert.deepEqual(runs.map((run) => run.id), [runId]);
    assert.equal(products.total, 1);
    assert.equal(products.items[0].externalId, '11889');
    assert.equal(products.items[0].name, 'Римская штора тестовая');
    assert.equal(products.items[0].sourcePrice.amount, 4799);
});

test('status service rejects traversal identifiers before reading a run', async (t) => {
    const { dataRoot } = await createRunFixture(t);
    const service = await createStatusService({ dataRoot });

    await assert.rejects(service.getRunSnapshot('../outside'), /run ID.*safe/i);
    await assert.rejects(service.listProducts('run-20260826-120000-test', 0, 20), /page/i);
    await assert.rejects(service.getImagePath('run-20260826-120000-test', '../11889'), /product external ID.*safe/i);
});

test('normal GUI start invokes unlimited collector mode with shell disabled', async (t) => {
    const dataRoot = await createDataRoot(t);
    const calls = [];
    const supervisor = new CollectorSupervisor({
        cliPath: 'D:\\project\\scripts\\rimskie-import\\cli.mjs',
        dataRoot,
        now: () => new Date('2026-08-26T09:10:11.000Z'),
        randomId: () => 'abcd1234',
        spawnProcess(command, args, options) {
            calls.push({ command, args, options });
            return fakeChild();
        },
    });

    const result = await supervisor.start();

    assert.equal(result.runId, 'run-20260826-121011-abcd1234');
    assert.deepEqual(calls[0].args, [
        'D:\\project\\scripts\\rimskie-import\\cli.mjs',
        'start',
        '--run', result.runId,
        '--data-root', dataRoot,
        '--json',
    ]);
    assert.equal(calls[0].options.shell, false);
    assert.equal(calls[0].args.includes('--max-products'), false);
    assert.equal(calls[0].args.includes('--max-requests'), false);
});

test('supervisor dispatches only fixed collector actions and safe run IDs', async (t) => {
    const dataRoot = await createDataRoot(t);
    const calls = [];
    const supervisor = new CollectorSupervisor({
        cliPath: 'D:\\project\\scripts\\rimskie-import\\cli.mjs',
        dataRoot,
        spawnProcess(command, args, options) {
            calls.push({ command, args, options });
            return fakeChild();
        },
    });

    await supervisor.pause('run-safe');
    await supervisor.resume('run-safe');
    await supervisor.stop('run-safe');
    await supervisor.exportRun('run-safe');
    await supervisor.openFolder('run-safe');

    assert.deepEqual(calls.slice(0, 4).map((call) => call.args[1]), [
        'pause', 'resume', 'stop', 'export',
    ]);
    assert.equal(calls.every((call) => call.options.shell === false), true);
    assert.equal(calls[4].command.toLowerCase(), 'explorer.exe');
    assert.deepEqual(calls[4].args, [join(dataRoot, 'run-safe')]);
    await assert.rejects(supervisor.resume('../unsafe'), /run ID.*safe/i);
});
