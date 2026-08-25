import assert from 'node:assert/strict';
import { mkdtemp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';

import { RunStore } from '../../scripts/rimskie-import/lib/run-store.mjs';

async function temporaryRoot(t) {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-import-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));

    return rootDir;
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

    await store.saveSource('white', { label: 'Белые', target_slug: 'white' });
    await store.saveProduct('11889', {
        externalId: '11889',
        firstImagePath: 'images/11889.webp',
    });
    await store.appendMembership({ sourceSlug: 'white', externalId: '11889' });

    await assert.rejects(store.exportManifest(), /first image/i);
    await mkdir(join(rootDir, 'run-003', 'images'), { recursive: true });
    await writeFile(join(rootDir, 'run-003', 'images', '11889.webp'), 'fixture image', 'utf8');

    const manifest = await store.exportManifest();
    assert.equal(manifest.schema_version, 'stylish-house.catalog-import/v1');
    assert.equal(manifest.products[0].externalId, '11889');
    assert.equal(JSON.parse(await readFile(join(rootDir, 'run-003', 'export.json'), 'utf8')).run_id, 'run-003');
});

test('run store rejects traversal in run, source, and product identifiers', async (t) => {
    const rootDir = await temporaryRoot(t);

    await assert.rejects(RunStore.open({ rootDir, runId: '../escape' }), /path traversal/i);
    const store = await RunStore.open({ rootDir, runId: 'run-004' });
    await assert.rejects(store.saveSource('../escape', {}), /path traversal/i);
    await assert.rejects(store.saveProduct('../escape', {}), /path traversal/i);
});
