import { access as fsAccess, mkdir } from 'node:fs/promises';
import { dirname, extname, join } from 'node:path';

import { chromium as defaultChromium } from 'playwright-core';

import {
    isApprovedDonorOriginUrl,
    isApprovedDonorUrl,
    resolveDonorUrl,
} from './donor-url-policy.mjs';
import { writeFileAtomically } from './safe-filesystem.mjs';
import { detectImageFormat, validateImageFile } from './webp.mjs';

export { detectImageFormat, validateImageFile } from './webp.mjs';

const analyticsHosts = [
    'google-analytics.com',
    'googletagmanager.com',
    'mc.yandex.ru',
    'metrika.yandex.ru',
    'analytics.yandex.ru',
];
const challengePattern = /bothunt|captcha|challenge-platform|cf-chl-|verify you are human|подтвердите[^<]{0,40}человек/i;
const simpleChallengeRetryPattern = /^(?:Попробовать снова|Повторить|Try again)$/i;
const fullCaptchaControlSelector = [
    'iframe[src*="captcha" i]',
    'iframe[title*="captcha" i]',
    'input[name*="captcha" i]',
    'input[id*="captcha" i]',
    '[data-sitekey]',
    '[class*="puzzle" i]',
    '[id*="puzzle" i]',
    'canvas',
].join(', ');
const htmlDocumentPattern = /^\s*(?:<!doctype\s+html\b|<html\b|<head\b|<body\b)/i;
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

function donorRequestError(error, label, value) {
    return new DonorRequestError('invalid_url', error?.message || `${label} is not approved`, {
        url: value,
    });
}

function approvedUrl(value, { kind, label }) {
    try {
        return resolveDonorUrl(value, { kind, label });
    } catch (error) {
        throw donorRequestError(error, label, value);
    }
}

function redirectedFromCountedTarget(request, targetUrl) {
    let previous = request.redirectedFrom?.();
    while (previous) {
        if (previous.url() === targetUrl) return true;
        previous = previous.redirectedFrom?.();
    }

    return false;
}

export function shouldAllowBrowserRequest({
    routeMode,
    activeOperation,
    resourceType,
    url,
    method = 'GET',
    redirectsFromActive = false,
}) {
    if (resourceType === 'websocket' || method !== 'GET') return false;
    if (!isApprovedDonorOriginUrl(url) || isAnalyticsUrl(url)) return false;
    if (!['collecting', 'challenge'].includes(routeMode)) return false;

    if (routeMode === 'collecting' && activeOperation?.kind === 'html'
        && resourceType === 'document'
        && isApprovedDonorUrl(url, { kind: activeOperation.pageKind || 'html' })) {
        return url === activeOperation.url && !activeOperation.consumed && !redirectsFromActive;
    }
    if (activeOperation?.kind === 'html'
        && ['stylesheet', 'script', 'fetch', 'xhr'].includes(resourceType)) {
        return true;
    }
    if (activeOperation?.kind === 'image' && url === activeOperation.url) {
        return ['fetch', 'xhr', 'image'].includes(resourceType);
    }

    return false;
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

async function hasFullCaptchaControls(page) {
    const controls = page.locator?.(fullCaptchaControlSelector);
    return !controls || await controls.count() > 0;
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
            chromiumSandbox: true,
            executablePath: browserPath,
            serviceWorkers: 'block',
            offline: true,
        });
        try {
            const page = context.pages()[0] || await context.newPage();
            const transport = new PlaywrightTransport(context, page);
            await context.route('**/*', (route) => transport.#route(route));
            await context.routeWebSocket('**/*', (webSocket) => webSocket.close({
                code: 1008,
                reason: 'WebSocket connections are disabled',
            }));
            await context.setOffline(false);

            return transport;
        } catch (error) {
            await context.close().catch(() => {});
            throw error;
        }
    }

    constructor(context, page) {
        this.context = context;
        this.page = page;
        this.activeOperation = null;
        this.pendingChallenge = null;
        this.routeMode = 'idle';
    }

    async #route(route) {
        const request = route.request();
        const resourceType = request.resourceType();
        const url = request.url();

        const allowed = shouldAllowBrowserRequest({
            routeMode: this.routeMode,
            activeOperation: this.activeOperation,
            resourceType,
            url,
            method: request.method?.() || 'GET',
            redirectsFromActive: this.activeOperation?.kind === 'html'
                && redirectedFromCountedTarget(request, this.activeOperation.url),
        });
        if (allowed) {
            if (this.routeMode === 'collecting'
                && (this.activeOperation?.kind === 'image' || resourceType === 'document')) {
                this.activeOperation.consumed = true;
            }
            await route.continue();
        } else {
            await route.abort();
        }
    }

    async getHtml(url, { kind: pageKind = 'html' } = {}) {
        const requestedUrl = approvedUrl(url, { kind: pageKind, label: 'queued donor HTML URL' });
        this.pendingChallenge = null;
        this.activeOperation = { kind: 'html', pageKind, url: requestedUrl, consumed: false };
        this.routeMode = 'collecting';
        try {
            let response;
            try {
                response = await this.page.goto(requestedUrl, { waitUntil: 'domcontentloaded' });
            } catch (error) {
                const kind = error?.name === 'TimeoutError' ? 'timeout' : 'network';
                throw new DonorRequestError(kind, error?.message || `Failed to navigate to ${requestedUrl}`, {
                    url: requestedUrl,
                });
            }
            if (!response) {
                throw new DonorRequestError('network', 'Donor navigation returned no response', {
                    url: requestedUrl,
                });
            }
            const finalUrl = approvedUrl(response.url?.() || requestedUrl, {
                kind: pageKind,
                label: 'final donor HTML URL',
            });
            if (finalUrl !== requestedUrl) {
                throw new DonorRequestError(
                    'invalid_url',
                    'HTML redirects are outside the exact counted request boundary',
                    { url: finalUrl, pageKind },
                );
            }
            let html;
            try {
                html = await this.page.content();
            } catch (error) {
                const kind = error?.name === 'TimeoutError' ? 'timeout' : 'network';
                throw new DonorRequestError(kind, error?.message || `Failed to read donor DOM at ${finalUrl}`, {
                    url: finalUrl,
                });
            }
            if (challengePattern.test(html)) {
                this.pendingChallenge = { url: finalUrl, pageKind };
                throw new DonorRequestError(
                    'challenge',
                    'BotHunt/challenge requires an explicitly authorized click in the visible browser',
                    { url: finalUrl, pageKind },
                );
            }
            const failure = statusFailure(response.status(), finalUrl);
            if (failure) throw failure;
            return html;
        } finally {
            if (this.pendingChallenge) {
                this.routeMode = 'challenge';
                this.activeOperation = {
                    kind: 'html',
                    pageKind: this.pendingChallenge.pageKind,
                    url: this.pendingChallenge.url,
                    consumed: true,
                };
            } else {
                this.routeMode = 'idle';
                this.activeOperation = null;
                await this.page.evaluate(() => globalThis.stop?.()).catch(() => {});
            }
        }
    }

    async retrySimpleChallenge(url, { kind: pageKind = 'html' } = {}) {
        const requestedUrl = approvedUrl(url, {
            kind: pageKind,
            label: 'queued donor challenge URL',
        });
        const visibleUrl = this.page.url?.() || requestedUrl;
        const pendingChallengeMatches = this.pendingChallenge?.url === requestedUrl
            && this.pendingChallenge?.pageKind === pageKind;
        let currentUrl = requestedUrl;
        if (!pendingChallengeMatches || /^https:/i.test(visibleUrl)) {
            currentUrl = approvedUrl(visibleUrl, {
                kind: pageKind,
                label: 'visible donor challenge URL',
            });
        }
        if (currentUrl !== requestedUrl) {
            throw new DonorRequestError(
                'invalid_url',
                'Visible challenge page differs from the exact counted request boundary',
                { url: currentUrl, pageKind },
            );
        }

        const isChromeErrorPage = pendingChallengeMatches
            && /^chrome-error:\/\/chromewebdata\/?$/i.test(visibleUrl);
        let retryButton = this.page.getByRole?.('button', {
            name: simpleChallengeRetryPattern,
            exact: true,
        });
        let retryButtonAvailable = retryButton && await retryButton.count() === 1;
        if (retryButtonAvailable) {
            retryButtonAvailable = await retryButton.isVisible() && await retryButton.isEnabled();
        }
        const useChromeErrorReload = isChromeErrorPage && !retryButtonAvailable;
        if (!isChromeErrorPage) {
            if (await hasFullCaptchaControls(this.page)) {
                throw new DonorRequestError(
                    'challenge',
                    'Full CAPTCHA controls are present or challenge eligibility cannot be verified',
                    { url: requestedUrl, pageKind },
                );
            }
            if (!retryButtonAvailable) {
                throw new DonorRequestError(
                    'challenge',
                    'No single visible simple challenge retry button is available',
                    { url: requestedUrl, pageKind },
                );
            }
        }

        this.activeOperation = { kind: 'html', pageKind, url: requestedUrl, consumed: false };
        this.routeMode = 'collecting';
        try {
            let response;
            try {
                if (useChromeErrorReload) {
                    response = await this.page.reload({ waitUntil: 'domcontentloaded' });
                } else {
                    [response] = await Promise.all([
                        this.page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
                        retryButton.click(),
                    ]);
                }
            } catch (error) {
                const failureKind = error?.name === 'TimeoutError' ? 'timeout' : 'network';
                throw new DonorRequestError(
                    failureKind,
                    error?.message || `Simple challenge retry failed at ${requestedUrl}`,
                    { url: requestedUrl, pageKind },
                );
            }
            if (!response) {
                throw new DonorRequestError('network', 'Challenge retry returned no response', {
                    url: requestedUrl, pageKind,
                });
            }
            const finalUrl = approvedUrl(response.url?.() || this.page.url?.() || requestedUrl, {
                kind: pageKind,
                label: 'final donor challenge URL',
            });
            if (finalUrl !== requestedUrl) {
                throw new DonorRequestError(
                    'invalid_url',
                    'Challenge retry redirected outside the exact counted request boundary',
                    { url: finalUrl, pageKind },
                );
            }
            const html = await this.page.content();
            const failure = statusFailure(response.status(), finalUrl);
            if (failure) throw failure;
            if (await hasFullCaptchaControls(this.page)) {
                throw new DonorRequestError(
                    'challenge',
                    'Full CAPTCHA controls appeared after the simple challenge retry',
                    { url: finalUrl, pageKind },
                );
            }
            if (challengePattern.test(html)) {
                throw new DonorRequestError('challenge', 'Challenge remained after one retry click', {
                    url: finalUrl, pageKind,
                });
            }
            return html;
        } finally {
            this.pendingChallenge = null;
            this.routeMode = 'idle';
            this.activeOperation = null;
            await this.page.evaluate(() => globalThis.stop?.()).catch(() => {});
        }
    }

    async downloadFirstImage(url, destination) {
        const requestedUrl = approvedUrl(url, { kind: 'image', label: 'queued donor image URL' });
        if (extname(destination).toLowerCase() !== '.webp') {
            throw new DonorRequestError(
                'invalid_destination',
                'First-image destination must use the .webp extension',
                { url: requestedUrl, destination },
            );
        }
        this.activeOperation = { kind: 'image', url: requestedUrl, consumed: false };
        this.routeMode = 'collecting';
        try {
            const responsePromise = this.page.waitForResponse((response) => response.url() === requestedUrl);
            const [, response] = await Promise.all([
                this.page.evaluate(async (imageUrl) => {
                    const result = await fetch(imageUrl, { credentials: 'include' });
                    await result.arrayBuffer();
                }, requestedUrl),
                responsePromise,
            ]);
            const finalUrl = approvedUrl(response.url(), {
                kind: 'image',
                label: 'final donor image URL',
            });
            if (finalUrl !== requestedUrl) {
                throw new DonorRequestError(
                    'invalid_url',
                    'First-image redirects are outside the exact counted request boundary',
                    { url: finalUrl },
                );
            }
            const status = response.status();
            const bytes = await response.body();
            const contentType = (await response.headerValue('content-type') || '')
                .split(';', 1)[0]
                .trim()
                .toLowerCase();
            const bodyPrefix = bytes.subarray(0, 2_048).toString('utf8');
            const failure = statusFailure(status, finalUrl);
            if (failure) throw failure;
            if (status < 200 || status >= 300) {
                throw new DonorRequestError('http_error', `Donor returned HTTP ${status}`, {
                    status, url: finalUrl,
                });
            }
            if (contentType === 'text/html' || htmlDocumentPattern.test(bodyPrefix)
                || challengePattern.test(bodyPrefix)) {
                throw new DonorRequestError(
                    'challenge',
                    'HTML challenge was returned for the first-image request',
                    {
                        status,
                        url: finalUrl,
                        challengeDocumentUrl: this.page.url?.(),
                        pageKind: 'product',
                    },
                );
            }
            const declaredFormat = contentType === 'image/webp' ? 'webp' : null;
            const detectedFormat = detectImageFormat(bytes);
            if (!declaredFormat || detectedFormat !== declaredFormat) {
                throw new DonorRequestError('invalid_image', 'First-image response must be valid WebP data', {
                    status,
                    url: finalUrl,
                    contentType,
                });
            }
            await mkdir(dirname(destination), { recursive: true });
            await writeFileAtomically(dirname(destination), destination, bytes);

            return bytes;
        } catch (error) {
            if (error instanceof DonorRequestError) throw error;
            const kind = error?.name === 'TimeoutError' ? 'timeout' : 'network';
            throw new DonorRequestError(kind, error?.message || 'First-image request failed', {
                url: requestedUrl,
            });
        } finally {
            this.activeOperation = null;
            this.routeMode = 'idle';
        }
    }

    async close() {
        await this.context.close();
    }
}
