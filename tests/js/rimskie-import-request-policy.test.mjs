import assert from 'node:assert/strict';
import test from 'node:test';

import {
    DEFAULT_LIMITS,
    RequestPolicy,
} from '../../scripts/rimskie-import/lib/request-policy.mjs';

function createFakeClock(initialTime = 0) {
    let currentTime = initialTime;

    return {
        now: () => currentTime,
        advance: (milliseconds) => {
            currentTime += milliseconds;
        },
    };
}

test('third consecutive donor failure cools down and starts a new retry cycle', async () => {
    const sleeps = [];
    const fakeClock = createFakeClock();
    const policy = new RequestPolicy({
        clock: fakeClock,
        random: () => 0,
        sleep: async (milliseconds) => sleeps.push(milliseconds),
    });

    assert.equal(await policy.recordFailure('http_403'), 'retry');
    assert.equal(await policy.recordFailure('http_429'), 'retry');
    assert.equal(await policy.recordFailure('network'), 'retry');
    assert.equal(sleeps.reduce((total, milliseconds) => total + milliseconds, 0), 1_320_000);
    assert.equal(policy.snapshot().consecutiveFailures, 0);
    assert.equal(policy.requiresFailurePause(), false);
});

test('failure backoff checks pause and stop controls every 30 seconds', async () => {
    const sleeps = [];
    let controlChecks = 0;
    const policy = new RequestPolicy({
        clock: createFakeClock(10_000),
        backoffMs: [90_000],
        budgetCheckIntervalMs: 30_000,
        sleep: async (milliseconds) => sleeps.push(milliseconds),
        beforeReserve: async () => {
            controlChecks += 1;
            return controlChecks < 2;
        },
    });

    assert.equal(await policy.recordFailure('http_403'), 'cancelled');
    assert.deepEqual(sleeps, [30_000, 30_000]);
    assert.equal(policy.snapshot().backoffUntil, 100_000);
});

test('default rolling hour budget waits for request 21 and continues automatically', async () => {
    const fakeClock = createFakeClock();
    const sleeps = [];
    const events = [];
    const policy = new RequestPolicy({
        clock: fakeClock,
        random: () => 0,
        htmlDelayMs: [0, 0],
        budgetCheckIntervalMs: 3_600_001,
        sleep: async (milliseconds) => {
            sleeps.push(milliseconds);
            fakeClock.advance(milliseconds);
        },
        onEvent: async (event) => events.push(event),
    });

    for (let count = 0; count < 20; count += 1) {
        await policy.beforeRequest('html');
    }

    await assert.doesNotReject(policy.beforeRequest('html'));
    assert.equal(sleeps.at(-1), 3_600_001);
    assert.equal(events.some((event) => event.type === 'hourly_wait'
        && event.milliseconds === 3_600_001), true);
    assert.equal(policy.snapshot().requestTimes.length, 1);
});

test('new policy process hydrates durable timestamps and waits for request 21', async () => {
    let durableState = null;
    const fakeClock = createFakeClock(50_000);
    const firstProcess = new RequestPolicy({
        clock: fakeClock,
        random: () => 0,
        sleep: async () => {},
        htmlDelayMs: [0, 0],
        persistState: async (state) => { durableState = structuredClone(state); },
    });
    for (let count = 0; count < 20; count += 1) {
        await firstProcess.beforeRequest('html');
    }

    const resumedProcess = new RequestPolicy({
        clock: fakeClock,
        random: () => 0,
        sleep: async (milliseconds) => fakeClock.advance(milliseconds),
        htmlDelayMs: [0, 0],
        budgetCheckIntervalMs: 3_600_001,
        state: durableState,
    });

    await assert.doesNotReject(resumedProcess.beforeRequest('html'));
    assert.equal(resumedProcess.snapshot().requestTimes.length, 1);
});

test('new policy process resumes the durable failure backoff sequence', async () => {
    let durableState = null;
    const sleeps = [];
    const firstProcess = new RequestPolicy({
        sleep: async (milliseconds) => sleeps.push(milliseconds),
        persistState: async (state) => { durableState = structuredClone(state); },
    });
    assert.equal(await firstProcess.recordFailure('network'), 'retry');

    const resumedProcess = new RequestPolicy({
        sleep: async (milliseconds) => sleeps.push(milliseconds),
        state: durableState,
    });
    assert.equal(await resumedProcess.recordFailure('timeout'), 'retry');

    assert.equal(sleeps.reduce((total, milliseconds) => total + milliseconds, 0), 420_000);
    assert.equal(resumedProcess.snapshot().consecutiveFailures, 2);
});

test('new policy process checks controls while waiting an unfinished durable backoff', async () => {
    let durableState = null;
    const fakeClock = createFakeClock(1_000);
    const firstProcess = new RequestPolicy({
        clock: fakeClock,
        sleep: async () => { throw new Error('simulated process death during backoff'); },
        persistState: async (state) => { durableState = structuredClone(state); },
    });

    await assert.rejects(firstProcess.recordFailure('network'), /process death/);
    assert.equal(durableState.backoffUntil, 121_000);

    const resumedSleeps = [];
    let controlChecks = 0;
    const resumedProcess = new RequestPolicy({
        clock: fakeClock,
        budgetCheckIntervalMs: 30_000,
        sleep: async (milliseconds) => {
            resumedSleeps.push(milliseconds);
            fakeClock.advance(milliseconds);
        },
        beforeReserve: async () => {
            controlChecks += 1;
            return true;
        },
        state: durableState,
        persistState: async (state) => { durableState = structuredClone(state); },
    });
    await resumedProcess.resumePendingBackoff();

    assert.deepEqual(resumedSleeps, [30_000, 30_000, 30_000, 30_000]);
    assert.equal(controlChecks, 4);
    assert.equal(durableState.backoffUntil, null);
});

test('default request delay spaces pages, images, and one guarded challenge click', async () => {
    assert.deepEqual(DEFAULT_LIMITS.htmlDelayMs, [120_000, 240_000]);
    assert.deepEqual(DEFAULT_LIMITS.imageDelayMs, [60_000, 120_000]);
    assert.deepEqual(DEFAULT_LIMITS.challengeDelayMs, [10_000, 20_000]);

    const events = [];
    const fakeClock = createFakeClock(10_000);
    const policy = new RequestPolicy({
        clock: fakeClock,
        random: () => 0.5,
        sleep: async (milliseconds) => fakeClock.advance(milliseconds),
        onEvent: async (event) => events.push(event),
    });

    await policy.beforeRequest('html');
    await policy.beforeRequest('image');
    await policy.beforeRequest('challenge');

    assert.deepEqual(events, [
        { type: 'delay', kind: 'html', milliseconds: 180_000, at: 10_000 },
        { type: 'request', kind: 'html', at: 190_000 },
        { type: 'delay', kind: 'image', milliseconds: 90_000, at: 190_000 },
        { type: 'request', kind: 'image', at: 280_000 },
        { type: 'delay', kind: 'challenge', milliseconds: 15_000, at: 280_000 },
        { type: 'request', kind: 'challenge', at: 295_000 },
    ]);
});

test('success resets consecutive failures to the first backoff', async () => {
    const sleeps = [];
    const policy = new RequestPolicy({
        clock: createFakeClock(),
        random: () => 0,
        sleep: async (milliseconds) => sleeps.push(milliseconds),
    });

    await policy.recordFailure('http_403');
    policy.recordSuccess();

    assert.equal(await policy.recordFailure('http_429'), 'retry');
    assert.equal(sleeps.reduce((total, milliseconds) => total + milliseconds, 0), 240_000);
});
