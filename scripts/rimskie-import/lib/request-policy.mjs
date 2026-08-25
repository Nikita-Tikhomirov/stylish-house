import { setTimeout as sleepTimeout } from 'node:timers/promises';

export const DEFAULT_LIMITS = Object.freeze({
    htmlDelayMs: [20_000, 40_000],
    imageDelayMs: [10_000, 20_000],
    hourlyLimit: 120,
    backoffMs: [120_000, 300_000, 900_000],
    concurrency: 1,
});

const HOUR_MS = 3_600_000;

export class RequestBudgetError extends Error {
    constructor() {
        super('hourly request budget exhausted');
        this.name = 'RequestBudgetError';
        this.code = 'hourly_budget_exhausted';
    }
}

export class RequestCancelledError extends Error {
    constructor() {
        super('request cancelled before transport access');
        this.name = 'RequestCancelledError';
        this.code = 'request_cancelled';
    }
}

function defaultClock() {
    return { now: () => Date.now() };
}

function randomDelay([minimum, maximum], random) {
    return Math.round(minimum + ((maximum - minimum) * random()));
}

export class RequestPolicy {
    constructor({
        clock = defaultClock(),
        random = Math.random,
        sleep = sleepTimeout,
        htmlDelayMs = DEFAULT_LIMITS.htmlDelayMs,
        imageDelayMs = DEFAULT_LIMITS.imageDelayMs,
        hourlyLimit = DEFAULT_LIMITS.hourlyLimit,
        backoffMs = DEFAULT_LIMITS.backoffMs,
        onEvent = async () => {},
        persistState = async () => {},
        beforeReserve = async () => true,
        state = {},
    } = {}) {
        this.clock = clock;
        this.random = random;
        this.sleep = sleep;
        this.delays = { html: htmlDelayMs, image: imageDelayMs };
        this.hourlyLimit = hourlyLimit;
        this.backoffMs = backoffMs;
        this.onEvent = onEvent;
        this.persistState = persistState;
        this.beforeReserve = beforeReserve;
        this.hydrate(state);
    }

    hydrate(state = {}) {
        this.requestTimes = Array.isArray(state.requestTimes)
            ? state.requestTimes.filter(Number.isFinite)
            : [];
        this.consecutiveFailures = Number.isInteger(state.consecutiveFailures)
            ? Math.max(0, state.consecutiveFailures)
            : 0;
        this.pauseRequired = Boolean(state.pauseRequired);
        this.lastFailureKind = typeof state.lastFailureKind === 'string'
            ? state.lastFailureKind
            : null;
        this.backoffUntil = Number.isFinite(state.backoffUntil)
            ? state.backoffUntil
            : null;
    }

    connect({ persistState, beforeReserve, onEvent } = {}) {
        if (persistState) this.persistState = persistState;
        if (beforeReserve) this.beforeReserve = beforeReserve;
        if (onEvent) this.onEvent = onEvent;
    }

    snapshot() {
        return {
            requestTimes: [...this.requestTimes],
            consecutiveFailures: this.consecutiveFailures,
            pauseRequired: this.pauseRequired,
            lastFailureKind: this.lastFailureKind,
            backoffUntil: this.backoffUntil,
        };
    }

    requiresFailurePause() {
        return this.pauseRequired;
    }

    async acknowledgeFailurePause() {
        this.consecutiveFailures = 0;
        this.pauseRequired = false;
        this.lastFailureKind = null;
        this.backoffUntil = null;
        await this.persistState(this.snapshot(), 'failure-pause-acknowledged');
    }

    async resumePendingBackoff() {
        if (!Number.isFinite(this.backoffUntil)) return false;

        const milliseconds = Math.max(0, this.backoffUntil - this.clock.now());
        if (milliseconds > 0) {
            await this.onEvent({
                type: 'backoff_resume',
                kind: this.lastFailureKind,
                milliseconds,
                at: this.clock.now(),
            });
            await this.sleep(milliseconds);
        }
        this.backoffUntil = null;
        await this.persistState(this.snapshot(), 'backoff-complete');

        return true;
    }

    async beforeRequest(kind) {
        const delayRange = this.delays[kind];
        if (!delayRange) throw new Error(`Unsupported request kind: ${kind}`);

        const milliseconds = randomDelay(delayRange, this.random);
        await this.onEvent({ type: 'delay', kind, milliseconds, at: this.clock.now() });
        await this.sleep(milliseconds);
        if (!await this.beforeReserve()) throw new RequestCancelledError();

        const now = this.clock.now();
        this.requestTimes = this.requestTimes.filter((timestamp) => now - timestamp < HOUR_MS);
        if (this.requestTimes.length >= this.hourlyLimit) {
            throw new RequestBudgetError();
        }

        this.requestTimes.push(now);
        await this.persistState(this.snapshot(), 'reservation');
        await this.onEvent({ type: 'request', kind, at: now });
    }

    recordSuccess() {
        this.consecutiveFailures = 0;
        this.pauseRequired = false;
        this.lastFailureKind = null;
        this.backoffUntil = null;
        return this.persistState(this.snapshot(), 'success');
    }

    async recordFailure(kind, { afterPersist = async () => {} } = {}) {
        if (this.consecutiveFailures >= this.backoffMs.length) return 'pause';

        const failureNumber = this.consecutiveFailures + 1;
        const milliseconds = this.backoffMs[this.consecutiveFailures];
        this.consecutiveFailures = failureNumber;
        const action = failureNumber >= this.backoffMs.length ? 'pause' : 'retry';
        this.lastFailureKind = kind;
        this.pauseRequired = action === 'pause';
        this.backoffUntil = this.clock.now() + milliseconds;

        await this.persistState(this.snapshot(), 'failure');
        await afterPersist({ action, failureNumber, kind, milliseconds });

        await this.onEvent({
            type: action,
            kind,
            failureNumber,
            milliseconds,
            at: this.clock.now(),
        });
        await this.sleep(milliseconds);
        this.backoffUntil = null;
        await this.persistState(this.snapshot(), 'backoff-complete');

        return action;
    }
}
