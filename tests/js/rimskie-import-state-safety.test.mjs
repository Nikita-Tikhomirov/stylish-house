import assert from 'node:assert/strict';
import test from 'node:test';

import { Collector } from '../../scripts/rimskie-import/lib/collector.mjs';
import { RequestPolicy } from '../../scripts/rimskie-import/lib/request-policy.mjs';

const sourceUrl = 'https://rimskie.com/catalog/rimskie-shtory/white';

function initialState() {
    return {
        status: 'ready',
        requestCount: 0,
        completedProductIds: [],
        sources: [{
            label: 'White',
            sourceSlug: 'white',
            sourceUrl,
            nextPageUrl: sourceUrl,
            pendingProducts: [],
            completed: false,
            pages: 0,
        }],
    };
}

function policy() {
    return new RequestPolicy({
        htmlDelayMs: [0, 0],
        imageDelayMs: [0, 0],
        backoffMs: [0, 0, 0],
        sleep: async () => {},
        onEvent: async () => {},
    });
}

function memoryStore(state, { failEvents = false } = {}) {
    const calls = [];
    let durableState = structuredClone(state);

    return {
        calls,
        async readState() {
            return structuredClone(durableState);
        },
        async checkpoint(nextState) {
            durableState = structuredClone(nextState);
            calls.push(['checkpoint', nextState.status, nextState.pauseReason]);
        },
        async appendEvent(event) {
            calls.push(['event', event.type, event.reason || event.kind]);
            if (failEvents === true || (typeof failEvents === 'function' && failEvents(event))) {
                throw new Error('simulated event log failure');
            }
        },
        async saveSource() {},
        async readProduct() { return null; },
        state() {
            return structuredClone(durableState);
        },
    };
}

test('challenge state is durable before diagnostics and survives event-log failure', async () => {
    const store = memoryStore(initialState(), {
        failEvents: (event) => event.type === 'challenge' || event.type === 'pause',
    });
    const transport = {
        calls: 0,
        async getHtml() {
            this.calls += 1;
            throw Object.assign(new Error('visible full CAPTCHA'), {
                kind: 'challenge',
                manual: true,
            });
        },
    };

    const result = await new Collector().run({ store, transport, policy: policy() });

    assert.equal(result.status, 'paused');
    assert.equal(result.pauseReason, 'challenge');
    assert.equal(store.state().status, 'paused');
    assert.equal(store.state().pauseReason, 'challenge');
    const challengeEventIndex = store.calls.findIndex((call) => call[0] === 'event' && call[1] === 'challenge');
    const pauseCheckpointIndex = store.calls.findIndex((call) => call[0] === 'checkpoint'
        && call[1] === 'paused' && call[2] === 'challenge');
    assert.ok(pauseCheckpointIndex >= 0);
    assert.ok(challengeEventIndex > pauseCheckpointIndex);
    assert.equal(transport.calls, 1);
});

test('collector pauses for a manual challenge without enabling legacy challenge mode', async () => {
    const store = memoryStore(initialState());
    const observations = [];
    const transport = {
        async getHtml() {
            throw Object.assign(new Error('visible BotHunt challenge'), {
                kind: 'challenge',
                manual: true,
                url: sourceUrl,
            });
        },
        async enableChallengeMode(url) {
            observations.push([store.state().status, store.state().pauseReason, url]);
        },
    };

    const result = await new Collector().run({ store, transport, policy: policy() });

    assert.equal(result.status, 'paused');
    assert.deepEqual(observations, []);
});

test('persisted challenge pause requires explicit resume acknowledgement', async () => {
    const paused = initialState();
    paused.status = 'paused';
    paused.pauseReason = 'challenge';
    const store = memoryStore(paused);
    const transport = {
        calls: 0,
        async getHtml() {
            this.calls += 1;
            return '<!doctype html><html><body></body></html>';
        },
    };
    const collector = new Collector();

    const blocked = await collector.run({ store, transport, policy: policy() });

    assert.equal(blocked.status, 'paused');
    assert.equal(blocked.pauseReason, 'challenge');
    assert.equal(transport.calls, 0);

    const resumed = await collector.run({
        store, transport, policy: policy(), acknowledgeChallenge: true,
    });
    assert.equal(resumed.status, 'completed');
    assert.equal(transport.calls, 1);
});

test('error is terminal until explicitly acknowledged by resume', async () => {
    const store = memoryStore(initialState(), {
        failEvents: (event) => event.type === 'error',
    });
    const transport = {
        calls: 0,
        async getHtml() {
            this.calls += 1;
            if (this.calls === 1) {
                throw Object.assign(new Error('invalid donor response'), { kind: 'invalid_html' });
            }
            return '<!doctype html><html><body></body></html>';
        },
    };
    const collector = new Collector();

    const failed = await collector.run({ store, transport, policy: policy() });
    const blocked = await collector.run({ store, transport, policy: policy() });

    assert.equal(failed.status, 'error');
    assert.equal(blocked.status, 'error');
    assert.equal(transport.calls, 1);
    const errorCheckpointIndex = store.calls.findIndex((call) => call[0] === 'checkpoint'
        && call[1] === 'error');
    const errorEventIndex = store.calls.findIndex((call) => call[0] === 'event' && call[1] === 'error');
    assert.ok(errorCheckpointIndex >= 0);
    assert.ok(errorEventIndex > errorCheckpointIndex);

    const resumed = await collector.run({
        store,
        transport,
        policy: policy(),
        acknowledgeError: true,
    });

    assert.equal(resumed.status, 'completed');
    assert.equal(transport.calls, 2);
});

test('operator stop is checkpointed before its event is appended', async () => {
    const store = memoryStore(initialState(), { failEvents: true });
    const transport = {
        calls: 0,
        async getHtml() {
            this.calls += 1;
            return '<!doctype html><html><body></body></html>';
        },
    };

    const result = await new Collector().run({
        store,
        transport,
        policy: policy(),
        control: { read: async () => ({ pause: false, stop: true }) },
    });

    assert.equal(result.status, 'stopped');
    assert.equal(store.state().status, 'stopped');
    const stopCheckpointIndex = store.calls.findIndex((call) => call[0] === 'checkpoint'
        && call[1] === 'stopped');
    const stopEventIndex = store.calls.findIndex((call) => call[0] === 'event' && call[1] === 'stop');
    assert.ok(stopCheckpointIndex >= 0);
    assert.ok(stopEventIndex > stopCheckpointIndex);
    assert.equal(transport.calls, 0);
});

test('failed delay event durably errors the run before transport access', async () => {
    const store = memoryStore(initialState(), { failEvents: true });
    const transport = {
        calls: 0,
        async getHtml() {
            this.calls += 1;
            return '<!doctype html><html><body></body></html>';
        },
    };

    const result = await new Collector().run({ store, transport, policy: policy() });

    assert.equal(result.status, 'error');
    assert.equal(store.state().status, 'error');
    assert.equal(store.state().eventLogError.eventType, 'delay');
    assert.equal(transport.calls, 0);
});
