import assert from 'node:assert/strict';
import { mkdir, mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import test from 'node:test';

import { importBrowserSeed } from '../../scripts/rimskie-import/lib/browser-seed-import.mjs';
import { initializeRun } from '../../scripts/rimskie-import/cli.mjs';
import { RunStore } from '../../scripts/rimskie-import/lib/run-store.mjs';
import { validateImageFile } from '../../scripts/rimskie-import/lib/webp.mjs';

async function createFixture(t) {
    const parent = 'G:\\stylish-house-data\\rimskie-imports\\browser-seed-tests';
    await mkdir(parent, { recursive: true });
    const rootDir = await mkdtemp(join(parent, 'run-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const runId = 'run-browser-seed-test';
    const store = await RunStore.open({ rootDir, runId });
    const initialized = await initializeRun(store, {
        maxRequests: Number.POSITIVE_INFINITY,
        maxProducts: 50,
        maxRequestsExplicit: false,
        maxProductsExplicit: true,
    });
    const state = structuredClone(initialized.state);
    state.status = 'paused';
    state.pauseReason = 'operator';
    await store.checkpoint(state);

    const seedRoot = join(store.runDir, 'browser-seed');
    const seedProducts = join(seedRoot, 'products');
    const seedImages = join(seedRoot, 'images');
    await mkdir(seedProducts, { recursive: true });
    await mkdir(seedImages, { recursive: true });
    const sourceSlug = state.sources[0].sourceSlug;
    const product = {
        externalId: '11889',
        sourceUrl: 'https://rimskie.com/products/11889-test',
        sourceTitle: 'Римская штора тестовая',
        sourceDescription: 'Полное исходное описание товара длиной больше ста символов. '.repeat(3),
        sourcePrice: '4799.00',
        firstImageUrl: 'https://rimskie.com/media/output/test.webp',
        firstImagePath: 'images/11889.webp',
        attributes: { material: ['Лён'] },
        sourceSlugs: [sourceSlug],
    };
    await writeFile(join(seedProducts, '11889.json'), `${JSON.stringify(product, null, 2)}\n`, 'utf8');
    const fixtureImage = await readFile(join(
        process.cwd(), 'tests', 'fixtures', 'catalog-import', 'images', '11889.webp',
    ));
    await writeFile(join(seedImages, '11889.webp'), fixtureImage);

    return { store, sourceSlug, product };
}

test('browser seed import durably merges completed products into the collector checkpoint', async (t) => {
    const { store, sourceSlug, product } = await createFixture(t);

    const first = await importBrowserSeed({ store });
    const second = await importBrowserSeed({ store });
    const state = await store.readState();
    const memberships = await store.readMemberships();

    assert.deepEqual(first, { imported: 1, skipped: 0, completedProducts: 1 });
    assert.deepEqual(second, { imported: 0, skipped: 1, completedProducts: 1 });
    assert.deepEqual(state.completedProductIds, ['11889']);
    assert.deepEqual(await store.readProduct('11889'), product);
    assert.deepEqual(memberships, [{ sourceSlug, externalId: '11889' }]);
    assert.equal(await validateImageFile(join(store.imagesDir, '11889.webp'), 'webp'), true);
    assert.equal(state.status, 'paused');
    assert.equal(state.pauseReason, 'operator');
});

test('browser seed import rejects a product whose image is missing', async (t) => {
    const { store } = await createFixture(t);
    await rm(join(store.runDir, 'browser-seed', 'images', '11889.webp'));

    await assert.rejects(importBrowserSeed({ store }), /missing.*image.*11889/i);
    assert.deepEqual((await store.readState()).completedProductIds, []);
});
