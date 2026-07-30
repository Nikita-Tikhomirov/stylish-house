import assert from 'node:assert/strict';
import test from 'node:test';

import {
    matchesNavigationQuery,
    syncDisclosure,
    toggleAccordionKey,
} from '../../resources/js/header-navigation.js';

test('matchesNavigationQuery ignores case and surrounding spaces', () => {
    assert.equal(matchesNavigationQuery('Рулонные шторы День-Ночь', ' день-ночь '), true);
    assert.equal(matchesNavigationQuery('Секционные ворота', 'жалюзи'), false);
});

test('toggleAccordionKey keeps at most one mobile group open', () => {
    assert.equal(toggleAccordionKey(null, 'story'), 'story');
    assert.equal(toggleAccordionKey('story', 'jaluzi'), 'jaluzi');
    assert.equal(toggleAccordionKey('story', 'story'), null);
});

test('syncDisclosure keeps aria and hidden state synchronized', () => {
    const attributes = {};
    const trigger = { setAttribute: (name, value) => { attributes[name] = value; } };
    const panel = { hidden: true };

    syncDisclosure(trigger, panel, true);
    assert.equal(attributes['aria-expanded'], 'true');
    assert.equal(panel.hidden, false);

    syncDisclosure(trigger, panel, false);
    assert.equal(attributes['aria-expanded'], 'false');
    assert.equal(panel.hidden, true);
});
