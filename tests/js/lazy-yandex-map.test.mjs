import assert from 'node:assert/strict';
import test from 'node:test';

import {
    createMapObserver,
    yandexMapsUrl,
} from '../../resources/js/lazy-yandex-map.js';

test('yandexMapsUrl encodes the public API key and locale', () => {
    assert.equal(
        yandexMapsUrl('public key'),
        'https://api-maps.yandex.ru/2.1/?apikey=public%20key&lang=ru_RU'
    );
});

test('createMapObserver loads a map before it reaches the viewport', () => {
    let callback;
    let options;
    let observed;
    let disconnected = false;
    const Observer = class {
        constructor(handler, observerOptions) {
            callback = handler;
            options = observerOptions;
        }
        observe(element) { observed = element; }
        disconnect() { disconnected = true; }
    };
    const map = {};
    let loaded = false;
    const observer = createMapObserver(map, () => { loaded = true; }, Observer);

    assert.equal(observed, map);
    assert.equal(options.rootMargin, '600px 0px');
    callback([{ isIntersecting: true }]);
    assert.equal(loaded, true);
    assert.equal(disconnected, true);
    assert.ok(observer);
});
