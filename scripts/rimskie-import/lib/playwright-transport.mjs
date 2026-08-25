import { access as fsAccess, mkdir, readFile, rename, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';

import { chromium as defaultChromium } from 'playwright-core';

const blockedResourceTypes = new Set(['stylesheet', 'font', 'media']);
const analyticsHosts = [
    'google-analytics.com',
    'googletagmanager.com',
    'mc.yandex.ru',
    'metrika.yandex.ru',
    'analytics.yandex.ru',
];
const challengePattern = /bothunt|captcha|challenge-platform|cf-chl-|verify you are human|подтвердите[^<]{0,40}человек/i;

export function detectImageFormat(bytes) {
    if (!Buffer.isBuffer(bytes)) return null;
    if (bytes.length >= 12 && bytes.subarray(0, 4).equals(Buffer.from('RIFF'))
        && bytes.subarray(8, 12).equals(Buffer.from('WEBP'))) return 'webp';
    if (bytes.length >= 3 && bytes[0] === 0xff && bytes[1] === 0xd8 && bytes[2] === 0xff) return 'jpeg';
    if (bytes.length >= 8 && bytes.subarray(0, 8).equals(Buffer.from([
        0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a,
    ]))) return 'png';

    return null;
}

export async function validateImageFile(path, expectedFormat = null) {
    try {
        const detectedFormat = detectImageFormat(await readFile(path));
        return expectedFormat ? detectedFormat === expectedFormat : Boolean(detectedFormat);
    } catch (error) {
        if (error?.code === 'ENOENT') return false;
        throw error;
    }
}

export class DonorRequestError extends Error {
    constructor(kind, message, details = {}) {
        super(message);
        this.name = 'DonorRequestError';
        this.kind = kind;
        Object.assign(this, details);
    }
}

function windowsCandidates(environment) {
    return [
        environment.ProgramFiles && join(environment.ProgramFiles, 'Google', 'Chrome', 'Application', 'chrome.exe'),
        environment['ProgramFiles(x86)']
            && join(environment['ProgramFiles(x86)'], 'Google', 'Chrome', 'Application', 'chrome.exe'),
        environment.LOCALAPPDATA
            && join(environment.LOCALAPPDATA, 'Google', 'Chrome', 'Application', 'chrome.exe'),
        environment.ProgramFiles && join(environment.ProgramFiles, 'Microsoft', 'Edge', 'Application', 'msedge.exe'),
        environment['ProgramFiles(x86)']
            && join(environment['ProgramFiles(x86)'], 'Microsoft', 'Edge', 'Application', 'msedge.exe'),
    ].filter(Boolean);
}

function linuxCandidates() {
    return [
        '/usr/bin/google-chrome-stable',
        '/usr/bin/google-chrome',
        '/usr/bin/chromium-browser',
        '/usr/bin/chromium',
        '/snap/bin/chromium',
    ];
}

export async function findBrowserExecutable({
    platform = process.platform,
    environment = process.env,
    access = fsAccess,
} = {}) {
    const candidates = platform === 'win32' ? windowsCandidates(environment) : linuxCandidates();

    for (const candidate of candidates) {
        try {
            await access(candidate);
            return candidate;
        } catch (error) {
            if (error?.code !== 'ENOENT') throw error;
        }
    }

    throw new Error('No installed Chrome, Edge, or Chromium executable was found');
}

function isAnalyticsUrl(value) {
    let hostname;
    try {
        hostname = new URL(value).hostname;
    } catch {
        return false;
    }

    return analyticsHosts.some((host) => hostname === host || hostname.endsWith(`.${host}`));
}

function statusFailure(status, url) {
    if (status === 403 || status === 429) {
        return new DonorRequestError(`http_${status}`, `Donor returned HTTP ${status}`, { status, url });
    }
    if (status >= 400) {
        return new DonorRequestError('http_error', `Donor returned HTTP ${status}`, { status, url });
    }

    return null;
}

export class PlaywrightTransport {
    static async open({
        profileDir,
        headed = true,
        executablePath,
        chromium = defaultChromium,
    }) {
        if (!headed) {
            throw new Error('Headless donor collection is disabled; a visible browser is required');
        }
        const browserPath = executablePath || await findBrowserExecutable();
        const context = await chromium.launchPersistentContext(profileDir, {
            headless: false,
            executablePath: browserPath,
        });
        const page = context.pages()[0] || await context.newPage();
        const transport = new PlaywrightTransport(context, page);
        await context.route('**/*', (route) => transport.#route(route));

        return transport;
    }

    constructor(context, page) {
        this.context = context;
        this.page = page;
        this.allowedImageUrl = null;
        this.routingArmed = false;
    }

    async #route(route) {
        const request = route.request();
        const resourceType = request.resourceType();
        const url = request.url();
        if (!this.routingArmed) {
            await route.continue();
            return;
        }
        const isUnrelatedImage = resourceType === 'image' && url !== this.allowedImageUrl;

        if (blockedResourceTypes.has(resourceType) || isAnalyticsUrl(url) || isUnrelatedImage) {
            await route.abort();
            return;
        }
        await route.continue();
    }

    async getHtml(url) {
        let response;
        try {
            response = await this.page.goto(url, { waitUntil: 'domcontentloaded' });
        } catch (error) {
            const kind = error?.name === 'TimeoutError' ? 'timeout' : 'network';
            throw new DonorRequestError(kind, error?.message || `Failed to navigate to ${url}`, { url });
        }
        if (!response) {
            throw new DonorRequestError('network', 'Donor navigation returned no response', { url });
        }
        let html;
        try {
            html = await this.page.content();
        } catch (error) {
            const kind = error?.name === 'TimeoutError' ? 'timeout' : 'network';
            throw new DonorRequestError(kind, error?.message || `Failed to read donor DOM at ${url}`, { url });
        }
        if (challengePattern.test(html)) {
            this.routingArmed = false;
            throw new DonorRequestError(
                'challenge',
                'BotHunt/challenge requires an explicitly authorized click in the visible browser',
                { url },
            );
        }
        const failure = statusFailure(response.status(), url);
        if (failure) throw failure;

        this.routingArmed = true;

        return html;
    }

    async downloadFirstImage(url, destination) {
        this.allowedImageUrl = url;
        try {
            const responsePromise = this.page.waitForResponse((response) => response.url() === url);
            const [, response] = await Promise.all([
                this.page.evaluate(async (imageUrl) => {
                    const result = await fetch(imageUrl, { credentials: 'include' });
                    await result.arrayBuffer();
                }, url),
                responsePromise,
            ]);
            const status = response.status();
            const bytes = await response.body();
            const contentType = (await response.headerValue('content-type') || '')
                .split(';', 1)[0]
                .trim()
                .toLowerCase();
            const bodyPrefix = bytes.subarray(0, 2_048).toString('utf8');
            if (contentType === 'text/html' || challengePattern.test(bodyPrefix)) {
                this.routingArmed = false;
                throw new DonorRequestError(
                    'challenge',
                    'HTML challenge was returned for the first-image request',
                    { status, url },
                );
            }
            if (status < 200 || status >= 300) {
                throw statusFailure(status, url)
                    || new DonorRequestError('http_error', `Donor returned HTTP ${status}`, { status, url });
            }
            const declaredFormat = contentType === 'image/webp' ? 'webp' : null;
            const detectedFormat = detectImageFormat(bytes);
            if (!declaredFormat || detectedFormat !== declaredFormat) {
                throw new DonorRequestError('invalid_image', 'First-image response must be valid WebP data', {
                    status,
                    url,
                    contentType,
                });
            }
            await mkdir(dirname(destination), { recursive: true });
            const temporaryPath = `${destination}.${process.pid}.tmp`;
            await writeFile(temporaryPath, bytes);
            await rename(temporaryPath, destination);

            return bytes;
        } catch (error) {
            if (error instanceof DonorRequestError) throw error;
            const kind = error?.name === 'TimeoutError' ? 'timeout' : 'network';
            throw new DonorRequestError(kind, error?.message || 'First-image request failed', { url });
        } finally {
            this.allowedImageUrl = null;
        }
    }

    async close() {
        await this.context.close();
    }
}
