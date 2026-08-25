import assert from 'node:assert/strict';
import { mkdtemp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';

import { configSchemaVersion, RunStore } from '../../scripts/rimskie-import/lib/run-store.mjs';

const validWebpBytes = Buffer.from([
    0x52, 0x49, 0x46, 0x46, 0x16, 0x00, 0x00, 0x00,
    0x57, 0x45, 0x42, 0x50, 0x56, 0x50, 0x38, 0x58,
    0x0a, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
    0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
]);

async function temporaryRoot(t) {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-import-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));

    return rootDir;
}

async function prepareCompletedRun(store) {
    const completedSource = {
        sourceSlug: 'white',
        sourceUrl: 'https://rimskie.com/catalog/rimskie-shtory/white',
        completed: true,
        nextPageUrl: null,
    };
    await store.initializeConfig({
        schema_version: configSchemaVersion,
        sources: [completedSource],
        limits: { max_requests: null, max_products: null },
    });
    await store.checkpoint({
        status: 'completed',
        completedProductIds: ['11889'],
        sources: [completedSource],
    });
    await store.saveSource('white', { label: 'Белые', target_slug: 'white', status: 'completed' });
    await store.saveProduct('11889', {
        externalId: '11889',
        firstImagePath: 'images/11889.webp',
    });
    await store.appendMembership({ sourceSlug: 'white', externalId: '11889' });
}

test('checkpoint survives reopen and membership append is idempotent', async (t) => {
    const rootDir = await temporaryRoot(t);
    const store = await RunStore.open({ rootDir, runId: 'run-001' });

    await store.checkpoint({ status: 'running', nextProductIndex: 4 });
    await store.appendMembership({ sourceSlug: 'white', externalId: '11889' });
    await store.appendMembership({ sourceSlug: 'white', externalId: '11889' });

    const reopened = await RunStore.open({ rootDir, runId: 'run-001' });
    assert.equal((await reopened.readState()).nextProductIndex, 4);
    assert.equal((await reopened.readMemberships()).length, 1);
});

test('run store saves source and product records and appends events as NDJSON', async (t) => {
    const rootDir = await temporaryRoot(t);
    const store = await RunStore.open({ rootDir, runId: 'run-002' });

    await store.saveSource('white', { label: 'Белые', status: 'complete' });
    await store.saveProduct('11889', { externalId: '11889', firstImagePath: 'images/11889.webp' });
    await store.appendEvent({ type: 'source-complete', sourceSlug: 'white' });

    const source = JSON.parse(await readFile(join(rootDir, 'run-002', 'sources', 'white.json'), 'utf8'));
    const product = JSON.parse(await readFile(join(rootDir, 'run-002', 'products', '11889.json'), 'utf8'));
    const events = await readFile(join(rootDir, 'run-002', 'events.ndjson'), 'utf8');
    assert.deepEqual(source, { label: 'Белые', status: 'complete' });
    assert.deepEqual(product, { externalId: '11889', firstImagePath: 'images/11889.webp' });
    assert.deepEqual(events.trim().split('\n').map(JSON.parse), [{ type: 'source-complete', sourceSlug: 'white' }]);
});

test('export manifest requires every referenced product JSON and first image', async (t) => {
    const rootDir = await temporaryRoot(t);
    const store = await RunStore.open({ rootDir, runId: 'run-003' });

    await prepareCompletedRun(store);

    await assert.rejects(store.exportManifest(), /first image/i);
    await mkdir(join(rootDir, 'run-003', 'images'), { recursive: true });
    await writeFile(join(rootDir, 'run-003', 'images', '11889.webp'), validWebpBytes);

    const manifest = await store.exportManifest();
    assert.equal(manifest.schema_version, 'stylish-house.catalog-import/v1');
    assert.equal(manifest.products[0].externalId, '11889');
    assert.equal(JSON.parse(await readFile(join(rootDir, 'run-003', 'export.json'), 'utf8')).run_id, 'run-003');
});

test('export manifest rejects paths outside images directory even when target files exist', async (t) => {
    const rootDir = await temporaryRoot(t);
    const store = await RunStore.open({ rootDir, runId: 'run-004' });
    const runDir = join(rootDir, 'run-004');

    await prepareCompletedRun(store);

    for (const firstImagePath of ['images/../state.json', 'state.json', join(runDir, 'state.json')]) {
        await store.saveProduct('11889', { externalId: '11889', firstImagePath });
        await assert.rejects(store.exportManifest(), /first image path/i);
    }
});

test('run store rejects traversal in run, source, and product identifiers', async (t) => {
    const rootDir = await temporaryRoot(t);

    await assert.rejects(RunStore.open({ rootDir, runId: '../escape' }), /safe path|path traversal/i);
    const store = await RunStore.open({ rootDir, runId: 'run-004' });
    await assert.rejects(store.saveSource('../escape', {}), /safe path|path traversal/i);
    await assert.rejects(store.saveProduct('../escape', {}), /safe path|path traversal/i);
});

test('export rejects running, empty, and inconsistent runs', async (t) => {
    const rootDir = await temporaryRoot(t);
    const store = await RunStore.open({ rootDir, runId: 'run-005' });
    const runningSource = { sourceSlug: 'white', completed: false };
    await store.initializeConfig({
        schema_version: configSchemaVersion,
        sources: [runningSource],
        limits: { max_requests: null, max_products: null },
    });
    await store.checkpoint({
        status: 'running', completedProductIds: [], sources: [runningSource],
    });

    await assert.rejects(store.exportManifest(), /completed is required/i);

    const completedSource = { ...runningSource, completed: true };
    await store.checkpoint({
        status: 'completed', completedProductIds: [], sources: [completedSource],
    });
    await store.saveSource('white', { status: 'completed' });
    await assert.rejects(store.exportManifest(), /empty membership/i);
});
