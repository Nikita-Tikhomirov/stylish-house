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
    assert.equal(await policy.recordFailure('challenge'), 'pause');
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
