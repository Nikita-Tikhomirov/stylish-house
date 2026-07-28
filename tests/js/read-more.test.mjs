import assert from 'node:assert/strict';
import test from 'node:test';

import {
    initReadMore,
    toggleReadMore,
} from '../../resources/js/read-more.js';

const createFixture = ({ scrollHeight = 120, clientHeight = 48 } = {}) => {
    const attributes = new Map([['aria-expanded', 'false']]);
    const root = { classList: { toggle() {} } };
    const content = { scrollHeight, clientHeight };
    const button = {
        hidden: false,
        textContent: 'Подробнее',
        closest: () => root,
        getAttribute: (name) => attributes.get(name),
        setAttribute: (name, value) => attributes.set(name, value),
        addEventListener(type, listener) {
            this.listener = listener;
        },
    };

    root.querySelector = (selector) => selector === '[data-read-more-content]' ? content : null;

    return { root, content, button, attributes };
};

test('toggleReadMore expands and collapses the description accessibly', () => {
    const { button, attributes } = createFixture();

    toggleReadMore(button);
    assert.equal(attributes.get('aria-expanded'), 'true');
    assert.equal(button.textContent, 'Скрыть');

    toggleReadMore(button);
    assert.equal(attributes.get('aria-expanded'), 'false');
    assert.equal(button.textContent, 'Подробнее');
});

test('initReadMore hides the button when the description is not truncated', () => {
    const { button } = createFixture({ scrollHeight: 48, clientHeight: 48 });
    const scope = { querySelectorAll: () => [button] };

    initReadMore(scope);

    assert.equal(button.hidden, true);
});
