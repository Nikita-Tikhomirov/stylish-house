import assert from 'node:assert/strict';
import { mkdtemp, rm, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import test from 'node:test';

import { createGuiServer } from '../../scripts/rimskie-import/gui/server.mjs';

function fixture() {
    const calls = [];
    const snapshot = {
        id: 'run-safe', status: 'paused', pauseReason: 'operator',
        metrics: { categories: 46, completedCategories: 3, uniqueProducts: 12 },
        events: [], sources: [],
    };
    return {
        calls,
        statusService: {
            dataRoot: 'G:\\stylish-house-data\\rimskie-imports',
            listRuns: async () => [{ id: 'run-safe', status: 'paused' }],
            getRunSnapshot: async (runId) => ({ ...snapshot, id: runId }),
            listProducts: async (runId, page, perPage) => ({
                page, perPage, total: 1, pages: 1,
                items: [{ externalId: '11889', name: 'Тест', runId }],
            }),
            getImagePath: async () => null,
        },
        supervisor: Object.fromEntries([
            ['start', async () => { calls.push(['start']); return { runId: 'run-new', command: 'start' }; }],
            ...['pause', 'resume', 'stop', 'exportRun', 'openFolder'].map((method) => [
                method,
                async (runId) => { calls.push([method, runId]); return { runId, command: method }; },
            ]),
        ]),
    };
}

async function startServer(t, overrides = {}) {
    const data = fixture();
    const gui = createGuiServer({
        host: '127.0.0.1',
        port: 0,
        token: 'test-session-token',
        statusService: data.statusService,
        supervisor: data.supervisor,
        publicDir: overrides.publicDir,
    });
    const address = await gui.listen();
    t.after(() => gui.close());
    return { ...data, gui, baseUrl: address.url };
}

test('GUI server rejects non-loopback binding', () => {
    const data = fixture();
    assert.throws(() => createGuiServer({
        host: '0.0.0.0', port: 43127, token: 'token-token-token',
        statusService: data.statusService, supervisor: data.supervisor,
    }), /127\.0\.0\.1|loopback/i);
});

test('bootstrap and run reads return persisted local state', async (t) => {
    const { baseUrl } = await startServer(t);

    const bootstrapResponse = await fetch(`${baseUrl}/api/bootstrap`);
    const bootstrap = await bootstrapResponse.json();
    const snapshotResponse = await fetch(`${baseUrl}/api/runs/run-safe`);
    const snapshot = await snapshotResponse.json();
    const productsResponse = await fetch(`${baseUrl}/api/runs/run-safe/products?page=1&perPage=20`);
    const products = await productsResponse.json();

    assert.equal(bootstrapResponse.status, 200);
    assert.equal(bootstrap.dataRoot, 'G:\\stylish-house-data\\rimskie-imports');
    assert.equal(bootstrap.sessionToken, 'test-session-token');
    assert.deepEqual(bootstrap.runs, [{ id: 'run-safe', status: 'paused' }]);
    assert.equal(snapshot.status, 'paused');
    assert.equal(products.items[0].externalId, '11889');
    assert.equal(products.perPage, 20);
    assert.equal(bootstrapResponse.headers.get('access-control-allow-origin'), null);
    assert.match(bootstrapResponse.headers.get('content-security-policy'), /default-src 'self'/);
});

test('POST actions require exact same origin and session token', async (t) => {
    const { baseUrl, calls } = await startServer(t);
    const endpoint = `${baseUrl}/api/runs/run-safe/pause`;

    const missingSecurity = await fetch(endpoint, { method: 'POST' });
    const foreignOrigin = await fetch(endpoint, {
        method: 'POST',
        headers: { Origin: 'https://evil.example', 'X-Rimskie-Token': 'test-session-token' },
    });
    const wrongToken = await fetch(endpoint, {
        method: 'POST',
        headers: { Origin: baseUrl, 'X-Rimskie-Token': 'wrong-token' },
    });
    const accepted = await fetch(endpoint, {
        method: 'POST',
        headers: { Origin: baseUrl, 'X-Rimskie-Token': 'test-session-token' },
    });

    assert.equal(missingSecurity.status, 403);
    assert.equal(foreignOrigin.status, 403);
    assert.equal(wrongToken.status, 403);
    assert.equal(accepted.status, 202);
    assert.deepEqual(calls, [['pause', 'run-safe']]);
});

test('new-run endpoint dispatches the full collector start action', async (t) => {
    const { baseUrl, calls } = await startServer(t);
    const response = await fetch(`${baseUrl}/api/runs`, {
        method: 'POST',
        headers: { Origin: baseUrl, 'X-Rimskie-Token': 'test-session-token' },
    });

    assert.equal(response.status, 202);
    assert.deepEqual(await response.json(), { ok: true, runId: 'run-new', command: 'start' });
    assert.deepEqual(calls, [['start']]);
});

test('routes reject unsafe run identifiers, unknown methods and oversized bodies', async (t) => {
    const { baseUrl, calls } = await startServer(t);
    const traversal = await fetch(`${baseUrl}/api/runs/%2e%2e%2foutside`);
    const wrongMethod = await fetch(`${baseUrl}/api/bootstrap`, { method: 'DELETE' });
    const oversized = await fetch(`${baseUrl}/api/runs`, {
        method: 'POST',
        headers: {
            Origin: baseUrl,
            'X-Rimskie-Token': 'test-session-token',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ padding: 'x'.repeat(20_000) }),
    });

    assert.equal(traversal.status, 400);
    assert.equal(wrongMethod.status, 405);
    assert.equal(oversized.status, 413);
    assert.deepEqual(calls, []);
});

test('validated WebP responses are bounded to the selected run', async (t) => {
    const root = await mkdtemp('G:\\stylish-house-data\\rimskie-imports\\gui-image-');
    t.after(() => rm(root, { recursive: true, force: true }));
    const imagePath = join(root, '11889.webp');
    await writeFile(imagePath, Buffer.from('RIFF-test-WEBP'));
    const data = fixture();
    data.statusService.getImagePath = async (runId, externalId) => {
        assert.equal(runId, 'run-safe');
        assert.equal(externalId, '11889');
        return imagePath;
    };
    const gui = createGuiServer({
        host: '127.0.0.1', port: 0, token: 'test-session-token',
        statusService: data.statusService, supervisor: data.supervisor,
    });
    const { url } = await gui.listen();
    t.after(() => gui.close());

    const response = await fetch(`${url}/api/runs/run-safe/images/11889`);

    assert.equal(response.status, 200);
    assert.equal(response.headers.get('content-type'), 'image/webp');
    assert.equal(Buffer.from(await response.arrayBuffer()).toString(), 'RIFF-test-WEBP');
});
