import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { JSDOM } from 'jsdom';
import test from 'node:test';

const publicRoot = new URL('../../scripts/rimskie-import/gui/public/', import.meta.url);

test('dashboard renders persisted progress and dispatches a protected full start', async (t) => {
    const [html, script] = await Promise.all([
        readFile(new URL('index.html', publicRoot), 'utf8'),
        readFile(new URL('app.js', publicRoot), 'utf8'),
    ]);
    const dom = new JSDOM(html, {
        url: 'http://127.0.0.1:43127/',
        runScripts: 'outside-only',
        pretendToBeVisual: true,
    });
    t.after(() => dom.window.close());
    const calls = [];
    const snapshot = {
        id: 'run-test', status: 'paused', pauseReason: 'operator', exportReady: false,
        currentSource: { label: 'На арочное окно', pages: 2, pendingProducts: 3 },
        metrics: {
            categories: 46, completedCategories: 8, pages: 15,
            uniqueProducts: 27, images: 26, memberships: 41, requests: 52,
            requestsLastHour: 3, hourlyLimit: 20,
        },
        nextRequestAt: 1_787_743_400_000,
        lastUrl: 'https://rimskie.com/products/11889-test',
        sources: [{
            label: 'На арочное окно', slug: 'rimskie-shtory-na-arochnoe-okno',
            status: 'pending', pages: 2, pendingProducts: 3,
        }],
        events: [{ at: '2026-08-26T09:00:00.000Z', type: 'pause', reason: 'operator' }],
    };
    dom.window.fetch = async (url, options = {}) => {
        calls.push({ url, options });
        assert.match(String(url), /^\/api\//);
        if (url === '/api/bootstrap') return new Response(JSON.stringify({
            dataRoot: 'G:\\stylish-house-data\\rimskie-imports',
            sessionToken: 'test-session-token',
            runs: [{ id: 'run-test', status: 'paused', metrics: snapshot.metrics }],
        }), { status: 200, headers: { 'Content-Type': 'application/json' } });
        if (url === '/api/runs/run-test') {
            return new Response(JSON.stringify(snapshot), { status: 200 });
        }
        if (String(url).startsWith('/api/runs/run-test/products')) {
            const secondPage = String(url).includes('page=2');
            return new Response(JSON.stringify({
                page: secondPage ? 2 : 1, pages: 2, total: 25,
                items: [{
                    externalId: secondPage ? '11900' : '11889',
                    name: secondPage ? 'Римская штора со второй страницы' : 'Римская штора тестовая',
                    sourcePrice: { amount: 4799, currency: 'RUB' },
                    imageUrl: '/api/runs/run-test/images/11889',
                    categories: ['rimskie-shtory-na-arochnoe-okno'],
                }],
            }), { status: 200 });
        }
        if (url === '/api/runs' && options.method === 'POST') {
            return new Response(JSON.stringify({ ok: true, runId: 'run-new', command: 'start' }), {
                status: 202,
            });
        }
        throw new Error(`Unexpected request: ${url}`);
    };
    dom.window.confirm = () => true;

    dom.window.eval(script);
    await dom.window.rimskieGuiReady;

    assert.equal(dom.window.document.querySelector('meta[name="viewport"]').content, 'width=device-width, initial-scale=1');
    assert.equal(dom.window.document.querySelector('[data-role="data-root"]').textContent, 'G:\\stylish-house-data\\rimskie-imports');
    assert.match(dom.window.document.querySelector('[data-role="status-title"]').textContent, /пауз/i);
    assert.match(dom.window.document.querySelector('[data-metric="products"]').textContent, /27/);
    assert.match(dom.window.document.querySelector('[data-role="request-budget"]').textContent, /3 из 20/);
    assert.match(dom.window.document.querySelector('[data-role="last-url"]').textContent, /products\/11889-test/);
    assert.match(dom.window.document.querySelector('[data-role="categories"]').textContent, /На арочное окно/);
    assert.match(dom.window.document.querySelector('[data-role="products"]').textContent, /Римская штора тестовая/);
    assert.equal(dom.window.document.querySelector('[data-role="product-count"]').textContent, '25');
    assert.equal(dom.window.document.querySelector('[data-action="start"]').textContent.trim(), 'Начать новый сбор');
    assert.equal(dom.window.document.querySelector('[data-action="resume"]').disabled, false);
    assert.equal(dom.window.document.querySelector('[data-action="pause"]').disabled, true);

    dom.window.document.querySelector('[data-page-action="next"]').click();
    await new Promise((resolve) => dom.window.setTimeout(resolve, 0));
    assert.equal(calls.some((call) => String(call.url).includes('products?page=2')), true);
    assert.match(dom.window.document.querySelector('[data-role="products"]').textContent, /со второй страницы/);

    dom.window.document.querySelector('[data-action="start"]').click();
    await new Promise((resolve) => dom.window.setTimeout(resolve, 0));

    const startCall = calls.find((call) => call.url === '/api/runs' && call.options.method === 'POST');
    assert.equal(startCall.options.headers['X-Rimskie-Token'], 'test-session-token');
    assert.equal(startCall.options.headers.Origin, undefined);
    assert.equal(dom.window.document.querySelector('[data-role="run-id"]').textContent, 'run-new');
    assert.match(dom.window.document.querySelector('[data-role="status-title"]').textContent, /созда/i);
});
