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

test('third consecutive donor failure pauses after 2m, 5m, and 15m backoff', async () => {
    const sleeps = [];
    const fakeClock = createFakeClock();
    const policy = new RequestPolicy({
        clock: fakeClock,
        random: () => 0,
        sleep: async (milliseconds) => sleeps.push(milliseconds),
    });

    assert.equal(await policy.recordFailure('http_403'), 'retry');
    assert.equal(await policy.recordFailure('http_429'), 'retry');
    assert.equal(await policy.recordFailure('network'), 'pause');
    assert.deepEqual(sleeps, [120_000, 300_000, 900_000]);
});

test('rolling hour budget rejects request 121 without transport access', async () => {
    const fakeClock = createFakeClock();
    const policy = new RequestPolicy({
        clock: fakeClock,
        random: () => 0,
        sleep: async () => {},
    });

    for (let count = 0; count < 120; count += 1) {
        await policy.beforeRequest('html');
    }

    await assert.rejects(policy.beforeRequest('html'), /hourly request budget exhausted/);
    fakeClock.advance(3_600_001);
    await assert.doesNotReject(policy.beforeRequest('html'));
});

test('new policy process hydrates durable timestamps and rejects request 121', async () => {
    let durableState = null;
    const fakeClock = createFakeClock(50_000);
    const firstProcess = new RequestPolicy({
        clock: fakeClock,
        random: () => 0,
        sleep: async () => {},
        htmlDelayMs: [0, 0],
        persistState: async (state) => { durableState = structuredClone(state); },
    });
    for (let count = 0; count < 120; count += 1) {
        await firstProcess.beforeRequest('html');
    }

    const resumedProcess = new RequestPolicy({
        clock: fakeClock,
        random: () => 0,
        sleep: async () => {},
        htmlDelayMs: [0, 0],
        state: durableState,
    });

    await assert.rejects(resumedProcess.beforeRequest('html'), /hourly request budget exhausted/);
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

    assert.deepEqual(sleeps, [120_000, 300_000]);
    assert.equal(resumedProcess.snapshot().consecutiveFailures, 2);
});

test('new policy process waits the unfinished durable backoff before proceeding', async () => {
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
    const resumedProcess = new RequestPolicy({
        clock: fakeClock,
        sleep: async (milliseconds) => {
            resumedSleeps.push(milliseconds);
            fakeClock.advance(milliseconds);
        },
        state: durableState,
        persistState: async (state) => { durableState = structuredClone(state); },
    });
    await resumedProcess.resumePendingBackoff();

    assert.deepEqual(resumedSleeps, [120_000]);
    assert.equal(durableState.backoffUntil, null);
});

test('request delay uses the exact per-kind ranges before recording access', async () => {
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

    assert.deepEqual(events, [
        { type: 'delay', kind: 'html', milliseconds: 30_000, at: 10_000 },
        { type: 'request', kind: 'html', at: 40_000 },
        { type: 'delay', kind: 'image', milliseconds: 15_000, at: 40_000 },
        { type: 'request', kind: 'image', at: 55_000 },
    ]);
    assert.deepEqual(DEFAULT_LIMITS, {
        htmlDelayMs: [20_000, 40_000],
        imageDelayMs: [10_000, 20_000],
        hourlyLimit: 120,
        backoffMs: [120_000, 300_000, 900_000],
        concurrency: 1,
    });
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
    assert.deepEqual(sleeps, [120_000, 120_000]);
});
