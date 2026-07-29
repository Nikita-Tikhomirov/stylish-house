let editorSequence = 0;

export function createNavigationNode(nodeType, placement = 'mega') {
    return {
        editorKey: `navigation-${Date.now()}-${editorSequence++}`,
        node_type: nodeType,
        placement,
        label: nodeType === 'tab' ? 'Новая вкладка' : nodeType === 'section' ? 'Новая колонка' : 'Новая ссылка',
        source_type: nodeType === 'section' ? null : 'custom',
        source_id: null,
        url: nodeType === 'section' ? null : '#',
        is_active: true,
        children: [],
    };
}

export function moveWithinSiblings(nodes, draggedKey, targetKey) {
    const from = nodes.findIndex((node) => node.editorKey === draggedKey);
    const to = nodes.findIndex((node) => node.editorKey === targetKey);
    if (from < 0 || to < 0 || from === to) return false;

    const [node] = nodes.splice(from, 1);
    nodes.splice(to, 0, node);
    return true;
}

export function stripEditorKeys(nodes) {
    return nodes.map(({ editorKey, children = [], ...node }) => ({
        ...node,
        children: stripEditorKeys(children),
    }));
}

function hydrateNode(node) {
    return {
        ...createNavigationNode(node.node_type || 'link', node.placement || 'mega'),
        ...node,
        editorKey: node.editorKey || `navigation-${Date.now()}-${editorSequence++}`,
        children: (node.children || []).map(hydrateNode),
    };
}

function findNode(nodes, key) {
    for (const node of nodes) {
        if (node.editorKey === key) return node;
        const child = findNode(node.children || [], key);
        if (child) return child;
    }
    return null;
}

function findSiblings(nodes, key) {
    if (nodes.some((node) => node.editorKey === key)) return nodes;
    for (const node of nodes) {
        const found = findSiblings(node.children || [], key);
        if (found) return found;
    }
    return null;
}

function removeNode(nodes, key) {
    const index = nodes.findIndex((node) => node.editorKey === key);
    if (index >= 0) {
        nodes.splice(index, 1);
        return true;
    }
    return nodes.some((node) => removeNode(node.children || [], key));
}

function escapeHtml(value = '') {
    return String(value).replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
    }[character]));
}

function sourceOptions(sources, type, selected) {
    if (!['category', 'subcategory', 'page'].includes(type)) return '';
    return (sources[type] || []).map((source) => (
        `<option value="${source.id}" ${String(source.id) === String(selected) ? 'selected' : ''}>${escapeHtml(source.title)}</option>`
    )).join('');
}

function nodeMarkup(node, sources) {
    const kinds = { tab: 'Вкладка', section: 'Колонка', link: 'Ссылка' };
    const canAdd = node.node_type === 'tab' || node.node_type === 'section';
    const childType = node.node_type === 'tab' ? 'section' : 'link';
    const sourceFields = node.node_type !== 'section' ? `
        <label>Источник
            <select data-field="source_type">
                <option value="custom" ${node.source_type === 'custom' ? 'selected' : ''}>Своя ссылка</option>
                <option value="category" ${node.source_type === 'category' ? 'selected' : ''}>Категория</option>
                <option value="subcategory" ${node.source_type === 'subcategory' ? 'selected' : ''}>Подкатегория</option>
                <option value="page" ${node.source_type === 'page' ? 'selected' : ''}>Страница</option>
            </select>
        </label>
        ${node.source_type === 'custom'
            ? `<label>Внутренний адрес<input type="text" data-field="url" value="${escapeHtml(node.url || '')}" placeholder="/shop-pages/kontakty"></label>`
            : `<label>Страница<select data-field="source_id"><option value="">Выберите</option>${sourceOptions(sources, node.source_type, node.source_id)}</select></label>`}
    ` : '<span></span><span></span>';

    return `
        <article class="navigation-node navigation-node--${node.node_type}" data-node-key="${node.editorKey}" draggable="true">
            <div class="navigation-node__head">
                <button type="button" class="navigation-node__drag" title="Перетащить" aria-label="Перетащить">⋮⋮</button>
                <span class="navigation-node__kind">${kinds[node.node_type]}</span>
                <input class="form-control navigation-node__label" type="text" data-field="label" value="${escapeHtml(node.label)}" aria-label="Название">
                <div class="navigation-node__controls">
                    <button type="button" data-move="up" title="Выше" aria-label="Переместить выше">↑</button>
                    <button type="button" data-move="down" title="Ниже" aria-label="Переместить ниже">↓</button>
                    <button type="button" class="is-danger" data-remove title="Удалить" aria-label="Удалить">×</button>
                </div>
            </div>
            <div class="navigation-node__fields">
                ${sourceFields}
                <label class="navigation-node__toggle"><input type="checkbox" data-field="is_active" ${node.is_active ? 'checked' : ''}> Показывать</label>
            </div>
            <div class="navigation-node__children" data-children>${(node.children || []).map((child) => nodeMarkup(child, sources)).join('')}</div>
            ${canAdd ? `<button type="button" class="navigation-node__add" data-add-child="${childType}">+ ${childType === 'section' ? 'Добавить колонку' : 'Добавить ссылку'}</button>` : ''}
        </article>`;
}

function previewMarkup(nodes) {
    const tabs = nodes.filter((node) => node.placement === 'mega' && node.node_type === 'tab' && node.is_active);
    if (!tabs.length) return '<div class="navigation-preview__empty">Добавьте первую вкладку каталога.</div>';
    const active = tabs[0];
    return `
        <div class="navigation-preview__tabs">${tabs.map((tab, index) => `<button type="button" class="navigation-preview__tab ${index === 0 ? 'is-active' : ''}">${escapeHtml(tab.label)}</button>`).join('')}</div>
        <div class="navigation-preview__columns">${(active.children || []).filter((section) => section.is_active).map((section) => `
            <section><h3>${escapeHtml(section.label)}</h3>${(section.children || []).filter((link) => link.is_active).map((link) => `<a href="#">${escapeHtml(link.label)}</a>`).join('')}</section>
        `).join('')}</div>`;
}

export function initNavigationEditor(root = document) {
    const editor = root.querySelector('[data-navigation-editor]');
    if (!editor) return;

    const initial = JSON.parse(editor.querySelector('[data-navigation-initial]').textContent || '[]');
    const sources = JSON.parse(editor.querySelector('[data-navigation-sources]').textContent || '{}');
    const tree = editor.querySelector('[data-navigation-tree]');
    const preview = editor.querySelector('[data-navigation-preview]');
    const payload = editor.querySelector('[data-navigation-payload]');
    const count = editor.querySelector('[data-navigation-count]');
    const state = initial.map(hydrateNode);
    let draggedKey = null;

    const render = () => {
        tree.innerHTML = state.map((node) => nodeMarkup(node, sources)).join('') || '<div class="navigation-preview__empty">Меню пока пустое.</div>';
        preview.innerHTML = previewMarkup(state);
        payload.value = JSON.stringify(stripEditorKeys(state));
        const total = JSON.stringify(state).match(/"editorKey"/g)?.length || 0;
        count.textContent = `Элементов: ${total}`;
    };

    editor.addEventListener('click', (event) => {
        const addRoot = event.target.closest('[data-add-root]');
        if (addRoot) {
            const placement = addRoot.dataset.addRoot;
            state.push(createNavigationNode(placement === 'mega' ? 'tab' : 'link', placement));
            render();
            return;
        }

        const nodeElement = event.target.closest('[data-node-key]');
        if (!nodeElement) return;
        const key = nodeElement.dataset.nodeKey;
        const node = findNode(state, key);
        const siblings = findSiblings(state, key);

        if (event.target.closest('[data-remove]')) {
            removeNode(state, key);
            render();
        } else if (event.target.closest('[data-add-child]')) {
            node.children.push(createNavigationNode(event.target.closest('[data-add-child]').dataset.addChild, node.placement));
            render();
        } else if (event.target.closest('[data-move]') && siblings) {
            const index = siblings.findIndex((item) => item.editorKey === key);
            const direction = event.target.closest('[data-move]').dataset.move === 'up' ? -1 : 1;
            const target = siblings[index + direction];
            if (target) moveWithinSiblings(siblings, key, target.editorKey);
            render();
        }
    });

    editor.addEventListener('input', (event) => {
        const nodeElement = event.target.closest('[data-node-key]');
        const field = event.target.dataset.field;
        if (!nodeElement || !field) return;
        const node = findNode(state, nodeElement.dataset.nodeKey);
        node[field] = event.target.type === 'checkbox' ? event.target.checked : event.target.value;
        payload.value = JSON.stringify(stripEditorKeys(state));
        preview.innerHTML = previewMarkup(state);
    });

    editor.addEventListener('change', (event) => {
        if (event.target.dataset.field === 'source_type') render();
    });

    editor.addEventListener('dragstart', (event) => {
        const node = event.target.closest('[data-node-key]');
        if (!node) return;
        draggedKey = node.dataset.nodeKey;
        node.classList.add('is-dragging');
    });

    editor.addEventListener('dragover', (event) => {
        const node = event.target.closest('[data-node-key]');
        if (!node || !draggedKey) return;
        event.preventDefault();
        node.classList.add('is-drop-target');
    });

    editor.addEventListener('dragleave', (event) => event.target.closest('[data-node-key]')?.classList.remove('is-drop-target'));
    editor.addEventListener('drop', (event) => {
        const target = event.target.closest('[data-node-key]');
        if (!target || !draggedKey) return;
        event.preventDefault();
        const siblings = findSiblings(state, draggedKey);
        if (siblings === findSiblings(state, target.dataset.nodeKey)) {
            moveWithinSiblings(siblings, draggedKey, target.dataset.nodeKey);
        }
        draggedKey = null;
        render();
    });

    editor.addEventListener('dragend', () => {
        draggedKey = null;
        editor.querySelectorAll('.is-dragging, .is-drop-target').forEach((element) => element.classList.remove('is-dragging', 'is-drop-target'));
    });

    editor.querySelector('[data-navigation-form]').addEventListener('submit', () => {
        payload.value = JSON.stringify(stripEditorKeys(state));
    });

    render();
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => initNavigationEditor());
}
