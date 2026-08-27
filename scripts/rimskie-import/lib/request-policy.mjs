import { setTimeout as sleepTimeout } from 'node:timers/promises';

export const DEFAULT_LIMITS = Object.freeze({
    htmlDelayMs: [120_000, 240_000],
    imageDelayMs: [60_000, 120_000],
    challengeDelayMs: [10_000, 20_000],
    hourlyLimit: 20,
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
        challengeDelayMs = DEFAULT_LIMITS.challengeDelayMs,
        hourlyLimit = DEFAULT_LIMITS.hourlyLimit,
        budgetCheckIntervalMs = 30_000,
        backoffMs = DEFAULT_LIMITS.backoffMs,
        onEvent = async () => {},
        persistState = async () => {},
        beforeReserve = async () => true,
        state = {},
    } = {}) {
        this.clock = clock;
        this.random = random;
        this.sleep = sleep;
        this.delays = { html: htmlDelayMs, image: imageDelayMs, challenge: challengeDelayMs };
        this.hourlyLimit = hourlyLimit;
        this.budgetCheckIntervalMs = budgetCheckIntervalMs;
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
        if (!Number.isFinite(this.backoffUntil)) return 'none';

        let milliseconds = Math.max(0, this.backoffUntil - this.clock.now());
        if (milliseconds > 0) {
            await this.onEvent({
                type: 'backoff_resume',
                kind: this.lastFailureKind,
                milliseconds,
                at: this.clock.now(),
            });
            while (milliseconds > 0) {
                await this.sleep(Math.min(milliseconds, this.budgetCheckIntervalMs));
                if (!await this.beforeReserve()) return 'cancelled';
                milliseconds = Math.max(0, this.backoffUntil - this.clock.now());
            }
        }
        this.backoffUntil = null;
        if (this.consecutiveFailures >= this.backoffMs.length) {
            this.consecutiveFailures = 0;
        }
        await this.persistState(this.snapshot(), 'backoff-complete');

        return 'completed';
    }

    async beforeRequest(kind) {
        const delayRange = this.delays[kind];
        if (!delayRange) throw new Error(`Unsupported request kind: ${kind}`);

        const milliseconds = randomDelay(delayRange, this.random);
        await this.onEvent({ type: 'delay', kind, milliseconds, at: this.clock.now() });
        await this.sleep(milliseconds);
        if (!await this.beforeReserve()) throw new RequestCancelledError();

        await this.#waitForHourlySlot(kind);

        const now = this.clock.now();
        this.requestTimes = this.requestTimes.filter((timestamp) => now - timestamp < HOUR_MS);
        this.requestTimes.push(now);
        await this.persistState(this.snapshot(), 'reservation');
        await this.onEvent({ type: 'request', kind, at: now });
    }

    async #waitForHourlySlot(kind) {
        if (!Number.isInteger(this.hourlyLimit) || this.hourlyLimit <= 0) {
            throw new RequestBudgetError();
        }
        while (true) {
            const now = this.clock.now();
            this.requestTimes = this.requestTimes.filter((timestamp) => now - timestamp < HOUR_MS);
            if (this.requestTimes.length < this.hourlyLimit) return;

            const waitUntil = this.requestTimes[0] + HOUR_MS + 1;
            const milliseconds = Math.max(1, waitUntil - now);
            await this.onEvent({ type: 'hourly_wait', kind, milliseconds, at: now });

            let remaining = milliseconds;
            while (remaining > 0) {
                await this.sleep(Math.min(remaining, this.budgetCheckIntervalMs));
                if (!await this.beforeReserve()) throw new RequestCancelledError();
                remaining = Math.max(0, waitUntil - this.clock.now());
            }
        }
    }

    recordSuccess() {
        this.consecutiveFailures = 0;
        this.pauseRequired = false;
        this.lastFailureKind = null;
        this.backoffUntil = null;
        return this.persistState(this.snapshot(), 'success');
    }

    async recordFailure(kind, { afterPersist = async () => {} } = {}) {
        if (this.consecutiveFailures >= this.backoffMs.length) {
            this.consecutiveFailures = 0;
        }

        const failureNumber = this.consecutiveFailures + 1;
        const milliseconds = this.backoffMs[this.consecutiveFailures];
        this.consecutiveFailures = failureNumber;
        const action = 'retry';
        this.lastFailureKind = kind;
        this.pauseRequired = false;
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
        let remaining = milliseconds;
        while (remaining > 0) {
            const chunk = Math.min(remaining, this.budgetCheckIntervalMs);
            await this.sleep(chunk);
            remaining -= chunk;
            if (!await this.beforeReserve()) return 'cancelled';
        }
        this.backoffUntil = null;
        if (failureNumber >= this.backoffMs.length) this.consecutiveFailures = 0;
        await this.persistState(this.snapshot(), 'backoff-complete');

        return action;
    }
}
