import assert from 'node:assert/strict';
import test from 'node:test';

let initModalCloseDelegation;

try {
    ({ initModalCloseDelegation } = await import('../../resources/js/modal.js'));
} catch {
    // The first RED run proves the shared modal behavior does not exist yet.
}

const createHarness = () => {
    const listeners = new Map();
    const appended = [];
    const scheduledDelays = [];
    const popup = { style: { display: 'block' } };
    const modalClasses = [];
    const modal = {
        classList: {
            add: (name) => modalClasses.push(name),
            contains: (name) => name === 'modal',
        },
        querySelector(selector) {
            assert.equal(selector, '.modal__container > :not(.modal__close)');
            return popup;
        },
        removeCalled: false,
        remove() {
            this.removeCalled = true;
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
    const schedule = (callback, delay) => {
        scheduledDelays.push(delay);
        callback();
    };

    initModalCloseDelegation(documentRef, schedule);

    return {
        appended,
        documentRef,
        listener: listeners.get('click'),
        modal,
        modalClasses,
        popup,
        scheduledDelays,
    };
};

const createEvent = (target) => ({
    target,
    preventDefaultCalled: false,
    stopImmediatePropagationCalled: false,
    preventDefault() {
        this.preventDefaultCalled = true;
    },
    stopImmediatePropagation() {
        this.stopImmediatePropagationCalled = true;
    },
});

const assertClosed = (harness, event) => {
    assert.deepEqual(harness.modalClasses, ['fadeOut']);
    assert.deepEqual(harness.scheduledDelays, [450]);
    assert.deepEqual(harness.appended, [harness.popup]);
    assert.equal(harness.popup.style.display, '');
    assert.equal(harness.modal.removeCalled, true);
    assert.equal(harness.documentRef.body.style.overflow, '');
    assert.equal(harness.documentRef.body.style.paddingRight, '');
    assert.equal(harness.documentRef.documentElement.style.overflow, '');
    assert.equal(event.preventDefaultCalled, true);
    assert.equal(event.stopImmediatePropagationCalled, true);
};

test('delegated modal close restores popup content when its nested icon is clicked', () => {
    assert.equal(typeof initModalCloseDelegation, 'function');

    const harness = createHarness();
    const closeButton = {
        closest(selector) {
            assert.equal(selector, '.modal');
            return harness.modal;
        },
    };
    const nestedIcon = {
        closest(selector) {
            assert.equal(selector, '[data-modal-close], .modal__close');
            return closeButton;
        },
        classList: { contains: () => false },
    };
    const event = createEvent(nestedIcon);

    harness.listener(event);

    assertClosed(harness, event);
});

test('direct modal overlay click closes and restores the popup', () => {
    assert.equal(typeof initModalCloseDelegation, 'function');

    const harness = createHarness();
    const event = createEvent(harness.modal);

    harness.listener(event);

    assertClosed(harness, event);
});

test('click inside popup content leaves the modal open', () => {
    assert.equal(typeof initModalCloseDelegation, 'function');

    const harness = createHarness();
    const popupContent = {
        closest: () => null,
        classList: { contains: () => false },
    };
    const event = createEvent(popupContent);

    harness.listener(event);

    assert.deepEqual(harness.modalClasses, []);
    assert.deepEqual(harness.scheduledDelays, []);
    assert.deepEqual(harness.appended, []);
    assert.equal(harness.modal.removeCalled, false);
    assert.equal(harness.documentRef.body.style.overflow, 'hidden');
    assert.equal(harness.documentRef.body.style.paddingRight, '15px');
    assert.equal(harness.documentRef.documentElement.style.overflow, 'hidden');
    assert.equal(event.preventDefaultCalled, false);
    assert.equal(event.stopImmediatePropagationCalled, false);
});
