import { spawn as defaultSpawnProcess } from 'node:child_process';
import {
    access as fsAccess,
    mkdir,
    rm,
} from 'node:fs/promises';
import { createServer } from 'node:net';
import { dirname, extname, join } from 'node:path';
import { setTimeout as sleep } from 'node:timers/promises';

import { chromium as defaultChromium } from 'playwright-core';

import {
    isApprovedDonorOriginUrl,
    isApprovedDonorUrl,
    resolveDonorUrl,
} from './donor-url-policy.mjs';
import { writeFileAtomically } from './safe-filesystem.mjs';
import { detectImageFormat, validateImageFile } from './webp.mjs';

export { detectImageFormat, validateImageFile } from './webp.mjs';

const challengePattern = /bothunt|captcha|challenge-platform|cf-chl-|verify you are human|подтвердите[^<]{0,40}человек|не удалось выполнить проверку|попроб(?:овать|уйте)\s+(?:снова|ещ[её]\s+раз)/i;
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
const devToolsPortFileName = 'DevToolsActivePort';

function visibleDocumentText(html) {
    return String(html)
        .replace(/<!--[\s\S]*?-->/g, ' ')
        .replace(/<(?:script|style|template|noscript)\b[^>]*>[\s\S]*?<\/(?:script|style|template|noscript)>/gi, ' ')
        .replace(/<[^>]+>/g, ' ')
        .replace(/\s+/g, ' ');
}

function isChallengeDocument(html) {
    return challengePattern.test(visibleDocumentText(html));
}

export class DonorRequestError extends Error {
    constructor(kind, message, details = {}) {
        super(message);
        this.name = 'DonorRequestError';
        this.kind = kind;
        Object.assign(this, details);
    }
}

export function chromeCdpArguments(profileDir, debuggingPort) {
    if (!Number.isInteger(debuggingPort) || debuggingPort < 1 || debuggingPort > 65_535) {
        throw new Error('Chrome CDP requires a nonzero local debugging port');
    }
    return [
        `--remote-debugging-port=${debuggingPort}`,
        `--user-data-dir=${profileDir}`,
        '--no-first-run',
        '--no-default-browser-check',
        '--new-window',
        'about:blank',
    ];
}

async function prepareCdpProfile(profileDir) {
    await mkdir(profileDir, { recursive: true });
    await rm(join(profileDir, devToolsPortFileName), { force: true });
}

async function reserveLocalPort() {
    return new Promise((resolve, reject) => {
        const server = createServer();
        server.once('error', reject);
        server.listen(0, '127.0.0.1', () => {
            const address = server.address();
            const port = typeof address === 'object' ? address?.port : null;
            server.close((error) => {
                if (error) reject(error);
                else if (!Number.isInteger(port)) reject(new Error('Could not reserve a local CDP port'));
                else resolve(port);
            });
        });
    });
}

async function waitForChromeEndpoint({
    endpoint,
    browserProcess,
    fetchImpl = fetch,
    wait = sleep,
    timeoutMs = 20_000,
}) {
    const deadline = Date.now() + timeoutMs;
    while (Date.now() < deadline) {
        if (browserProcess.exitCode !== null) {
            throw new Error(`Chrome exited before its DevTools port was ready (${browserProcess.exitCode})`);
        }
        try {
            const response = await fetchImpl(`${endpoint}/json/version`);
            if (response.ok) return endpoint;
        } catch {}
        await wait(100);
    }

    throw new Error(`Chrome DevTools port was not ready within ${timeoutMs} ms`);
}

export async function launchChromeCdp({
    executablePath,
    profileDir,
    chromium = defaultChromium,
    spawnProcess = defaultSpawnProcess,
    prepareProfile = prepareCdpProfile,
    reservePort = reserveLocalPort,
    waitForEndpoint = waitForChromeEndpoint,
}) {
    await prepareProfile(profileDir);
    const debuggingPort = await reservePort();
    const endpoint = `http://127.0.0.1:${debuggingPort}`;
    const browserProcess = spawnProcess(executablePath, chromeCdpArguments(profileDir, debuggingPort), {
        detached: false,
        stdio: 'ignore',
        windowsHide: false,
    });
    const spawnFailure = typeof browserProcess.once === 'function'
        ? new Promise((_, reject) => browserProcess.once('error', reject))
        : new Promise(() => {});
    try {
        const readyEndpoint = await Promise.race([
            waitForEndpoint({ endpoint, browserProcess }),
            spawnFailure,
        ]);
        const browser = await chromium.connectOverCDP(readyEndpoint);
        return { browser, browserProcess };
    } catch (error) {
        if (browserProcess.exitCode === null) browserProcess.kill();
        throw error;
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
    if (!['collecting', 'challenge'].includes(routeMode)) return false;
    if (resourceType === 'websocket') return false;

    if (routeMode === 'collecting' && activeOperation?.kind === 'html'
        && resourceType === 'document'
        && isApprovedDonorUrl(url, { kind: activeOperation.pageKind || 'html' })) {
        return url === activeOperation.url && !activeOperation.consumed && !redirectsFromActive;
    }
    if (activeOperation?.kind === 'image' && url === activeOperation.url) {
        return method === 'GET' && ['fetch', 'xhr', 'image'].includes(resourceType);
    }
    if (activeOperation?.kind === 'html' && resourceType !== 'document') {
        if (!['GET', 'HEAD', 'POST', 'OPTIONS'].includes(method)) return false;
        let parsed;
        try {
            parsed = new URL(url);
        } catch {
            return false;
        }
        if (parsed.protocol !== 'https:') return false;
        if (resourceType === 'image'
            && isApprovedDonorOriginUrl(url)
            && parsed.pathname.startsWith('/media/output/')) {
            return false;
        }
        return true;
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
    if (!controls) return true;
    const count = await controls.count();
    for (let index = 0; index < count; index += 1) {
        const control = typeof controls.nth === 'function' ? controls.nth(index) : controls;
        if (typeof control.isVisible !== 'function' || await control.isVisible()) return true;
    }

    return false;
}

export class PlaywrightTransport {
    static async open({
        profileDir,
        headed = true,
        executablePath,
        chromium = defaultChromium,
        cdpLauncher = launchChromeCdp,
    }) {
        if (!headed) {
            throw new Error('Headless donor collection is disabled; a visible browser is required');
        }
        const browserPath = executablePath || await findBrowserExecutable();
        const { browser, browserProcess } = await cdpLauncher({
            profileDir,
            executablePath: browserPath,
            chromium,
        });
        try {
            const [context] = browser.contexts();
            if (!context) throw new Error('Native Chrome did not expose its default browser context');
            const page = context.pages()[0] || await context.newPage();
            const transport = new PlaywrightTransport(context, page, { browser, browserProcess });
            await context.route('**/*', (route) => transport.#route(route));
            return transport;
        } catch (error) {
            await browser.close().catch(() => {});
            if (browserProcess.exitCode === null) browserProcess.kill();
            throw error;
        }
    }

    constructor(context, page, { browser = null, browserProcess = null } = {}) {
        this.context = context;
        this.page = page;
        this.browser = browser;
        this.browserProcess = browserProcess;
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
            if (isChallengeDocument(html)) {
                this.pendingChallenge = { url: finalUrl, pageKind };
                throw new DonorRequestError(
                    'challenge',
                    'BotHunt/challenge requires an explicitly authorized click in the visible browser',
                    {
                        url: finalUrl,
                        pageKind,
                        manual: await hasFullCaptchaControls(this.page),
                    },
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
                    { url: requestedUrl, pageKind, manual: true },
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
                    { url: finalUrl, pageKind, manual: true },
                );
            }
            if (isChallengeDocument(html)) {
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
        if (this.browser) {
            await this.browser.close();
            if (this.browserProcess?.exitCode === null) this.browserProcess.kill();
            return;
        }
        await this.context.close();
    }
}
