import { access as fsAccess, mkdir, rename, writeFile } from 'node:fs/promises';
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
    }

    async #route(route) {
        const request = route.request();
        const resourceType = request.resourceType();
        const url = request.url();
        const isUnrelatedImage = resourceType === 'image' && url !== this.allowedImageUrl;

        if (blockedResourceTypes.has(resourceType) || isAnalyticsUrl(url) || isUnrelatedImage) {
            await route.abort();
            return;
        }
        await route.continue();
    }

    async getHtml(url) {
        const response = await this.page.goto(url, { waitUntil: 'domcontentloaded' });
        if (!response) {
            throw new DonorRequestError('transport', 'Donor navigation returned no response', { url });
        }
        const failure = statusFailure(response.status(), url);
        if (failure) throw failure;

        const html = await this.page.content();
        if (challengePattern.test(html)) {
            throw new DonorRequestError(
                'challenge',
                'BotHunt/challenge requires an explicitly authorized click in the visible browser',
                { url },
            );
        }

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
            const failure = statusFailure(response.status(), url);
            if (failure) throw failure;

            const bytes = await response.body();
            await mkdir(dirname(destination), { recursive: true });
            const temporaryPath = `${destination}.${process.pid}.tmp`;
            await writeFile(temporaryPath, bytes);
            await rename(temporaryPath, destination);

            return bytes;
        } finally {
            this.allowedImageUrl = null;
        }
    }

    async close() {
        await this.context.close();
    }
}
