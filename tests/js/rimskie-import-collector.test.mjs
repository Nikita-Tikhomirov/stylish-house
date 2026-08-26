import assert from 'node:assert/strict';
import { execFile } from 'node:child_process';
import { mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';
import { promisify } from 'node:util';
import { fileURLToPath } from 'node:url';

import {
    assertRunDirectorySafe,
    ControlFile,
    parseArguments,
    resolveDataRoot,
} from '../../scripts/rimskie-import/cli.mjs';
import { Collector } from '../../scripts/rimskie-import/lib/collector.mjs';
import {
    DonorRequestError,
    findBrowserExecutable,
    PlaywrightTransport,
} from '../../scripts/rimskie-import/lib/playwright-transport.mjs';
import { RequestPolicy } from '../../scripts/rimskie-import/lib/request-policy.mjs';
import {
    configDigest,
    configSchemaVersion,
    RunStore,
} from '../../scripts/rimskie-import/lib/run-store.mjs';

const whiteUrl = 'https://rimskie.com/catalog/rimskie-shtory/white';
const greyUrl = 'https://rimskie.com/catalog/rimskie-shtory/grey';
const productUrl = 'https://rimskie.com/products/11889-example';
const secondProductUrl = 'https://rimskie.com/products/11900-example';
const imageUrl = 'https://rimskie.com/media/output/first.webp';
const firstImageBytes = Buffer.from([
    0x52, 0x49, 0x46, 0x46, 0x16, 0x00, 0x00, 0x00,
    0x57, 0x45, 0x42, 0x50, 0x56, 0x50, 0x38, 0x58,
    0x0a, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
    0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
]);
const execFileAsync = promisify(execFile);

function categoryHtml(products) {
    return `<!doctype html><html><body>${products.map(({ externalId, url }) => `
        <article class="product" data-id="${externalId}">
            <a class="product-link" href="${url}">
                <span class="product-title">Product ${externalId}</span>
            </a>
            <meta itemprop="lowPrice" content="2708">
        </article>
    `).join('')}</body></html>`;
}

function productHtml(externalId, firstImageUrl = imageUrl) {
    return `<!doctype html><html><body>
        <main data-product-id="${externalId}">
            <h1>Product ${externalId}</h1>
            <div class="product-gallery"><img src="${firstImageUrl}"></div>
        </main>
    </body></html>`;
}

function source(sourceSlug, sourceUrl) {
    return {
        label: sourceSlug,
        sourceSlug,
        sourceUrl,
        enabled: true,
        sortOrder: sourceSlug === 'white' ? 1 : 2,
        nextPageUrl: sourceUrl,
        pendingProducts: [],
        completed: false,
        pages: 0,
    };
}

function initialState(sources) {
    return {
        status: 'ready',
        requestCount: 0,
        completedProductIds: [],
        sources,
    };
}

async function createStore(t, state) {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-collector-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });
    await store.checkpoint(state);

    return store;
}

function createPolicy(store) {
    return new RequestPolicy({
        htmlDelayMs: [0, 0],
        imageDelayMs: [0, 0],
        challengeDelayMs: [0, 0],
        backoffMs: [0, 0, 0],
        sleep: async () => {},
        random: () => 0,
        onEvent: (event) => store.appendEvent(event),
    });
}

class FakeTransport {
    constructor(responses) {
        this.responses = responses;
        this.calls = [];
    }

    async getHtml(url) {
        this.calls.push(['html', url]);
        const response = this.responses.get(url);
        if (response instanceof Error) throw response;
        if (typeof response !== 'string') throw new Error(`Missing fake HTML for ${url}`);

        return response;
    }

    async downloadFirstImage(url, destination) {
        this.calls.push(['image', url]);
        const image = firstImageBytes;
        await writeFile(destination, image);

        return image;
    }

    async close() {}
}

async function readEvents(store) {
    try {
        const text = await readFile(store.eventsPath, 'utf8');
        return text.trim().split('\n').filter(Boolean).map(JSON.parse);
    } catch (error) {
        if (error.code === 'ENOENT') return [];
        throw error;
    }
}

test('category page is checkpointed before product HTML and first-image work', async (t) => {
    const store = await createStore(t, initialState([source('white', whiteUrl)]));
    const fakeTransport = new FakeTransport(new Map([
        [whiteUrl, categoryHtml([{ externalId: '11889', url: productUrl }])],
        [productUrl, productHtml('11889')],
    ]));
    const checkpoints = [];
    const checkpoint = store.checkpoint.bind(store);
    store.checkpoint = async (state) => {
        checkpoints.push(structuredClone(state));
        await checkpoint(state);
    };
    const originalGetHtml = fakeTransport.getHtml.bind(fakeTransport);
    fakeTransport.getHtml = async (url) => {
        if (url === whiteUrl) {
            assert.equal((await store.readState()).requestPolicy.requestTimes.length, 1);
        }
        if (url === productUrl) {
            assert.equal(checkpoints.at(-1).sources[0].pendingProducts[0].externalId, '11889');
            assert.equal((await store.readMemberships()).length, 1);
        }
        return originalGetHtml(url);
    };

    const snapshot = await new Collector().run({
        store,
        transport: fakeTransport,
        policy: createPolicy(store),
    });

    assert.deepEqual(fakeTransport.calls, [
        ['html', whiteUrl],
        ['html', productUrl],
        ['image', imageUrl],
    ]);
    assert.equal(snapshot.uniqueProducts, 1);
    assert.equal(snapshot.status, 'completed');
    assert.deepEqual(await readFile(join(store.imagesDir, '11889.webp')), firstImageBytes);
    assert.equal(fakeTransport.calls.filter(([kind]) => kind === 'image').length, 1);
    const savedSource = JSON.parse(await readFile(join(store.sourcesDir, 'white.json'), 'utf8'));
    assert.equal(savedSource.source_url, whiteUrl);
    assert.equal(savedSource.target_slug, 'white');
});

test('duplicate products across two sources fetch product HTML and image once', async (t) => {
    const store = await createStore(t, initialState([
        source('white', whiteUrl),
        source('grey', greyUrl),
    ]));
    const duplicateCategory = categoryHtml([{ externalId: '11889', url: productUrl }]);
    const fakeTransport = new FakeTransport(new Map([
        [whiteUrl, duplicateCategory],
        [greyUrl, duplicateCategory],
        [productUrl, productHtml('11889')],
    ]));

    const snapshot = await new Collector().run({
        store,
        transport: fakeTransport,
        policy: createPolicy(store),
    });

    assert.equal(fakeTransport.calls.filter(([kind, url]) => kind === 'html' && url === productUrl).length, 1);
    assert.equal(fakeTransport.calls.filter(([kind]) => kind === 'image').length, 1);
    assert.equal((await store.readMemberships()).length, 2);
    assert.equal(snapshot.uniqueProducts, 1);
    assert.equal(snapshot.status, 'completed');
});

test('completed resume performs zero transport calls', async (t) => {
    const completed = initialState([source('white', whiteUrl)]);
    completed.status = 'completed';
    completed.sources[0].completed = true;
    completed.sources[0].nextPageUrl = null;
    const store = await createStore(t, completed);
    const fakeTransport = new FakeTransport(new Map());

    const snapshot = await new Collector().run({
        store,
        transport: fakeTransport,
        policy: createPolicy(store),
    });

    assert.deepEqual(fakeTransport.calls, []);
    assert.equal(snapshot.status, 'completed');
});

test('maxRequests=3 checkpoints a clean limited state before a fourth request', async (t) => {
    const store = await createStore(t, initialState([source('white', whiteUrl)]));
    const fakeTransport = new FakeTransport(new Map([
        [whiteUrl, categoryHtml([
            { externalId: '11889', url: productUrl },
            { externalId: '11900', url: secondProductUrl },
        ])],
        [productUrl, productHtml('11889')],
        [secondProductUrl, productHtml('11900')],
    ]));

    const snapshot = await new Collector().run({
        store,
        transport: fakeTransport,
        policy: createPolicy(store),
        maxRequests: 3,
    });

    assert.deepEqual(fakeTransport.calls, [
        ['html', whiteUrl],
        ['html', productUrl],
        ['image', imageUrl],
    ]);
    assert.equal(snapshot.requestCount, 3);
    assert.equal(snapshot.status, 'limited');
    assert.equal((await store.readState()).status, 'limited');
});

test('maxRequests is a total durable cap across collector restarts', async (t) => {
    const state = initialState([source('white', whiteUrl)]);
    state.requestCount = 3;
    const store = await createStore(t, state);
    const fakeTransport = new FakeTransport(new Map());

    const snapshot = await new Collector().run({
        store, transport: fakeTransport, policy: createPolicy(store), maxRequests: 3,
    });

    assert.equal(snapshot.status, 'limited');
    assert.equal(snapshot.pauseReason, 'max-requests');
    assert.deepEqual(fakeTransport.calls, []);
});

test('maxProducts is a total durable cap across collector restarts', async (t) => {
    const queuedSource = source('white', whiteUrl);
    queuedSource.nextPageUrl = null;
    queuedSource.pendingProducts = [{ externalId: '11900', sourceUrl: secondProductUrl }];
    const state = initialState([queuedSource]);
    state.completedProductIds = ['11889'];
    const store = await createStore(t, state);
    const fakeTransport = new FakeTransport(new Map([
        [secondProductUrl, productHtml('11900')],
    ]));

    const snapshot = await new Collector().run({
        store, transport: fakeTransport, policy: createPolicy(store), maxProducts: 1,
    });

    assert.equal(snapshot.status, 'limited');
    assert.equal(snapshot.pauseReason, 'max-products');
    assert.deepEqual(fakeTransport.calls, []);
});

test('resume after durable product HTML stage skips a second product request', async (t) => {
    const store = await createStore(t, initialState([source('white', whiteUrl)]));
    const firstTransport = new FakeTransport(new Map([
        [whiteUrl, categoryHtml([{ externalId: '11889', url: productUrl }])],
        [productUrl, productHtml('11889')],
    ]));
    const checkpoint = store.checkpoint.bind(store);
    let injectedCrash = false;
    store.checkpoint = async (state) => {
        await checkpoint(state);
        if (!injectedCrash && state.sources[0].pendingProducts[0]?.stage === 'html-complete') {
            injectedCrash = true;
            throw new Error('simulated crash after product HTML checkpoint');
        }
    };

    const crashed = await new Collector().run({
        store,
        transport: firstTransport,
        policy: createPolicy(store),
    });
    const resumedTransport = new FakeTransport(new Map());
    const resumed = await new Collector().run({
        store,
        transport: resumedTransport,
        policy: createPolicy(store),
        acknowledgeError: true,
    });

    assert.equal(crashed.status, 'error');
    assert.deepEqual(firstTransport.calls, [['html', whiteUrl], ['html', productUrl]]);
    assert.deepEqual(resumedTransport.calls, [['image', imageUrl]]);
    assert.equal(resumed.status, 'completed');
});

test('resume recovers a saved product draft when the process dies before its state checkpoint', async (t) => {
    const store = await createStore(t, initialState([source('white', whiteUrl)]));
    const firstTransport = new FakeTransport(new Map([
        [whiteUrl, categoryHtml([{ externalId: '11889', url: productUrl }])],
        [productUrl, productHtml('11889')],
    ]));
    const saveProduct = store.saveProduct.bind(store);
    const checkpoint = store.checkpoint.bind(store);
    let processDied = false;
    store.saveProduct = async (externalId, product) => {
        await saveProduct(externalId, product);
        if (product.collectionStage === 'html-complete') {
            processDied = true;
            throw new Error('simulated process death after durable product draft');
        }
    };
    store.checkpoint = async (state) => {
        if (processDied) throw new Error('dead process cannot write an error checkpoint');
        return checkpoint(state);
    };

    await assert.rejects(new Collector().run({
        store,
        transport: firstTransport,
        policy: createPolicy(store),
    }), /dead process/);
    store.saveProduct = saveProduct;
    store.checkpoint = checkpoint;

    const resumedTransport = new FakeTransport(new Map());
    const resumed = await new Collector().run({
        store,
        transport: resumedTransport,
        policy: createPolicy(store),
        acknowledgeError: true,
    });

    assert.deepEqual(firstTransport.calls, [['html', whiteUrl], ['html', productUrl]]);
    assert.deepEqual(resumedTransport.calls, [['image', imageUrl]]);
    assert.equal(resumed.status, 'completed');
});

test('resume after image bytes reach disk validates them and skips a second download', async (t) => {
    const store = await createStore(t, initialState([source('white', whiteUrl)]));
    const firstTransport = new FakeTransport(new Map([
        [whiteUrl, categoryHtml([{ externalId: '11889', url: productUrl }])],
        [productUrl, productHtml('11889')],
    ]));
    const download = firstTransport.downloadFirstImage.bind(firstTransport);
    firstTransport.downloadFirstImage = async (...args) => {
        await download(...args);
        throw Object.assign(new Error('simulated crash after atomic image rename'), { kind: 'process_crash' });
    };

    const crashed = await new Collector().run({
        store,
        transport: firstTransport,
        policy: createPolicy(store),
    });
    const resumedTransport = new FakeTransport(new Map());
    const resumed = await new Collector().run({
        store,
        transport: resumedTransport,
        policy: createPolicy(store),
        acknowledgeError: true,
    });

    assert.equal(crashed.status, 'error');
    assert.equal(firstTransport.calls.filter(([kind]) => kind === 'image').length, 1);
    assert.deepEqual(resumedTransport.calls, []);
    assert.equal(resumed.status, 'completed');
    assert.deepEqual(await readFile(join(store.imagesDir, '11889.webp')), firstImageBytes);
});

test('resume re-downloads an invalid file despite an image-complete checkpoint', async (t) => {
    const store = await createStore(t, initialState([source('white', whiteUrl)]));
    const firstTransport = new FakeTransport(new Map([
        [whiteUrl, categoryHtml([{ externalId: '11889', url: productUrl }])],
        [productUrl, productHtml('11889')],
    ]));
    const limited = await new Collector().run({
        store,
        transport: firstTransport,
        policy: createPolicy(store),
        maxRequests: 2,
    });
    const staged = await store.readState();
    staged.sources[0].pendingProducts[0].stage = 'image-complete';
    await store.checkpoint(staged);
    await writeFile(join(store.imagesDir, '11889.webp'), Buffer.from('corrupt image'));

    const resumedTransport = new FakeTransport(new Map());
    const resumed = await new Collector().run({
        store, transport: resumedTransport, policy: createPolicy(store),
    });

    assert.equal(limited.status, 'limited');
    assert.deepEqual(resumedTransport.calls, [['image', imageUrl]]);
    assert.equal(resumed.status, 'completed');
    assert.deepEqual(await readFile(join(store.imagesDir, '11889.webp')), firstImageBytes);
});

test('pause requested during delay prevents request reservation and transport access', async (t) => {
    const store = await createStore(t, initialState([source('white', whiteUrl)]));
    const fakeTransport = new FakeTransport(new Map([[whiteUrl, categoryHtml([])]]));
    const flags = { pause: false, stop: false };
    const policy = new RequestPolicy({
        htmlDelayMs: [1, 1],
        imageDelayMs: [1, 1],
        sleep: async () => { flags.pause = true; },
        onEvent: (event) => store.appendEvent(event),
    });

    const snapshot = await new Collector().run({
        store,
        transport: fakeTransport,
        policy,
        control: { read: async () => ({ ...flags }) },
    });

    assert.equal(snapshot.status, 'paused');
    assert.deepEqual(fakeTransport.calls, []);
    assert.deepEqual(snapshot.requestPolicy.requestTimes, []);
});

test('pause arriving during request reservation checkpoint prevents transport access', async (t) => {
    const store = await createStore(t, initialState([source('white', whiteUrl)]));
    const fakeTransport = new FakeTransport(new Map([[whiteUrl, categoryHtml([])]]));
    const flags = { pause: false, stop: false };
    const checkpoint = store.checkpoint.bind(store);
    store.checkpoint = async (state) => {
        await checkpoint(state);
        if (state.requestPolicy?.requestTimes?.length === 1) flags.pause = true;
    };

    const snapshot = await new Collector().run({
        store,
        transport: fakeTransport,
        policy: createPolicy(store),
        control: { read: async () => ({ ...flags }) },
    });

    assert.equal(snapshot.status, 'paused');
    assert.deepEqual(fakeTransport.calls, []);
    assert.equal(snapshot.requestPolicy.requestTimes.length, 1);
});

test('stop requested during delay prevents request reservation and transport access', async (t) => {
    const store = await createStore(t, initialState([source('white', whiteUrl)]));
    const fakeTransport = new FakeTransport(new Map([[whiteUrl, categoryHtml([])]]));
    const flags = { pause: false, stop: false };
    const policy = new RequestPolicy({
        htmlDelayMs: [1, 1],
        imageDelayMs: [1, 1],
        sleep: async () => { flags.stop = true; },
        onEvent: (event) => store.appendEvent(event),
    });

    const snapshot = await new Collector().run({
        store,
        transport: fakeTransport,
        policy,
        control: { read: async () => ({ ...flags }) },
    });

    assert.equal(snapshot.status, 'stopped');
    assert.deepEqual(fakeTransport.calls, []);
    assert.deepEqual(snapshot.requestPolicy.requestTimes, []);
});

test('exhausted rolling budget pauses cleanly without transport access', async (t) => {
    const store = await createStore(t, initialState([source('white', whiteUrl)]));
    const fakeTransport = new FakeTransport(new Map());
    const policy = new RequestPolicy({
        htmlDelayMs: [0, 0],
        imageDelayMs: [0, 0],
        hourlyLimit: 0,
        sleep: async () => {},
        onEvent: (event) => store.appendEvent(event),
    });

    const snapshot = await new Collector().run({ store, transport: fakeTransport, policy });

    assert.equal(snapshot.status, 'paused');
    assert.equal(snapshot.pauseReason, 'hourly-budget');
    assert.deepEqual(fakeTransport.calls, []);
    assert.equal((await readEvents(store)).some((event) => event.type === 'pause'
        && event.reason === 'hourly-budget'), true);
});

test('challenge writes an event and checkpoints the run as paused', async (t) => {
    const store = await createStore(t, initialState([source('white', whiteUrl)]));
    const challenge = Object.assign(new Error('BotHunt challenge requires a visible authorized click'), {
        kind: 'challenge',
    });
    const fakeTransport = new FakeTransport(new Map([[whiteUrl, challenge]]));

    const snapshot = await new Collector().run({
        store,
        transport: fakeTransport,
        policy: createPolicy(store),
    });

    const events = await readEvents(store);
    assert.equal(snapshot.status, 'paused');
    assert.equal(snapshot.pauseReason, 'challenge');
    assert.equal(fakeTransport.calls.length, 1);
    assert.equal(events.some((event) => event.type === 'challenge' && event.url === whiteUrl), true);
    assert.equal(events.some((event) => event.type === 'pause' && event.reason === 'challenge'), true);
});

test('simple challenge button is clicked once and its request is durably counted', async (t) => {
    const store = await createStore(t, initialState([source('white', whiteUrl)]));
    const calls = [];
    const transport = {
        async getHtml(url) {
            calls.push(['html', url]);
            throw Object.assign(new Error('visible BotHunt retry page'), {
                kind: 'challenge', url, pageKind: 'category',
            });
        },
        async retrySimpleChallenge(url, { kind }) {
            calls.push(['challenge', url, kind]);
            const durable = await store.readState();
            assert.deepEqual(durable.challengeRetryUrls, [whiteUrl]);
            assert.equal(durable.requestCount, 2);
            return categoryHtml([]);
        },
    };

    const snapshot = await new Collector().run({
        store, transport, policy: createPolicy(store),
    });

    assert.equal(snapshot.status, 'completed');
    assert.equal(snapshot.requestCount, 2);
    assert.deepEqual(snapshot.challengeRetryUrls, [whiteUrl]);
    assert.deepEqual(calls, [
        ['html', whiteUrl],
        ['challenge', whiteUrl, 'category'],
    ]);
});

test('a failed challenge click is never repeated for the same URL', async (t) => {
    const state = initialState([source('white', whiteUrl)]);
    state.challengeRetryUrls = [whiteUrl];
    const store = await createStore(t, state);
    const calls = [];
    const transport = {
        async getHtml(url) {
            calls.push(['html', url]);
            throw Object.assign(new Error('challenge returned again'), {
                kind: 'challenge', url, pageKind: 'category',
            });
        },
        async retrySimpleChallenge() {
            calls.push(['unexpected-challenge-click']);
            return categoryHtml([]);
        },
    };

    const snapshot = await new Collector().run({
        store, transport, policy: createPolicy(store), acknowledgeChallenge: true,
    });

    assert.equal(snapshot.status, 'paused');
    assert.equal(snapshot.pauseReason, 'challenge');
    assert.deepEqual(calls, [['html', whiteUrl]]);
});

test('an image challenge pauses without clicking or marking the image complete', async (t) => {
    const store = await createStore(t, initialState([source('white', whiteUrl)]));
    const transport = new FakeTransport(new Map([
        [whiteUrl, categoryHtml([{ externalId: '11889', url: productUrl }])],
        [productUrl, productHtml('11889')],
    ]));
    let retryClicks = 0;
    transport.downloadFirstImage = async (url) => {
        transport.calls.push(['image', url]);
        throw Object.assign(new Error('image fetch returned challenge HTML'), {
            kind: 'challenge',
            challengeDocumentUrl: productUrl,
            pageKind: 'product',
        });
    };
    transport.retrySimpleChallenge = async () => {
        retryClicks += 1;
        return productHtml('11889');
    };

    const snapshot = await new Collector().run({
        store, transport, policy: createPolicy(store),
    });

    assert.equal(snapshot.status, 'paused');
    assert.equal(snapshot.pauseReason, 'challenge');
    assert.equal(retryClicks, 0);
    assert.deepEqual(snapshot.completedProductIds, []);
    assert.equal(snapshot.sources[0].pendingProducts[0].stage, 'html-complete');
    await assert.rejects(readFile(join(store.imagesDir, '11889.webp')), /ENOENT/);
});

test('first transient or protection failure pauses without an automatic retry', async (t) => {
    for (const failureKind of ['network', 'timeout', 'http_403', 'http_429']) {
        await t.test(failureKind, async (subtest) => {
            const store = await createStore(subtest, initialState([source('white', whiteUrl)]));
            const fakeTransport = new FakeTransport(new Map());
            fakeTransport.getHtml = async (url) => {
                fakeTransport.calls.push(['html', url]);
                throw Object.assign(new Error(`typed ${failureKind} failure`), { kind: failureKind });
            };
            const sleeps = [];
            const policy = new RequestPolicy({
                htmlDelayMs: [0, 0],
                imageDelayMs: [0, 0],
                backoffMs: [120_000, 300_000, 900_000],
                sleep: async (milliseconds) => sleeps.push(milliseconds),
                onEvent: (event) => store.appendEvent(event),
            });

            const snapshot = await new Collector().run({ store, transport: fakeTransport, policy });

            assert.equal(snapshot.status, 'paused');
            assert.equal(snapshot.pauseReason, failureKind);
            assert.equal(fakeTransport.calls.length, 1);
            assert.deepEqual(sleeps, [0]);
        });
    }
});

test('restart during the third failure backoff cannot make a fourth automatic request', async (t) => {
    const state = initialState([source('white', whiteUrl)]);
    state.status = 'running';
    state.requestPolicy = {
        requestTimes: [],
        consecutiveFailures: 3,
        pauseRequired: true,
        lastFailureKind: 'network',
    };
    const store = await createStore(t, state);
    const fakeTransport = new FakeTransport(new Map([[whiteUrl, categoryHtml([])]]));

    const recovered = await new Collector().run({
        store,
        transport: fakeTransport,
        policy: createPolicy(store),
    });

    assert.equal(recovered.status, 'paused');
    assert.equal(recovered.pauseReason, 'network');
    assert.deepEqual(fakeTransport.calls, []);

    const resumed = await new Collector().run({
        store,
        transport: fakeTransport,
        policy: createPolicy(store),
        acknowledgeFailurePause: true,
    });
    assert.equal(resumed.status, 'completed');
    assert.deepEqual(fakeTransport.calls, [['html', whiteUrl]]);
});

test('protective pause is durable before its error event and blocks an implicit restart', async (t) => {
    const state = initialState([source('white', whiteUrl)]);
    const store = await createStore(t, state);
    const firstTransport = new FakeTransport(new Map());
    firstTransport.getHtml = async (url) => {
        firstTransport.calls.push(['html', url]);
        throw Object.assign(new Error('third network failure'), { kind: 'network' });
    };
    const appendEvent = store.appendEvent.bind(store);
    let eventWriteCrashed = false;
    store.appendEvent = async (event) => {
        if (!eventWriteCrashed && event.type === 'error' && event.kind === 'network') {
            eventWriteCrashed = true;
            throw new Error('simulated process death before error event');
        }
        return appendEvent(event);
    };

    const crashed = await new Collector().run({
        store,
        transport: firstTransport,
        policy: createPolicy(store),
    });
    assert.equal(crashed.status, 'paused');
    assert.equal((await store.readState()).pauseReason, 'network');
    assert.equal(firstTransport.calls.length, 1);

    const resumedTransport = new FakeTransport(new Map([[whiteUrl, categoryHtml([])]]));
    const recovered = await new Collector().run({
        store,
        transport: resumedTransport,
        policy: createPolicy(store),
    });
    assert.equal(recovered.status, 'paused');
    assert.deepEqual(resumedTransport.calls, []);
});

test('category parser failure appends a diagnostic event and checkpoints error state', async (t) => {
    const store = await createStore(t, initialState([source('white', whiteUrl)]));
    const fakeTransport = new FakeTransport(new Map([[
        whiteUrl,
        categoryHtml([{ externalId: '11889', url: 'http://[' }]),
    ]]));

    const snapshot = await new Collector().run({
        store, transport: fakeTransport, policy: createPolicy(store),
    });

    assert.equal(snapshot.status, 'error');
    assert.equal((await store.readState()).status, 'error');
    assert.equal((await readEvents(store)).some((event) => event.type === 'error'
        && event.kind === 'collector' && /invalid url/i.test(event.message)), true);
});

test('storage failure appends a diagnostic event and checkpoints error state', async (t) => {
    const store = await createStore(t, initialState([source('white', whiteUrl)]));
    const fakeTransport = new FakeTransport(new Map([[whiteUrl, categoryHtml([])]]));
    const saveSource = store.saveSource.bind(store);
    let failOnce = true;
    store.saveSource = async (...args) => {
        if (failOnce) {
            failOnce = false;
            throw new Error('simulated source persistence failure');
        }
        return saveSource(...args);
    };

    const snapshot = await new Collector().run({
        store, transport: fakeTransport, policy: createPolicy(store),
    });

    assert.equal(snapshot.status, 'error');
    assert.equal((await store.readState()).status, 'error');
    assert.equal((await readEvents(store)).some((event) => event.type === 'error'
        && /source persistence failure/i.test(event.message)), true);
});

function fakePlaywright({
    status = 200,
    html = '<html>ok</html>',
    image = firstImageBytes,
    contentType = 'image/webp',
    gotoError = null,
    contentError = null,
    imageError = null,
} = {}) {
    const launchCalls = [];
    const routes = [];
    const page = {
        goto: async () => {
            if (gotoError) throw gotoError;
            return { status: () => status };
        },
        content: async () => {
            if (contentError) throw contentError;
            return html;
        },
        waitForResponse: async (predicate) => {
            const response = {
                url: () => imageUrl,
                status: () => status,
                body: async () => image,
                headerValue: async (name) => name.toLowerCase() === 'content-type' ? contentType : null,
            };
            assert.equal(predicate(response), true);
            return response;
        },
        evaluate: async () => {
            if (imageError) throw imageError;
            return { ok: true };
        },
        url: () => productUrl,
    };
    const context = {
        pages: () => [page],
        route: async (pattern, handler) => routes.push([pattern, handler]),
        routeWebSocket: async () => {},
        setOffline: async () => {},
        close: async () => {},
    };
    const chromium = {
        launchPersistentContext: async (...args) => {
            launchCalls.push(args);
            return context;
        },
    };

    return { chromium, context, page, launchCalls, routes };
}

test('playwright transport opens the supplied persistent profile in headed sandboxed Chrome', async () => {
    const fake = fakePlaywright();
    const transport = await PlaywrightTransport.open({
        profileDir: 'C:\\runs\\run-001\\profile',
        headed: true,
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        chromium: fake.chromium,
    });

    assert.deepEqual(fake.launchCalls, [[
        'C:\\runs\\run-001\\profile',
        {
            headless: false,
            chromiumSandbox: true,
            executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            serviceWorkers: 'block',
            offline: true,
        },
    ]]);
    assert.equal(fake.routes.length, 1);
    await transport.close();
});

test('browser discovery checks Windows Chrome and Edge installations without downloads', async () => {
    const checked = [];
    const edgePath = 'D:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe';
    const executablePath = await findBrowserExecutable({
        platform: 'win32',
        environment: {
            ProgramFiles: 'D:\\Program Files',
            'ProgramFiles(x86)': 'D:\\Program Files (x86)',
            LOCALAPPDATA: 'D:\\Users\\operator\\AppData\\Local',
        },
        access: async (candidate) => {
            checked.push(candidate);
            if (candidate !== edgePath) throw Object.assign(new Error('missing'), { code: 'ENOENT' });
        },
    });

    assert.equal(executablePath, edgePath);
    assert.equal(checked.some((candidate) => candidate.endsWith('Google\\Chrome\\Application\\chrome.exe')), true);
});

test('playwright transport denies every uncounted resource after a challenge', async () => {
    const fake = fakePlaywright();
    const transport = await PlaywrightTransport.open({
        profileDir: 'profile',
        executablePath: 'chrome.exe',
        chromium: fake.chromium,
    });
    const handler = fake.routes[0][1];

    async function routeDecision(resourceType, url, method = 'GET') {
        let decision = null;
        await handler({
            request: () => ({
                method: () => method,
                resourceType: () => resourceType,
                url: () => url,
                redirectedFrom: () => null,
            }),
            abort: async () => { decision = 'abort'; },
            continue: async () => { decision = 'continue'; },
        });
        return decision;
    }

    assert.equal(await routeDecision('stylesheet', 'https://rimskie.com/app.css'), 'abort');
    assert.equal(await routeDecision('script', 'https://mc.yandex.ru/metrika/tag.js'), 'abort');
    assert.equal(await routeDecision('image', 'https://rimskie.com/other.webp'), 'abort');
    await transport.getHtml(whiteUrl);
    assert.equal(await routeDecision('stylesheet', 'https://rimskie.com/app.css'), 'abort');
    assert.equal(await routeDecision('script', 'https://mc.yandex.ru/metrika/tag.js'), 'abort');
    assert.equal(await routeDecision('image', 'https://rimskie.com/other.webp'), 'abort');
    assert.equal(await routeDecision('document', whiteUrl), 'abort');
    fake.page.goto = async () => ({ status: () => 403 });
    fake.page.content = async () => '<html><body>BotHunt verification</body></html>';
    await assert.rejects(transport.getHtml(whiteUrl), (error) => error.kind === 'challenge');
    assert.equal(await routeDecision('stylesheet', 'https://rimskie.com/challenge.css'), 'abort');
    assert.equal(await routeDecision('script', 'https://evil.example/challenge.js'), 'abort');
    assert.equal(await routeDecision('document', whiteUrl), 'abort');
    assert.equal(await routeDecision(
        'document', 'https://rimskie.com/catalog/rimskie-shtory/black',
    ), 'abort');
    assert.equal(await routeDecision('stylesheet', 'https://rimskie.com/challenge.css'), 'abort');
    assert.equal(await routeDecision('fetch', 'https://rimskie.com/api/catalog'), 'abort');
    assert.equal(await routeDecision('script', 'https://rimskie.com/challenge.js', 'POST'), 'abort');
    assert.equal(await routeDecision('websocket', 'https://rimskie.com/challenge/socket'), 'abort');
    await transport.close();
});

test('playwright transport reports typed 403 and visible challenge failures', async () => {
    const forbiddenFake = fakePlaywright({ status: 403 });
    const forbiddenTransport = await PlaywrightTransport.open({
        profileDir: 'profile',
        executablePath: 'chrome.exe',
        chromium: forbiddenFake.chromium,
    });
    await assert.rejects(
        forbiddenTransport.getHtml(whiteUrl),
        (error) => error instanceof DonorRequestError && error.kind === 'http_403',
    );

    const challengeFake = fakePlaywright({ html: '<html><body>BotHunt verification</body></html>' });
    const challengeTransport = await PlaywrightTransport.open({
        profileDir: 'profile',
        executablePath: 'chrome.exe',
        chromium: challengeFake.chromium,
    });
    await assert.rejects(
        challengeTransport.getHtml(whiteUrl),
        (error) => error instanceof DonorRequestError && error.kind === 'challenge',
    );

    const forbiddenChallengeFake = fakePlaywright({
        status: 403,
        html: '<html><body>BotHunt verification</body></html>',
    });
    const forbiddenChallengeTransport = await PlaywrightTransport.open({
        profileDir: 'profile',
        executablePath: 'chrome.exe',
        chromium: forbiddenChallengeFake.chromium,
    });
    await assert.rejects(
        forbiddenChallengeTransport.getHtml(whiteUrl),
        (error) => error instanceof DonorRequestError && error.kind === 'challenge',
    );
});

test('playwright transport clicks the exact simple challenge retry button once', async () => {
    let html = '<html><body>BotHunt verification<button>Попробовать снова</button></body></html>';
    let retryClicks = 0;
    const response = {
        status: () => 200,
        url: () => whiteUrl,
    };
    const locator = {
        count: async () => 1,
        isVisible: async () => true,
        isEnabled: async () => true,
        click: async () => {
            retryClicks += 1;
            html = categoryHtml([]);
        },
    };
    const page = {
        goto: async () => response,
        content: async () => html,
        evaluate: async () => {},
        // Chrome can expose its internal error-document URL while preserving the
        // donor challenge DOM that was returned for the counted navigation.
        url: () => 'chrome-error://chromewebdata/',
        getByRole: (role, options) => {
            assert.equal(role, 'button');
            assert.match('Попробовать снова', options.name);
            return locator;
        },
        locator: () => ({ count: async () => 0 }),
        waitForNavigation: async () => response,
    };
    const transport = new PlaywrightTransport({ close: async () => {} }, page);

    await assert.rejects(
        transport.getHtml(whiteUrl, { kind: 'category' }),
        (error) => error instanceof DonorRequestError && error.kind === 'challenge',
    );
    const result = await transport.retrySimpleChallenge(whiteUrl, { kind: 'category' });

    assert.equal(result, categoryHtml([]));
    assert.equal(retryClicks, 1);
});

test('playwright transport never clicks retry when full CAPTCHA controls are present', async () => {
    let retryClicks = 0;
    const page = {
        url: () => whiteUrl,
        locator: () => ({ count: async () => 1 }),
        getByRole: () => ({
            count: async () => 1,
            isVisible: async () => true,
            isEnabled: async () => true,
            click: async () => { retryClicks += 1; },
        }),
    };
    const transport = new PlaywrightTransport({ close: async () => {} }, page);

    await assert.rejects(
        transport.retrySimpleChallenge(whiteUrl, { kind: 'category' }),
        (error) => error instanceof DonorRequestError && error.kind === 'challenge'
            && /full CAPTCHA/i.test(error.message),
    );
    assert.equal(retryClicks, 0);
});

test('playwright transport pauses when a simple retry opens a full CAPTCHA', async () => {
    let retryClicks = 0;
    let fullCaptchaVisible = false;
    const response = { status: () => 200, url: () => whiteUrl };
    const page = {
        url: () => whiteUrl,
        locator: () => ({ count: async () => fullCaptchaVisible ? 1 : 0 }),
        getByRole: () => ({
            count: async () => 1,
            isVisible: async () => true,
            isEnabled: async () => true,
            click: async () => {
                retryClicks += 1;
                fullCaptchaVisible = true;
            },
        }),
        waitForNavigation: async () => response,
        content: async () => '<html><body><canvas></canvas></body></html>',
        evaluate: async () => {},
    };
    const transport = new PlaywrightTransport({ close: async () => {} }, page);

    await assert.rejects(
        transport.retrySimpleChallenge(whiteUrl, { kind: 'category' }),
        (error) => error instanceof DonorRequestError && error.kind === 'challenge'
            && /full CAPTCHA/i.test(error.message),
    );
    assert.equal(retryClicks, 1);
});

test('playwright transport reports typed timeout and network navigation failures', async () => {
    const timeoutFake = fakePlaywright({ gotoError: Object.assign(new Error('navigation timed out'), {
        name: 'TimeoutError',
    }) });
    const timeoutTransport = await PlaywrightTransport.open({
        profileDir: 'profile',
        executablePath: 'chrome.exe',
        chromium: timeoutFake.chromium,
    });
    await assert.rejects(
        timeoutTransport.getHtml(whiteUrl),
        (error) => error instanceof DonorRequestError && error.kind === 'timeout',
    );

    const networkFake = fakePlaywright({ gotoError: new Error('net::ERR_CONNECTION_RESET') });
    const networkTransport = await PlaywrightTransport.open({
        profileDir: 'profile',
        executablePath: 'chrome.exe',
        chromium: networkFake.chromium,
    });
    await assert.rejects(
        networkTransport.getHtml(whiteUrl),
        (error) => error instanceof DonorRequestError && error.kind === 'network',
    );

    const contentFake = fakePlaywright({ contentError: new Error('Target page closed while reading DOM') });
    const contentTransport = await PlaywrightTransport.open({
        profileDir: 'profile', executablePath: 'chrome.exe', chromium: contentFake.chromium,
    });
    await assert.rejects(
        contentTransport.getHtml(whiteUrl),
        (error) => error instanceof DonorRequestError && error.kind === 'network',
    );
});

test('playwright transport reports a typed network image failure without writing', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-transport-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const destination = join(rootDir, 'images', '11889.webp');
    const fake = fakePlaywright({ imageError: new Error('net::ERR_CONNECTION_RESET') });
    const transport = await PlaywrightTransport.open({
        profileDir: 'profile', executablePath: 'chrome.exe', chromium: fake.chromium,
    });

    await assert.rejects(
        transport.downloadFirstImage(imageUrl, destination),
        (error) => error instanceof DonorRequestError && error.kind === 'network',
    );
    await assert.rejects(readFile(destination), /ENOENT/);
});

test('playwright transport writes the exact first-image response to destination', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-transport-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const destination = join(rootDir, 'images', '11889.webp');
    const fake = fakePlaywright();
    const transport = await PlaywrightTransport.open({
        profileDir: 'profile',
        executablePath: 'chrome.exe',
        chromium: fake.chromium,
    });

    await transport.downloadFirstImage(imageUrl, destination);

    assert.deepEqual(await readFile(destination), firstImageBytes);
});

test('playwright transport rejects 403 HTML challenge bytes without writing an image', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-transport-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const destination = join(rootDir, 'images', '11889.webp');
    const fake = fakePlaywright({
        status: 403,
        image: Buffer.from('<html><body>Access verification required</body></html>'),
        contentType: 'text/html; charset=utf-8',
    });
    const transport = await PlaywrightTransport.open({
        profileDir: 'profile', executablePath: 'chrome.exe', chromium: fake.chromium,
    });

    await assert.rejects(
        transport.downloadFirstImage(imageUrl, destination),
        (error) => error instanceof DonorRequestError && error.kind === 'http_403',
    );
    await assert.rejects(readFile(destination), /ENOENT/);
});

test('playwright transport rejects non-2xx and invalid image signatures without writing', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-transport-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const destination = join(rootDir, 'images', '11889.webp');
    const redirectedFake = fakePlaywright({ status: 302 });
    const redirected = await PlaywrightTransport.open({
        profileDir: 'profile', executablePath: 'chrome.exe', chromium: redirectedFake.chromium,
    });
    await assert.rejects(
        redirected.downloadFirstImage(imageUrl, destination),
        (error) => error instanceof DonorRequestError && error.kind === 'http_error',
    );

    const invalidFake = fakePlaywright({ image: Buffer.from('not-an-image') });
    const invalid = await PlaywrightTransport.open({
        profileDir: 'profile', executablePath: 'chrome.exe', chromium: invalidFake.chromium,
    });
    await assert.rejects(
        invalid.downloadFirstImage(imageUrl, destination),
        (error) => error instanceof DonorRequestError && error.kind === 'invalid_image',
    );
    await assert.rejects(readFile(destination), /ENOENT/);
});

test('playwright transport rejects JPEG bytes instead of saving them under a WebP name', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-transport-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const destination = join(rootDir, 'images', '11889.webp');
    const jpegFake = fakePlaywright({
        image: Buffer.from([0xff, 0xd8, 0xff, 0xd9]),
        contentType: 'image/jpeg',
    });
    const transport = await PlaywrightTransport.open({
        profileDir: 'profile', executablePath: 'chrome.exe', chromium: jpegFake.chromium,
    });

    await assert.rejects(
        transport.downloadFirstImage(imageUrl, destination),
        (error) => error instanceof DonorRequestError && error.kind === 'invalid_image',
    );
    await assert.rejects(readFile(destination), /ENOENT/);
});

test('data root rejects Windows C drive before creating runtime files', async () => {
    let filesystemTouched = false;
    const forbiddenRoots = [
        'C:\\rimskie-imports',
        'c:/rimskie-imports',
        '\\\\?\\C:\\rimskie-imports',
        '//?/c:/rimskie-imports',
        '\\\\.\\C:\\rimskie-imports',
        '//./c:/rimskie-imports',
        '\\??\\C:\\rimskie-imports',
        '\\\\??\\c:\\rimskie-imports',
    ];

    for (const forbiddenRoot of forbiddenRoots) {
        await assert.rejects(resolveDataRoot(forbiddenRoot, {
            platform: 'win32',
            mkdir: async () => { filesystemTouched = true; },
            access: async () => { filesystemTouched = true; },
            realpath: async (value) => value,
        }), /must not use Windows drive C:/i);
    }
    await assert.rejects(resolveDataRoot('rimskie-imports', {
        platform: 'win32',
        mkdir: async () => { filesystemTouched = true; },
        access: async () => { filesystemTouched = true; },
        realpath: async (value) => value,
    }), /absolute/i);

    assert.equal(filesystemTouched, false);
});

test('existing run junction cannot escape the validated G data root', async () => {
    const dataRoot = 'G:\\stylish-house-data\\rimskie-imports';
    const runDir = `${dataRoot}\\run-001`;

    await assert.rejects(assertRunDirectorySafe(dataRoot, 'run-001', {
        platform: 'win32',
        realpath: async (value) => value === runDir ? 'C:\\escaped-run' : value,
    }), /run directory.*data root|drive C:/i);
});

test('existing run child junction cannot redirect image writes to C', async () => {
    const dataRoot = 'G:\\stylish-house-data\\rimskie-imports';
    const runDir = `${dataRoot}\\run-001`;
    const missing = () => Object.assign(new Error('missing'), { code: 'ENOENT' });

    await assert.rejects(assertRunDirectorySafe(dataRoot, 'run-001', {
        platform: 'win32',
        realpath: async (value) => {
            if (value === runDir) return value;
            if (value === `${runDir}\\images`) return 'C:\\escaped-images';
            throw missing();
        },
    }), /run child.*junction|drive C:/i);
});

test('data root rejects a non-C path whose nearest existing junction resolves to C', async () => {
    let filesystemTouched = false;
    const missing = () => Object.assign(new Error('missing'), { code: 'ENOENT' });

    await assert.rejects(resolveDataRoot('G:\\junction\\new\\root', {
        platform: 'win32',
        mkdir: async () => { filesystemTouched = true; },
        access: async () => { filesystemTouched = true; },
        realpath: async (value) => {
            if (value === 'G:\\junction\\new\\root' || value === 'G:\\junction\\new') throw missing();
            if (value === 'G:\\junction') return 'C:\\redirected';
            return value;
        },
    }), /must not use Windows drive C:/i);

    assert.equal(filesystemTouched, false);
});

test('data root validates an absolute writable non-C Windows directory', async () => {
    const operations = [];
    const dataRoot = await resolveDataRoot('G:\\stylish-house-data\\rimskie-imports', {
        platform: 'win32',
        mkdir: async (value) => operations.push(['mkdir', value]),
        access: async (value) => operations.push(['access', value]),
        realpath: async (value) => value,
    });

    assert.equal(dataRoot, 'G:\\stylish-house-data\\rimskie-imports');
    assert.deepEqual(operations, [
        ['mkdir', 'G:\\stylish-house-data\\rimskie-imports'],
        ['access', 'G:\\stylish-house-data\\rimskie-imports'],
    ]);
});

test('dry-run parser requires a data root and defaults to three requests and one product', () => {
    assert.deepEqual(parseArguments([
        'dry-run',
        '--run', 'run-001',
        '--data-root', 'G:\\stylish-house-data\\rimskie-imports',
    ]), {
        command: 'dry-run',
        runId: 'run-001',
        dataRoot: 'G:\\stylish-house-data\\rimskie-imports',
        chrome: undefined,
        json: false,
        maxRequests: 3,
        maxProducts: 1,
        maxRequestsExplicit: false,
        maxProductsExplicit: false,
    });
    assert.throws(() => parseArguments(['status', '--run', 'run-001']), /data-root/i);
});

test('control file atomically preserves pause and stop outside state checkpoints', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-control-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const control = new ControlFile(join(rootDir, 'control.json'));

    await control.write({ pause: true, stop: false });
    assert.deepEqual(await control.read(), { pause: true, stop: false });
    await control.update({ stop: true });

    assert.deepEqual(await control.read(), { pause: false, stop: true });
});

test('concurrent control updates preserve stop dominance and cannot clear it', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-control-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const control = new ControlFile(join(rootDir, 'control.json'));

    await control.write({ pause: false, stop: false });
    await Promise.all([
        control.update({ pause: true }),
        control.update({ stop: true }),
    ]);
    await control.update({ pause: false, stop: false });

    assert.deepEqual(await control.read(), { pause: false, stop: true });
});

test('run lock rejects stopped runs and a second live collector', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-control-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const control = new ControlFile(join(rootDir, 'control.json'), {
        isLiveProcess: (processId) => processId === 111,
        processFingerprintLookup: async (processId) => `process-start-${processId}`,
    });

    await control.write({
        pause: false,
        stop: false,
        ownerPid: 111,
        ownerProcessFingerprint: 'process-start-111',
    });
    await assert.rejects(control.claim(222), /collector is already running/i);
    await control.update({ stop: true });
    await assert.rejects(control.claim(222), /stopped run/i);
});

test('matching OS process fingerprint protects a live collector across callers', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-owner-identity-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const controlPath = join(rootDir, 'control.json');
    const processFingerprintLookup = async () => 'same-process-start';
    const first = new ControlFile(controlPath, {
        isLiveProcess: () => true,
        processIdentity: 'instance-first',
        processFingerprintLookup,
    });
    const reusedPid = new ControlFile(controlPath, {
        isLiveProcess: () => true,
        processIdentity: 'instance-second',
        processFingerprintLookup,
    });
    await first.claim(4242);

    await assert.rejects(reusedPid.claim(4242), /instance|already running/i);
});

test('live reused PID with a different OS fingerprint does not block a new owner', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-owner-reuse-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const controlPath = join(rootDir, 'control.json');
    let ownerFingerprint = 'original-process-start';
    const processFingerprintLookup = async (processId) => processId === 4242
        ? ownerFingerprint
        : 'test-runner-start';
    const first = new ControlFile(controlPath, {
        isLiveProcess: () => true,
        processIdentity: 'instance-first',
        processFingerprintLookup,
    });
    const replacement = new ControlFile(controlPath, {
        isLiveProcess: () => true,
        processIdentity: 'instance-second',
        processFingerprintLookup,
    });
    await first.claim(4242);
    ownerFingerprint = 'replacement-process-start';

    await replacement.claim(process.pid);

    const current = await replacement.read();
    assert.equal(current.ownerPid, process.pid);
    assert.equal(current.ownerProcessFingerprint, 'test-runner-start');
});

test('live reused PID with a different OS fingerprint is reclaimed from a stale lock', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-lock-reuse-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const controlPath = join(rootDir, 'control.json');
    const lockPath = `${controlPath}.lock`;
    await writeFile(lockPath, JSON.stringify({
        pid: 4242,
        token: 'reused-pid-token',
        processFingerprint: 'original-process-start',
    }), 'utf8');
    const control = new ControlFile(controlPath, {
        isLiveProcess: () => true,
        processIdentity: 'current-instance',
        processFingerprintLookup: async (processId) => processId === 4242
            ? 'replacement-process-start'
            : 'test-runner-start',
    });

    await control.update({ pause: true });

    assert.equal((await control.read()).pause, true);
    assert.equal(
        JSON.parse(await readFile(`${lockPath}.stale.reused-pid-token`, 'utf8')).processFingerprint,
        'original-process-start',
    );
});

test('live owner is protected when its OS process fingerprint cannot be verified', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-owner-unverified-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const control = new ControlFile(join(rootDir, 'control.json'), {
        isLiveProcess: () => true,
        processFingerprintLookup: async (processId) => processId === process.pid
            ? 'test-runner-start'
            : null,
    });
    await control.write({
        pause: false,
        stop: false,
        ownerPid: 4242,
        ownerIdentity: 'unknown-owner',
        ownerProcessFingerprint: 'recorded-process-start',
    });

    await assert.rejects(
        control.claim(process.pid),
        /cannot verify collector process instance.*refusing ownership change/i,
    );
    assert.equal((await control.read()).ownerPid, 4242);
});

test('control exclusive operation blocks collector claim through manifest export', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-export-lock-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const controlPath = join(rootDir, 'control.json');
    const exporter = new ControlFile(controlPath, {
        isLiveProcess: () => false,
        processIdentity: 'exporter',
        processFingerprintLookup: async (processId) => `process-start-${processId}`,
    });
    const collector = new ControlFile(controlPath, {
        isLiveProcess: () => false,
        processIdentity: 'collector',
        processFingerprintLookup: async (processId) => `process-start-${processId}`,
    });
    const order = [];
    let releaseExport;
    let notifyExportStarted;
    const exportGate = new Promise((resolve) => { releaseExport = resolve; });
    const exportStarted = new Promise((resolve) => { notifyExportStarted = resolve; });
    const exporting = exporter.exclusive(async () => {
        order.push('export-start');
        notifyExportStarted();
        await exportGate;
        order.push('export-end');
    });
    await exportStarted;
    const claiming = collector.claim(4242).then(() => order.push('claim'));
    await new Promise((resolve) => setImmediate(resolve));

    assert.deepEqual(order, ['export-start']);
    releaseExport();
    await Promise.all([exporting, claiming]);
    assert.deepEqual(order, ['export-start', 'export-end', 'claim']);
});

test('control update recovers a lock left by a dead process', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-control-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const controlPath = join(rootDir, 'control.json');
    const lockPath = `${controlPath}.lock`;
    const control = new ControlFile(controlPath, {
        isLiveProcess: (processId) => processId === process.pid,
    });
    await writeFile(lockPath, JSON.stringify({
        pid: 999999,
        token: 'dead-token',
        processFingerprint: 'dead-process-start',
    }), 'utf8');

    await control.update({ pause: true });

    assert.deepEqual(await control.read(), { pause: true, stop: false });
    assert.deepEqual(JSON.parse(await readFile(`${lockPath}.stale.dead-token`, 'utf8')), {
        pid: 999999,
        token: 'dead-token',
        processFingerprint: 'dead-process-start',
    });
});

test('concurrent stale-lock reclaimers cannot remove a newly acquired control lock', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-control-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const controlPath = join(rootDir, 'control.json');
    const lockPath = `${controlPath}.lock`;
    const processCheck = (processId) => processId === process.pid;
    const processFingerprintLookup = async (processId) => `process-start-${processId}`;
    const first = new ControlFile(controlPath, {
        isLiveProcess: processCheck,
        processFingerprintLookup,
    });
    const second = new ControlFile(controlPath, {
        isLiveProcess: processCheck,
        processFingerprintLookup,
    });
    await writeFile(lockPath, JSON.stringify({
        pid: 999999, token: 'dead-token', processFingerprint: 'dead-process-start',
    }), 'utf8');

    await Promise.all([
        first.update({ pause: true }),
        second.update({ stop: true }),
    ]);

    assert.deepEqual(await first.read(), { pause: false, stop: true });
    assert.equal(JSON.parse(await readFile(`${lockPath}.stale.dead-token`, 'utf8')).token, 'dead-token');
});

test('incomplete legacy control lock fails closed with a recovery instruction', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-control-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const controlPath = join(rootDir, 'control.json');
    const control = new ControlFile(controlPath, {
        processFingerprintLookup: async () => 'test-runner-start',
    });
    await writeFile(`${controlPath}.lock`, '', 'utf8');

    await assert.rejects(
        control.update({ pause: true }),
        /incomplete.*lock.*remove manually/i,
    );
});

test('status pause stop and export CLI commands never open the donor transport', async (t) => {
    const dataRoot = 'G:\\stylish-house-data\\rimskie-imports';
    const runId = `test-cli-no-donor-${process.pid}-${Date.now()}`;
    const runDir = join(dataRoot, runId);
    t.after(() => rm(runDir, { recursive: true, force: true }));
    const cliPath = fileURLToPath(new URL('../../scripts/rimskie-import/cli.mjs', import.meta.url));
    const store = await RunStore.open({ rootDir: dataRoot, runId });
    const configuredSource = source('white', whiteUrl);
    const completedSource = {
        ...configuredSource,
        nextPageUrl: null,
        completed: true,
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
        label: 'white', source_url: whiteUrl, target_slug: 'white', enabled: true,
        sort_order: 1, status: 'completed', pages: 1, next_page_url: null,
    });
    await store.saveProduct('11889', {
        externalId: '11889', sourceUrl: productUrl, sourceTitle: 'Product 11889',
        sourceDescription: 'Private donor description', sourcePrice: '2708.00',
        firstImageUrl: imageUrl, firstImagePath: 'images/11889.webp', attributes: {},
    });
    await store.appendMembership({ sourceSlug: 'white', externalId: '11889' });
    await writeFile(join(store.imagesDir, '11889.webp'), firstImageBytes);
    const common = [
        '--run', runId,
        '--data-root', dataRoot,
        '--chrome', 'Z:\\missing\\chrome.exe',
    ];

    const status = await execFileAsync(process.execPath, [cliPath, 'status', ...common, '--json']);
    const pause = await execFileAsync(process.execPath, [cliPath, 'pause', ...common]);
    const stop = await execFileAsync(process.execPath, [cliPath, 'stop', ...common]);
    const exported = await execFileAsync(process.execPath, [cliPath, 'export', ...common]);

    assert.equal(JSON.parse(status.stdout).control.stop, false);
    assert.match(pause.stdout, /Pause requested/);
    assert.match(stop.stdout, /Stop requested/);
    assert.match(exported.stdout, /export\.json/);
});

test('failed Chrome launch releases the claimed run owner', async (t) => {
    const dataRoot = 'G:\\stylish-house-data\\rimskie-imports';
    const runId = `test-cli-launch-failure-${process.pid}-${Date.now()}`;
    const runDir = join(dataRoot, runId);
    t.after(() => rm(runDir, { recursive: true, force: true }));
    const cliPath = fileURLToPath(new URL('../../scripts/rimskie-import/cli.mjs', import.meta.url));

    await assert.rejects(execFileAsync(process.execPath, [
        cliPath,
        'dry-run',
        '--run', runId,
        '--data-root', dataRoot,
        '--chrome', 'Z:\\missing\\chrome.exe',
    ]));

    const control = new ControlFile(join(runDir, 'control.json'));
    assert.equal((await control.read()).ownerPid, null);
});
