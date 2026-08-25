import assert from 'node:assert/strict';
import { mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';

import {
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
import { RunStore } from '../../scripts/rimskie-import/lib/run-store.mjs';

const whiteUrl = 'https://rimskie.com/catalog/rimskie-shtory/white';
const greyUrl = 'https://rimskie.com/catalog/rimskie-shtory/grey';
const productUrl = 'https://rimskie.com/products/11889-example';
const secondProductUrl = 'https://rimskie.com/products/11900-example';
const imageUrl = 'https://rimskie.com/media/output/first.webp';

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
        const image = Buffer.from('first-image');
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
    assert.deepEqual(await readFile(join(store.imagesDir, '11889.webp')), Buffer.from('first-image'));
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

function fakePlaywright({ status = 200, html = '<html>ok</html>', image = Buffer.from('first-image') } = {}) {
    const launchCalls = [];
    const routes = [];
    const page = {
        goto: async () => ({ status: () => status }),
        content: async () => html,
        waitForResponse: async (predicate) => {
            const response = {
                url: () => imageUrl,
                status: () => status,
                body: async () => image,
            };
            assert.equal(predicate(response), true);
            return response;
        },
        evaluate: async () => ({ ok: true }),
    };
    const context = {
        pages: () => [page],
        route: async (pattern, handler) => routes.push([pattern, handler]),
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

test('playwright transport opens the supplied persistent profile in headed Chrome', async () => {
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
            executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
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

test('playwright transport aborts heavy resources, analytics, and unrelated images', async () => {
    const fake = fakePlaywright();
    const transport = await PlaywrightTransport.open({
        profileDir: 'profile',
        executablePath: 'chrome.exe',
        chromium: fake.chromium,
    });
    const handler = fake.routes[0][1];

    async function routeDecision(resourceType, url) {
        let decision = null;
        await handler({
            request: () => ({ resourceType: () => resourceType, url: () => url }),
            abort: async () => { decision = 'abort'; },
            continue: async () => { decision = 'continue'; },
        });
        return decision;
    }

    assert.equal(await routeDecision('stylesheet', 'https://rimskie.com/app.css'), 'abort');
    assert.equal(await routeDecision('script', 'https://mc.yandex.ru/metrika/tag.js'), 'abort');
    assert.equal(await routeDecision('image', 'https://rimskie.com/other.webp'), 'abort');
    assert.equal(await routeDecision('document', whiteUrl), 'continue');
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

    assert.deepEqual(await readFile(destination), Buffer.from('first-image'));
});

test('data root rejects Windows C drive before creating runtime files', async () => {
    let filesystemTouched = false;

    await assert.rejects(resolveDataRoot('C:\\rimskie-imports', {
        platform: 'win32',
        mkdir: async () => { filesystemTouched = true; },
        access: async () => { filesystemTouched = true; },
        realpath: async (value) => value,
    }), /must not use Windows drive C:/i);
    await assert.rejects(resolveDataRoot('\\\\?\\C:\\rimskie-imports', {
        platform: 'win32',
        mkdir: async () => { filesystemTouched = true; },
        access: async () => { filesystemTouched = true; },
        realpath: async (value) => value,
    }), /must not use Windows drive C:/i);
    await assert.rejects(resolveDataRoot('rimskie-imports', {
        platform: 'win32',
        mkdir: async () => { filesystemTouched = true; },
        access: async () => { filesystemTouched = true; },
        realpath: async (value) => value,
    }), /absolute/i);

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

    assert.deepEqual(await control.read(), { pause: true, stop: true });
});
