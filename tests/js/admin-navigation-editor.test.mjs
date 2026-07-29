import assert from 'node:assert/strict';
import test from 'node:test';

import {
    createNavigationNode,
    moveWithinSiblings,
    stripEditorKeys,
} from '../../resources/js/admin-navigation-editor.js';

test('moveWithinSiblings only reorders nodes in the same visual group', () => {
    const nodes = [
        { editorKey: 'a', label: 'A' },
        { editorKey: 'b', label: 'B' },
        { editorKey: 'c', label: 'C' },
    ];

    assert.equal(moveWithinSiblings(nodes, 'c', 'a'), true);
    assert.deepEqual(nodes.map((node) => node.label), ['C', 'A', 'B']);
    assert.equal(moveWithinSiblings(nodes, 'missing', 'a'), false);
});

test('stripEditorKeys keeps the ordered menu payload without editor-only state', () => {
    const node = createNavigationNode('tab', 'mega');
    node.label = 'Жалюзи';
    node.children.push({
        ...createNavigationNode('section', 'mega'),
        label: 'По типу',
    });

    const payload = stripEditorKeys([node]);

    assert.equal(payload[0].label, 'Жалюзи');
    assert.equal(payload[0].children[0].label, 'По типу');
    assert.equal('editorKey' in payload[0], false);
    assert.equal('editorKey' in payload[0].children[0], false);
});
