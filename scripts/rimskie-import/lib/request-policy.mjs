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
    } = {}) {
        this.clock = clock;
        this.random = random;
        this.sleep = sleep;
        this.delays = { html: htmlDelayMs, image: imageDelayMs };
        this.hourlyLimit = hourlyLimit;
        this.backoffMs = backoffMs;
        this.onEvent = onEvent;
        this.requestTimes = [];
        this.consecutiveFailures = 0;
    }

    async beforeRequest(kind) {
        const delayRange = this.delays[kind];
        if (!delayRange) throw new Error(`Unsupported request kind: ${kind}`);

        const milliseconds = randomDelay(delayRange, this.random);
        await this.onEvent({ type: 'delay', kind, milliseconds, at: this.clock.now() });
        await this.sleep(milliseconds);

        const now = this.clock.now();
        this.requestTimes = this.requestTimes.filter((timestamp) => now - timestamp < HOUR_MS);
        if (this.requestTimes.length >= this.hourlyLimit) {
            throw new RequestBudgetError();
        }

        this.requestTimes.push(now);
        await this.onEvent({ type: 'request', kind, at: now });
    }

    recordSuccess() {
        this.consecutiveFailures = 0;
    }

    async recordFailure(kind) {
        if (this.consecutiveFailures >= this.backoffMs.length) return 'pause';

        const failureNumber = this.consecutiveFailures + 1;
        const milliseconds = this.backoffMs[this.consecutiveFailures];
        this.consecutiveFailures = failureNumber;
        const action = failureNumber >= this.backoffMs.length ? 'pause' : 'retry';

        await this.onEvent({
            type: action,
            kind,
            failureNumber,
            milliseconds,
            at: this.clock.now(),
        });
        await this.sleep(milliseconds);

        return action;
    }
}
