import assert from 'node:assert/strict';
import test from 'node:test';

let initModalCloseDelegation;

try {
    ({ initModalCloseDelegation } = await import('../../resources/js/modal.js'));
} catch {
    // The first RED run proves the shared modal behavior does not exist yet.
}

test('delegated modal close restores popup content when its nested icon is clicked', () => {
    assert.equal(typeof initModalCloseDelegation, 'function');

    const listeners = new Map();
    const appended = [];
    const popup = { style: { display: 'block' } };
    const modalClasses = [];
    const modal = {
        classList: { add: (name) => modalClasses.push(name) },
        querySelector: () => popup,
        removeCalled: false,
        remove() {
            this.removeCalled = true;
        },
    };
    const closeButton = { closest: () => modal };
    const nestedIcon = {
        closest(selector) {
            assert.equal(selector, '[data-modal-close], .modal__close');
            return closeButton;
        },
    };
    const documentRef = {
        body: {
            style: { overflow: 'hidden', paddingRight: '15px' },
            appendChild: (node) => appended.push(node),
        },
        documentElement: { style: { overflow: 'hidden' } },
        addEventListener(type, listener) {
            listeners.set(type, listener);
        },
    };
    const event = {
        target: nestedIcon,
        preventDefaultCalled: false,
        stopImmediatePropagationCalled: false,
        preventDefault() {
            this.preventDefaultCalled = true;
        },
        stopImmediatePropagation() {
            this.stopImmediatePropagationCalled = true;
        },
    };
    const schedule = (callback, delay) => {
        assert.equal(delay, 450);
        callback();
    };

    initModalCloseDelegation(documentRef, schedule);
    listeners.get('click')(event);

    assert.deepEqual(modalClasses, ['fadeOut']);
    assert.deepEqual(appended, [popup]);
    assert.equal(popup.style.display, '');
    assert.equal(modal.removeCalled, true);
    assert.equal(documentRef.body.style.overflow, '');
    assert.equal(documentRef.body.style.paddingRight, '');
    assert.equal(documentRef.documentElement.style.overflow, '');
    assert.equal(event.preventDefaultCalled, true);
    assert.equal(event.stopImmediatePropagationCalled, true);
});
