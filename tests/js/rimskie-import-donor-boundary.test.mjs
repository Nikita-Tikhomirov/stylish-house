import assert from 'node:assert/strict';
import { mkdtemp, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';

import { parseCategoryPage } from '../../scripts/rimskie-import/lib/category-parser.mjs';
import { parseProductPage } from '../../scripts/rimskie-import/lib/product-parser.mjs';
import { resolveDonorUrl } from '../../scripts/rimskie-import/lib/donor-url-policy.mjs';
import {
    detectImageFormat,
    DonorRequestError,
    PlaywrightTransport,
    shouldAllowBrowserRequest,
} from '../../scripts/rimskie-import/lib/playwright-transport.mjs';

const categoryUrl = 'https://rimskie.com/catalog/rimskie-shtory/white';
const imageUrl = 'https://rimskie.com/media/output/first.webp';
const validWebpBytes = Buffer.from([
    0x52, 0x49, 0x46, 0x46, 0x16, 0x00, 0x00, 0x00,
    0x57, 0x45, 0x42, 0x50, 0x56, 0x50, 0x38, 0x58,
    0x0a, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
    0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
]);

function fakePlaywright({
    finalUrl = categoryUrl,
    html = '<html><body>ok</body></html>',
    status = 200,
    responseImageUrl = imageUrl,
    image = validWebpBytes,
    contentType = 'image/webp',
} = {}) {
    const calls = { evaluate: [], goto: [] };
    const routes = [];
    const page = {
        goto: async (url) => {
            calls.goto.push(url);
            return {
                status: () => status,
                url: () => finalUrl,
            };
        },
        content: async () => html,
        evaluate: async (callback, url) => {
            calls.evaluate.push(url);
            return { ok: true };
        },
        waitForResponse: async (predicate) => {
            const response = {
                url: () => responseImageUrl,
                status: () => 200,
                body: async () => image,
                headerValue: async () => contentType,
            };
            assert.equal(predicate(response), true);
            return response;
        },
    };
    const context = {
        pages: () => [page],
        route: async (pattern, handler) => routes.push([pattern, handler]),
        close: async () => {},
    };
    const chromium = {
        launchPersistentContext: async () => context,
    };

    return { calls, chromium, routes };
}

async function openFakeTransport(options = {}) {
    const fake = fakePlaywright(options);
    const transport = await PlaywrightTransport.open({
        profileDir: 'profile',
        executablePath: 'chrome.exe',
        chromium: fake.chromium,
    });

    return { fake, transport };
}

function isInvalidUrl(error) {
    return error instanceof DonorRequestError && error.kind === 'invalid_url';
}

test('category parser rejects an off-origin product link', () => {
    const html = `
        <article class="product" data-id="11889">
            <a class="product-link" href="https://evil.example/products/11889"></a>
        </article>
    `;

    assert.throws(() => parseCategoryPage(html, categoryUrl), /product URL.*rimskie\.com/i);
});

test('donor URL policy separates category and product page contexts', () => {
    assert.equal(resolveDonorUrl(categoryUrl, { kind: 'category' }), categoryUrl);
    assert.equal(
        resolveDonorUrl('https://rimskie.com/products/11889-example', { kind: 'product' }),
        'https://rimskie.com/products/11889-example',
    );
    assert.throws(() => resolveDonorUrl(categoryUrl, { kind: 'product' }), /product path/i);
    assert.throws(
        () => resolveDonorUrl('https://rimskie.com/products/11889-example', { kind: 'category' }),
        /category path/i,
    );
});

test('donor URL policy rejects encoded separators and ambiguous dot segments', () => {
    for (const value of [
        'https://rimskie.com/catalog/rimskie-shtory/white%2fextra',
        'https://rimskie.com/catalog/rimskie-shtory/white%5cextra',
        'https://rimskie.com/catalog/rimskie-shtory/%2e%2e/products/11889-example',
        'https://rimskie.com/catalog/rimskie-shtory/white%252fextra',
    ]) {
        assert.throws(() => resolveDonorUrl(value, { kind: 'category' }), /encoded|ambiguous/i);
    }
});

test('category parser rejects a non-HTTPS next-page link', () => {
    const html = '<a rel="next" href="http://rimskie.com/catalog?page=2"></a>';

    assert.throws(() => parseCategoryPage(html, categoryUrl), /next-page URL.*HTTPS/i);
});

test('category parser keeps pagination on the exact configured source path', () => {
    const html = '<a rel="next" href="/catalog/rimskie-shtory/black?page=2"></a>';

    assert.throws(() => parseCategoryPage(html, categoryUrl), /exact source path|pagination/i);
});

test('category parser rejects an off-origin card image link', () => {
    const html = `
        <article class="product" data-id="11889">
            <a class="product-link" href="/products/11889"></a>
            <div class="product-image-background"
                style="background-image: url('https://cdn.example/card.webp')"></div>
        </article>
    `;

    assert.throws(() => parseCategoryPage(html, categoryUrl), /card image URL.*rimskie\.com/i);
});

test('product parser rejects an off-origin first-image link', () => {
    const html = `
        <main data-product-id="11889">
            <div class="product-gallery"><img src="https://cdn.example/first.webp"></div>
        </main>
    `;

    assert.throws(
        () => parseProductPage(html, 'https://rimskie.com/products/11889'),
        /first image URL.*rimskie\.com/i,
    );
});

test('getHtml rejects unapproved queued URLs before browser navigation', async () => {
    for (const url of ['https://evil.example/catalog', 'http://rimskie.com/catalog']) {
        const { fake, transport } = await openFakeTransport();

        await assert.rejects(transport.getHtml(url), isInvalidUrl);
        assert.deepEqual(fake.calls.goto, []);
    }
});

test('getHtml rejects an off-origin final navigation response', async () => {
    const { transport } = await openFakeTransport({ finalUrl: 'https://evil.example/redirected' });

    await assert.rejects(transport.getHtml(categoryUrl), isInvalidUrl);
});

test('download rejects unapproved image URLs before browser fetch', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-boundary-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));

    for (const url of ['https://evil.example/first.webp', 'http://rimskie.com/first.webp']) {
        const { fake, transport } = await openFakeTransport({ responseImageUrl: url });
        const destination = join(rootDir, `${fake.calls.evaluate.length}.webp`);

        await assert.rejects(transport.downloadFirstImage(url, destination), isInvalidUrl);
        assert.deepEqual(fake.calls.evaluate, []);
    }
});

test('browser routing permits only the exact counted operation outside challenge mode', () => {
    const activeHtml = { kind: 'html', url: categoryUrl };
    assert.equal(shouldAllowBrowserRequest({
        routeMode: 'collecting', activeOperation: activeHtml, resourceType: 'document', url: categoryUrl,
    }), true);
    assert.equal(shouldAllowBrowserRequest({
        routeMode: 'collecting', activeOperation: activeHtml, resourceType: 'script',
        url: 'https://rimskie.com/catalog/rimskie-shtory/app.js',
    }), false);
    assert.equal(shouldAllowBrowserRequest({
        routeMode: 'collecting', activeOperation: activeHtml, resourceType: 'xhr',
        url: 'https://rimskie.com/catalog/rimskie-shtory/api',
    }), false);
    assert.equal(shouldAllowBrowserRequest({
        routeMode: 'collecting', activeOperation: activeHtml, resourceType: 'document',
        url: 'https://evil.example/redirected', redirectsFromActive: true,
    }), false);
    assert.equal(shouldAllowBrowserRequest({
        routeMode: 'collecting', activeOperation: activeHtml, resourceType: 'document',
        url: 'https://rimskie.com/catalog/rimskie-shtory/black', redirectsFromActive: true,
    }), false);

    const activeImage = { kind: 'image', url: imageUrl };
    assert.equal(shouldAllowBrowserRequest({
        routeMode: 'collecting', activeOperation: activeImage, resourceType: 'fetch', url: imageUrl,
    }), true);
    assert.equal(shouldAllowBrowserRequest({
        routeMode: 'collecting', activeOperation: activeImage, resourceType: 'fetch',
        url: 'https://rimskie.com/media/output/second.webp',
    }), false);
    assert.equal(shouldAllowBrowserRequest({
        routeMode: 'challenge', activeOperation: { kind: 'challenge', url: categoryUrl },
        resourceType: 'script',
        url: 'https://rimskie.com/challenge/script.js',
    }), true);
    assert.equal(shouldAllowBrowserRequest({
        routeMode: 'challenge', activeOperation: { kind: 'challenge', url: categoryUrl },
        resourceType: 'script',
        url: 'https://evil.example/challenge.js',
    }), false);
    assert.equal(shouldAllowBrowserRequest({
        routeMode: 'challenge', activeOperation: { kind: 'challenge', url: categoryUrl },
        resourceType: 'websocket',
        url: 'https://rimskie.com/challenge/socket', method: 'GET',
    }), false);
});

test('one HTML operation authorizes only its first exact document request', async () => {
    const decisions = [];
    let stopCalls = 0;
    let routeHandler;
    const page = {
        goto: async () => {
            for (let count = 0; count < 2; count += 1) {
                await routeHandler({
                    request: () => ({
                        method: () => 'GET',
                        resourceType: () => 'document',
                        url: () => categoryUrl,
                        redirectedFrom: () => null,
                    }),
                    continue: async () => decisions.push('continue'),
                    abort: async () => decisions.push('abort'),
                });
            }
            return { status: () => 200, url: () => categoryUrl };
        },
        content: async () => '<html><body>ok</body></html>',
        evaluate: async () => { stopCalls += 1; },
    };
    const context = {
        pages: () => [page],
        route: async (_pattern, handler) => { routeHandler = handler; },
        close: async () => {},
    };
    const transport = await PlaywrightTransport.open({
        profileDir: 'profile', executablePath: 'chrome.exe',
        chromium: { launchPersistentContext: async () => context },
    });

    await transport.getHtml(categoryUrl);

    assert.deepEqual(decisions, ['continue', 'abort']);
    assert.equal(stopCalls, 1);
});

test('WebP validation checks RIFF length and the first chunk type', () => {
    assert.equal(detectImageFormat(validWebpBytes), 'webp');

    const wrongLength = Buffer.from(validWebpBytes);
    wrongLength.writeUInt32LE(1, 4);
    assert.equal(detectImageFormat(wrongLength), null);

    const wrongChunk = Buffer.from(validWebpBytes);
    wrongChunk.write('JUNK', 12, 'ascii');
    assert.equal(detectImageFormat(wrongChunk), null);
    assert.equal(detectImageFormat(Buffer.from('RIFF\u0004\u0000\u0000\u0000WEBP')), null);
});

test('image download rejects generic HTML even when MIME claims WebP', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-boundary-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const { transport } = await openFakeTransport({
        image: Buffer.from('<!doctype html><html><body>temporarily blocked</body></html>'),
        contentType: 'image/webp',
    });

    await assert.rejects(
        transport.downloadFirstImage(imageUrl, join(rootDir, '11889.webp')),
        (error) => error instanceof DonorRequestError && error.kind === 'challenge',
    );
});

test('image download requires a .webp destination before browser access', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-boundary-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const { fake, transport } = await openFakeTransport();

    await assert.rejects(
        transport.downloadFirstImage(imageUrl, join(rootDir, '11889.jpg')),
        (error) => error instanceof DonorRequestError && error.kind === 'invalid_destination',
    );
    assert.deepEqual(fake.calls.evaluate, []);
});
