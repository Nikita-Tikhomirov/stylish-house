import { createReadStream } from 'node:fs';
import { readFile, stat } from 'node:fs/promises';
import { createServer } from 'node:http';
import { extname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import { assertNumericExternalId, assertSafeIdentifier } from '../lib/safe-filesystem.mjs';

const maxBodyBytes = 16_384;
const defaultPublicDir = fileURLToPath(new URL('./public/', import.meta.url));
const contentTypes = new Map([
    ['.html', 'text/html; charset=utf-8'],
    ['.css', 'text/css; charset=utf-8'],
    ['.js', 'text/javascript; charset=utf-8'],
]);

function secureHeaders(contentType = 'application/json; charset=utf-8') {
    return {
        'Content-Type': contentType,
        'Cache-Control': 'no-store',
        'Content-Security-Policy': "default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'",
        'Cross-Origin-Opener-Policy': 'same-origin',
        'Cross-Origin-Resource-Policy': 'same-origin',
        'Referrer-Policy': 'no-referrer',
        'X-Content-Type-Options': 'nosniff',
        'X-Frame-Options': 'DENY',
    };
}

function json(response, status, value, extraHeaders = {}) {
    response.writeHead(status, { ...secureHeaders(), ...extraHeaders });
    response.end(`${JSON.stringify(value)}\n`);
}

function errorStatus(error) {
    if (error?.statusCode) return error.statusCode;
    if (error?.code === 'ENOENT') return 404;
    if (/safe|invalid|must be|not allowed|traversal|identifier/i.test(error?.message || '')) return 400;
    return 500;
}

function decodeSegment(value, label) {
    let decoded;
    try {
        decoded = decodeURIComponent(value);
    } catch {
        throw Object.assign(new Error(`${label} is invalid`), { statusCode: 400 });
    }
    if (decoded.includes('/') || decoded.includes('\\')) {
        throw Object.assign(new Error(`${label} is invalid`), { statusCode: 400 });
    }
    return decoded;
}

function validateRunId(value) {
    const runId = decodeSegment(value, 'run ID');
    assertSafeIdentifier(runId, 'run ID', { pattern: /^[a-z0-9][a-z0-9-]{0,127}$/ });
    return runId;
}

async function consumeBody(request) {
    const declaredLength = Number(request.headers['content-length'] || 0);
    if (Number.isFinite(declaredLength) && declaredLength > maxBodyBytes) {
        throw Object.assign(new Error('Request body is too large'), { statusCode: 413 });
    }
    let size = 0;
    for await (const chunk of request) {
        size += chunk.length;
        if (size > maxBodyBytes) {
            throw Object.assign(new Error('Request body is too large'), { statusCode: 413 });
        }
    }
}

export function createGuiServer({
    host = '127.0.0.1',
    port = 43127,
    token,
    statusService,
    supervisor,
    publicDir = defaultPublicDir,
}) {
    if (host !== '127.0.0.1') throw new Error('GUI server must bind to loopback 127.0.0.1');
    if (!Number.isInteger(port) || port < 0 || port > 65_535) throw new Error('GUI port is invalid');
    if (typeof token !== 'string' || token.length < 12) throw new Error('GUI session token is invalid');
    if (!statusService || !supervisor) throw new Error('GUI services are required');

    let baseUrl = null;

    async function requireMutationAccess(request) {
        if (!baseUrl || request.headers.origin !== baseUrl
            || request.headers['x-rimskie-token'] !== token) {
            throw Object.assign(new Error('Управляющий запрос не прошёл локальную проверку'), {
                statusCode: 403,
            });
        }
        await consumeBody(request);
    }

    async function serveStatic(response, fileName) {
        const path = join(publicDir, fileName);
        const body = await readFile(path);
        response.writeHead(200, secureHeaders(contentTypes.get(extname(path))));
        response.end(body);
    }

    async function handle(request, response) {
        const url = new URL(request.url || '/', 'http://127.0.0.1');
        const path = url.pathname;

        if (request.method === 'GET' && path === '/') return serveStatic(response, 'index.html');
        if (request.method === 'GET' && path === '/styles.css') return serveStatic(response, 'styles.css');
        if (request.method === 'GET' && path === '/app.js') return serveStatic(response, 'app.js');

        if (request.method === 'GET' && path === '/api/bootstrap') {
            return json(response, 200, {
                dataRoot: statusService.dataRoot,
                sessionToken: token,
                runs: await statusService.listRuns(),
            });
        }
        if (request.method === 'GET' && path === '/api/runs') {
            return json(response, 200, { runs: await statusService.listRuns() });
        }
        if (request.method === 'POST' && path === '/api/runs') {
            await requireMutationAccess(request);
            return json(response, 202, { ok: true, ...await supervisor.start() });
        }

        const productMatch = path.match(/^\/api\/runs\/([^/]+)\/products$/);
        if (request.method === 'GET' && productMatch) {
            const runId = validateRunId(productMatch[1]);
            return json(response, 200, await statusService.listProducts(
                runId,
                Number(url.searchParams.get('page') || 1),
                Number(url.searchParams.get('perPage') || 24),
            ));
        }

        const imageMatch = path.match(/^\/api\/runs\/([^/]+)\/images\/([^/]+)$/);
        if (request.method === 'GET' && imageMatch) {
            const runId = validateRunId(imageMatch[1]);
            const externalId = decodeSegment(imageMatch[2], 'product external ID');
            assertNumericExternalId(externalId);
            const imagePath = await statusService.getImagePath(runId, externalId);
            const imageStats = await stat(imagePath);
            response.writeHead(200, {
                ...secureHeaders('image/webp'),
                'Content-Length': imageStats.size,
            });
            createReadStream(imagePath).pipe(response);
            return;
        }

        const actionMatch = path.match(/^\/api\/runs\/([^/]+)\/(pause|resume|stop|export|open-folder)$/);
        if (request.method === 'POST' && actionMatch) {
            await requireMutationAccess(request);
            const runId = validateRunId(actionMatch[1]);
            await statusService.getRunSnapshot(runId);
            const methods = {
                pause: 'pause',
                resume: 'resume',
                stop: 'stop',
                export: 'exportRun',
                'open-folder': 'openFolder',
            };
            return json(response, 202, {
                ok: true,
                ...await supervisor[methods[actionMatch[2]]](runId),
            });
        }

        const runMatch = path.match(/^\/api\/runs\/([^/]+)$/);
        if (request.method === 'GET' && runMatch) {
            return json(response, 200, await statusService.getRunSnapshot(validateRunId(runMatch[1])));
        }

        const knownPath = path === '/api/bootstrap' || path === '/api/runs'
            || productMatch || imageMatch || actionMatch || runMatch;
        if (knownPath) {
            return json(response, 405, { error: 'Метод запроса не поддерживается' }, { Allow: 'GET, POST' });
        }
        return json(response, 404, { error: 'Страница локальной панели не найдена' });
    }

    const server = createServer((request, response) => {
        handle(request, response).catch((error) => {
            if (response.headersSent) {
                response.destroy(error);
                return;
            }
            json(response, errorStatus(error), {
                error: errorStatus(error) === 500
                    ? 'Локальная панель не смогла выполнить операцию'
                    : error.message,
            });
        });
    });

    return {
        async listen() {
            await new Promise((resolve, reject) => {
                server.once('error', reject);
                server.listen(port, host, resolve);
            });
            const address = server.address();
            baseUrl = `http://${host}:${address.port}`;
            return { host, port: address.port, url: baseUrl };
        },
        async close() {
            if (!server.listening) return;
            await new Promise((resolve, reject) => server.close((error) => error ? reject(error) : resolve()));
        },
    };
}
