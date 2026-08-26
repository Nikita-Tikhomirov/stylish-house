import assert from 'node:assert/strict';
import { mkdtemp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';

import {
    configDigest,
    configSchemaVersion,
    RunStore,
} from '../../scripts/rimskie-import/lib/run-store.mjs';

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
    const configuredSource = {
        label: 'Белые',
        sourceSlug: 'white',
        sourceUrl: 'https://rimskie.com/catalog/rimskie-shtory/white',
        enabled: true,
        sortOrder: 1,
        pendingProducts: [],
        completed: false,
        pages: 0,
        nextPageUrl: 'https://rimskie.com/catalog/rimskie-shtory/white',
    };
    const completedSource = {
        ...configuredSource,
        completed: true,
        nextPageUrl: null,
        pages: 1,
    };
    const config = {
        schema_version: configSchemaVersion,
        sources: [configuredSource],
        limits: {
            html_delay_ms: [20_000, 40_000], image_delay_ms: [10_000, 20_000],
            challenge_delay_ms: [10_000, 20_000],
            hourly_requests: 120, backoff_ms: [120_000, 300_000, 900_000],
            concurrency: 1, max_requests: null, max_products: null,
        },
    };
    await store.initializeConfig(config);
    await store.checkpoint({
        status: 'completed',
        requestCount: 3,
        completedProductIds: ['11889'],
        sources: [completedSource],
        configDigest: configDigest(config),
    });
    await store.saveSource('white', {
        label: 'Белые', source_url: configuredSource.sourceUrl, target_slug: 'white',
        enabled: true, sort_order: 1, status: 'completed', pages: 1, next_page_url: null,
    });
    await store.saveProduct('11889', {
        externalId: '11889',
        sourceUrl: 'https://rimskie.com/products/11889-example',
        sourceTitle: 'Римская штора 11889',
        sourceDescription: 'Фактическое описание',
        sourcePrice: '2708.00',
        firstImageUrl: 'https://rimskie.com/media/output/first.webp',
        firstImagePath: 'images/11889.webp',
        attributes: {},
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
    assert.deepEqual(manifest.counts, { sources: 1, products: 1, memberships: 1, images: 1 });
    assert.deepEqual(manifest.images, [{
        external_id: '11889',
        path: 'images/11889.webp',
        byte_length: 30,
        sha256: '4a5d3458dfeabd63090fe08fdc8224ae61d45f0c430eee3c0544a92891af3b69',
    }]);
    assert.match(manifest.config_digest, /^[a-f0-9]{64}$/);
    assert.equal(JSON.parse(await readFile(join(rootDir, 'run-003', 'export.json'), 'utf8')).run_id, 'run-003');
});

test('export accepts an empty Task 1 attributes record', async (t) => {
    const rootDir = await temporaryRoot(t);
    const store = await RunStore.open({ rootDir, runId: 'run-empty-attributes' });
    await prepareCompletedRun(store);
    await writeFile(join(store.imagesDir, '11889.webp'), validWebpBytes);

    const manifest = await store.exportManifest();

    assert.deepEqual(manifest.products[0].attributes, {});
});

test('export rejects malformed Task 1 attributes records without normalization', async (t) => {
    const rootDir = await temporaryRoot(t);
    const store = await RunStore.open({ rootDir, runId: 'run-invalid-attributes' });
    await prepareCompletedRun(store);
    await writeFile(join(store.imagesDir, '11889.webp'), validWebpBytes);
    const baseProduct = await store.readProduct('11889');
    const invalidAttributes = [
        ['missing', undefined],
        ['null', null],
        ['array record', []],
        ['scalar record', 'material'],
        ['empty key', { '': ['cotton'] }],
        ['unsafe key', { 'bad-key': ['cotton'] }],
        ['uppercase key', { Material: ['cotton'] }],
        ['double underscore key', { material__type: ['cotton'] }],
        ['non-array values', { material: 'cotton' }],
        ['empty values', { material: [] }],
        ['duplicate values', { material: ['cotton', 'cotton'] }],
        ['empty string value', { material: [''] }],
        ['whitespace string value', { material: [' '] }],
        ['non-string value', { material: [42] }],
        ['untrimmed value', { material: [' cotton '] }],
    ];

    for (const [label, attributes] of invalidAttributes) {
        const product = { ...baseProduct, attributes };
        if (label === 'missing') delete product.attributes;
        await store.saveProduct('11889', product);

        await assert.rejects(
            store.exportManifest(),
            /attributes/i,
            label,
        );
    }
});

test('export manifest rejects paths outside images directory even when target files exist', async (t) => {
    const rootDir = await temporaryRoot(t);
    const store = await RunStore.open({ rootDir, runId: 'run-004' });
    const runDir = join(rootDir, 'run-004');

    await prepareCompletedRun(store);

    for (const firstImagePath of ['images/../state.json', 'state.json', join(runDir, 'state.json')]) {
        await store.saveProduct('11889', { ...(await store.readProduct('11889')), firstImagePath });
        await assert.rejects(store.exportManifest(), /first image path/i);
    }
});

test('export rejects a persisted source record that contradicts immutable config', async (t) => {
    const rootDir = await temporaryRoot(t);
    const store = await RunStore.open({ rootDir, runId: 'run-source-tamper' });
    await prepareCompletedRun(store);
    await writeFile(join(store.imagesDir, '11889.webp'), validWebpBytes);
    const sourcePath = join(store.sourcesDir, 'white.json');
    const source = JSON.parse(await readFile(sourcePath, 'utf8'));
    source.label = 'Подмененная категория';
    await writeFile(sourcePath, `${JSON.stringify(source, null, 2)}\n`, 'utf8');

    await assert.rejects(store.exportManifest(), /source records.*config|contradict/i);
});

test('export preserves immutable source order independent of filenames', async (t) => {
    const rootDir = await temporaryRoot(t);
    const store = await RunStore.open({ rootDir, runId: 'run-source-order' });
    const sourceUrls = [
        'https://rimskie.com/catalog/rimskie-shtory/white',
        'https://rimskie.com/catalog/rimskie-shtory/grey',
    ];
    const configuredSources = ['white', 'grey'].map((sourceSlug, index) => ({
        label: sourceSlug, sourceSlug, sourceUrl: sourceUrls[index], enabled: true,
        sortOrder: index + 1, pendingProducts: [], completed: false, pages: 0,
        nextPageUrl: sourceUrls[index],
    }));
    const config = {
        schema_version: configSchemaVersion,
        sources: configuredSources,
        limits: {
            html_delay_ms: [20_000, 40_000], image_delay_ms: [10_000, 20_000],
            challenge_delay_ms: [10_000, 20_000],
            hourly_requests: 120, backoff_ms: [120_000, 300_000, 900_000],
            concurrency: 1, max_requests: null, max_products: null,
        },
    };
    await store.initializeConfig(config);
    await store.checkpoint({
        status: 'completed', completedProductIds: ['11889'],
        requestCount: 4,
        configDigest: configDigest(config),
        sources: configuredSources.map((source) => ({
            ...source, completed: true, pages: 1, nextPageUrl: null,
        })),
    });
    for (const source of configuredSources) {
        await store.saveSource(source.sourceSlug, {
            label: source.label, source_url: source.sourceUrl, target_slug: source.sourceSlug,
            enabled: true, sort_order: source.sortOrder, status: 'completed',
            pages: 1, next_page_url: null,
        });
        await store.appendMembership({ sourceSlug: source.sourceSlug, externalId: '11889' });
    }
    await store.saveProduct('11889', {
        externalId: '11889', sourceUrl: 'https://rimskie.com/products/11889-example',
        sourceTitle: 'Product 11889', sourceDescription: 'Description', sourcePrice: '2708.00',
        firstImageUrl: 'https://rimskie.com/media/output/first.webp',
        firstImagePath: 'images/11889.webp', attributes: {},
    });
    await writeFile(join(store.imagesDir, '11889.webp'), validWebpBytes);

    const manifest = await store.exportManifest();

    assert.deepEqual(manifest.sources.map(({ target_slug: slug }) => slug), ['white', 'grey']);
});

test('export rejects contradictory completed-source progress fields', async (t) => {
    const rootDir = await temporaryRoot(t);
    const store = await RunStore.open({ rootDir, runId: 'run-source-progress' });
    await prepareCompletedRun(store);
    await writeFile(join(store.imagesDir, '11889.webp'), validWebpBytes);
    const state = await store.readState();
    state.sources[0].pendingProducts = [{
        externalId: '11900', sourceUrl: 'https://rimskie.com/products/11900-example',
    }];
    await store.checkpoint(state);

    await assert.rejects(store.exportManifest(), /source progress|pending products/i);
});

test('export rejects draft-stage and incomplete product records', async (t) => {
    const rootDir = await temporaryRoot(t);
    const store = await RunStore.open({ rootDir, runId: 'run-product-draft' });
    await prepareCompletedRun(store);
    await writeFile(join(store.imagesDir, '11889.webp'), validWebpBytes);
    const product = await store.readProduct('11889');
    product.collectionStage = 'html-complete';
    await store.saveProduct('11889', product);

    await assert.rejects(store.exportManifest(), /draft-stage|incomplete product/i);
});

test('export requires every Task 4 product field', async (t) => {
    const requiredFields = [
        'sourceUrl', 'sourceTitle', 'sourceDescription', 'sourcePrice',
        'firstImageUrl', 'firstImagePath',
    ];
    for (const field of requiredFields) {
        const rootDir = await temporaryRoot(t);
        const store = await RunStore.open({ rootDir, runId: `missing-${field.toLowerCase()}` });
        await prepareCompletedRun(store);
        await writeFile(join(store.imagesDir, '11889.webp'), validWebpBytes);
        const product = await store.readProduct('11889');
        delete product[field];
        await store.saveProduct('11889', product);

        await assert.rejects(store.exportManifest(), new RegExp(field, 'i'));
    }
});

test('export validates exact product and first-image donor URL contexts', async (t) => {
    const mutations = [
        ['sourceUrl', 'https://rimskie.com/catalog/rimskie-shtory/white'],
        ['firstImageUrl', 'https://rimskie.com/products/11889-example'],
        ['sourceUrl', 'https://evil.example/products/11889-example'],
    ];
    for (const [index, [field, value]] of mutations.entries()) {
        const rootDir = await temporaryRoot(t);
        const store = await RunStore.open({
            rootDir, runId: `bad-url-${field.toLowerCase()}-${index}`,
        });
        await prepareCompletedRun(store);
        await writeFile(join(store.imagesDir, '11889.webp'), validWebpBytes);
        const product = await store.readProduct('11889');
        product[field] = value;
        await store.saveProduct('11889', product);

        await assert.rejects(store.exportManifest(), /approved.*(?:product|image)|path boundary|origin/i);
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
    const runningSource = {
        label: 'Белые', sourceSlug: 'white',
        sourceUrl: 'https://rimskie.com/catalog/rimskie-shtory/white',
        enabled: true, sortOrder: 1, pendingProducts: [], completed: false, pages: 0,
        nextPageUrl: 'https://rimskie.com/catalog/rimskie-shtory/white',
    };
    const config = {
        schema_version: configSchemaVersion,
        sources: [runningSource],
        limits: {
            html_delay_ms: [20_000, 40_000], image_delay_ms: [10_000, 20_000],
            challenge_delay_ms: [10_000, 20_000],
            hourly_requests: 120, backoff_ms: [120_000, 300_000, 900_000],
            concurrency: 1, max_requests: null, max_products: null,
        },
    };
    await store.initializeConfig(config);
    await store.checkpoint({
        status: 'running', requestCount: 0, completedProductIds: [], sources: [runningSource],
        configDigest: configDigest(config),
    });

    await assert.rejects(store.exportManifest(), /completed is required/i);

    const completedSource = {
        ...runningSource, completed: true, pages: 1, nextPageUrl: null,
    };
    await store.checkpoint({
        status: 'completed', requestCount: 0, completedProductIds: [], sources: [completedSource],
        configDigest: configDigest(config),
    });
    await store.saveSource('white', {
        label: 'Белые', source_url: runningSource.sourceUrl, target_slug: 'white',
        enabled: true, sort_order: 1, status: 'completed', pages: 1, next_page_url: null,
    });
    await assert.rejects(store.exportManifest(), /empty membership/i);
});
