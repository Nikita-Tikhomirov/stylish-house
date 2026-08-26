(() => {
    'use strict';

    const state = {
        token: '', runs: [], selectedRunId: null, pendingRunId: null, snapshot: null, pollTimer: null,
        productPage: 1, productPages: 1,
    };
    const statusCopy = {
        ready: ['Готов к запуску', 'Сбор подготовлен и может быть продолжен.'],
        running: ['Сбор выполняется', 'Chrome работает последовательно. Панель только читает локальное состояние.'],
        paused: ['Сбор на паузе', 'Продолжение начнётся с последнего сохранённого checkpoint.'],
        limited: ['Достигнут заданный лимит', 'Запуск можно продолжить с сохранённого места.'],
        completed: ['Сбор завершён', 'Все категории обработаны. Можно сформировать пакет для последующего импорта.'],
        stopped: ['Запуск остановлен', 'Этот запуск завершён навсегда и не может быть продолжен.'],
        invalid: ['Повреждённые данные запуска', 'Панель отказалась читать небезопасное или неполное состояние.'],
        starting: ['Запуск создаётся', 'Подготавливаю checkpoint и отдельный профиль Chrome.'],
    };
    const reasonCopy = {
        operator: 'Поставлено на паузу пользователем.',
        challenge: 'Сайт показал защитную проверку. Автоматические запросы остановлены.',
        http_403: 'Сайт временно запретил доступ (403). Запросы остановлены.',
        http_429: 'Сайт сообщил о слишком частых запросах (429). Запросы остановлены.',
        network: 'Нет соединения с сайтом. Можно продолжить позже.',
        timeout: 'Сайт не ответил вовремя. Можно продолжить позже.',
        'hourly-budget': 'Достигнут безопасный лимит запросов за час.',
        limit: 'Достигнут тестовый лимит запуска.',
    };
    const one = (selector) => document.querySelector(selector);
    const all = (selector) => [...document.querySelectorAll(selector)];
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"]/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;',
    }[char]));

    async function api(path, options = {}) {
        const request = { ...options, headers: { ...(options.headers || {}) } };
        if (request.method === 'POST') request.headers['X-Rimskie-Token'] = state.token;
        const response = await fetch(path, request);
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.error || `Локальный сервер ответил ${response.status}`);
        return data;
    }

    function statusLabel(status, reason) {
        if (status === 'paused' && reasonCopy[reason]) return reasonCopy[reason];
        return statusCopy[status]?.[0] || 'Состояние обновляется';
    }

    function statusClass(status) {
        if (status === 'running') return 'status-running';
        if (status === 'paused' || status === 'limited') return 'status-paused';
        if (status === 'completed') return 'status-completed';
        if (status === 'invalid' || status === 'stopped') return 'status-error';
        return 'status-idle';
    }

    function showNotice(message = '') {
        const notice = one('[data-role="notice"]');
        notice.textContent = message;
        notice.classList.toggle('notice-hidden', !message);
    }

    function renderRuns() {
        const container = one('[data-role="runs"]');
        if (!state.runs.length) {
            container.innerHTML = '<div class="empty-state">Запусков пока нет</div>';
            return;
        }
        container.innerHTML = state.runs.map((run) => {
            const warning = ['paused', 'limited'].includes(run.status) ? 'is-warning' : '';
            const error = ['invalid', 'stopped'].includes(run.status) ? 'is-error' : '';
            const progress = run.metrics
                ? `${run.metrics.completedCategories || 0} / ${run.metrics.categories || 46} · ${run.metrics.uniqueProducts || 0} товаров`
                : 'Требуется проверка';
            return `<button class="run-item ${run.id === state.selectedRunId ? 'is-active' : ''}" data-run-id="${escapeHtml(run.id)}">
                <strong>${escapeHtml(run.id)}</strong>
                <span class="run-state ${warning} ${error}">${escapeHtml(statusLabel(run.status, run.pauseReason))}</span>
                <span>${escapeHtml(progress)}</span>
            </button>`;
        }).join('');
        all('[data-run-id]').forEach((button) => button.addEventListener('click', () => selectRun(button.dataset.runId)));
    }

    function renderEmpty() {
        state.snapshot = null;
        one('[data-role="run-id"]').textContent = 'Запуск не выбран';
        one('[data-role="status-title"]').textContent = 'Можно начинать полный сбор';
        one('[data-role="status-detail"]').textContent = 'Будут последовательно обработаны все страницы 46 категорий. Уже сохранённые товары не скачиваются повторно.';
        one('[data-role="categories"]').innerHTML = '<tr><td colspan="4" class="empty-cell">Запусков пока нет</td></tr>';
        updateButtons();
    }

    function renderStarting(runId) {
        state.snapshot = null;
        const badge = one('[data-role="status-badge"]');
        badge.className = 'status-badge status-running';
        badge.textContent = 'Подготовка';
        one('[data-role="run-id"]').textContent = runId;
        one('[data-role="status-title"]').textContent = 'Запуск создаётся';
        one('[data-role="status-detail"]').textContent = 'Подготавливаю checkpoint и отдельный профиль Chrome. Панель обновится автоматически.';
        one('[data-role="current-source"]').textContent = 'Подготовка первой категории';
        updateButtons();
    }

    function renderSnapshot(snapshot) {
        const metrics = snapshot.metrics || {};
        const categories = metrics.categories || 46;
        const completed = metrics.completedCategories || 0;
        const percent = Math.min(100, Math.round((completed / categories) * 100));
        const copy = statusCopy[snapshot.status] || ['Состояние обновляется', 'Читаю сохранённые данные запуска.'];
        const badge = one('[data-role="status-badge"]');
        badge.className = `status-badge ${statusClass(snapshot.status)}`;
        badge.textContent = statusLabel(snapshot.status, snapshot.pauseReason);
        one('[data-role="run-id"]').textContent = snapshot.id;
        one('[data-role="status-title"]').textContent = copy[0];
        one('[data-role="status-detail"]').textContent = reasonCopy[snapshot.pauseReason] || copy[1];
        one('[data-role="progress-bar"]').style.width = `${percent}%`;
        one('[data-role="progress-copy"]').textContent = `${completed} из ${categories}`;
        one('[data-role="current-source"]').textContent = snapshot.currentSource
            ? `Сейчас: ${snapshot.currentSource.label}, страница ${Math.max(1, snapshot.currentSource.pages + 1)}`
            : 'Очередь категорий завершена';
        one('[data-metric="categories"]').textContent = `${completed} / ${categories}`;
        one('[data-metric="pages"]').textContent = metrics.pages || 0;
        one('[data-metric="products"]').textContent = metrics.uniqueProducts || 0;
        one('[data-metric="images"]').textContent = metrics.images || 0;
        one('[data-metric="memberships"]').textContent = metrics.memberships || 0;
        one('[data-metric="requests"]').textContent = metrics.requests || 0;
        one('[data-role="request-budget"]').textContent = `${metrics.requestsLastHour || 0} из ${metrics.hourlyLimit || 20} за последний час`;
        one('[data-role="next-request"]').textContent = snapshot.nextRequestAt
            ? `Следующий запрос не раньше: ${new Date(snapshot.nextRequestAt).toLocaleTimeString('ru-RU')}`
            : 'Следующий запрос: после команды запуска';
        one('[data-role="last-url"]').textContent = `Последний URL: ${snapshot.lastUrl || '—'}`;
        one('[data-role="category-count"]').textContent = `${categories} категорий`;
        renderCategories(snapshot.sources || [], snapshot.currentSource?.slug);
        renderEvents(snapshot.events || []);
        updateButtons();
    }

    function renderCategories(sources, currentSlug) {
        const body = one('[data-role="categories"]');
        if (!sources.length) {
            body.innerHTML = '<tr><td colspan="4" class="empty-cell">Данные категорий ещё не созданы</td></tr>';
            return;
        }
        body.innerHTML = sources.map((source) => {
            const current = source.slug === currentSlug && source.status !== 'completed';
            const status = source.status === 'completed' ? 'Завершено' : current ? 'В работе' : 'Ожидает';
            const className = source.status === 'completed' ? 'is-complete' : current ? 'is-current' : '';
            return `<tr><td><strong>${escapeHtml(source.label)}</strong></td><td>${source.pages || 0}</td><td>${source.pendingProducts || 0}</td><td><span class="category-status ${className}">${status}</span></td></tr>`;
        }).join('');
    }

    function priceText(price) {
        const amount = typeof price === 'object' ? price?.amount : price;
        if (amount === null || amount === undefined || amount === '') return 'Цена не указана';
        return `${new Intl.NumberFormat('ru-RU').format(Number(amount))} ₽ у донора`;
    }

    function renderProducts(result) {
        const products = result.items || [];
        state.productPage = result.page || 1;
        state.productPages = result.pages || 1;
        one('[data-role="product-count"]').textContent = result.total || 0;
        one('[data-role="product-page"]').textContent = `Страница ${state.productPage} из ${state.productPages}`;
        one('[data-page-action="previous"]').disabled = state.productPage <= 1;
        one('[data-page-action="next"]').disabled = state.productPage >= state.productPages;
        one('[data-role="products"]').innerHTML = products.length ? products.map((product) => `
            <article class="product-card">
                <img src="${escapeHtml(product.imageUrl)}" alt="" loading="lazy">
                <div><strong>${escapeHtml(product.name)}</strong><span>ID ${escapeHtml(product.externalId)}</span><span class="product-price">${escapeHtml(priceText(product.sourcePrice))}</span><span>${product.categories.length} категорий</span></div>
            </article>`).join('') : '<div class="empty-state">Товары появятся здесь после сохранения</div>';
    }

    function renderEvents(events) {
        one('[data-role="event-count"]').textContent = events.length;
        one('[data-role="events"]').innerHTML = events.length ? [...events].reverse().map((event) => `
            <div class="event-row"><time>${escapeHtml(event.at ? new Date(event.at).toLocaleString('ru-RU') : 'Без времени')}</time><strong>${escapeHtml(event.type || 'событие')}</strong><span>${escapeHtml(reasonCopy[event.reason] || event.reason || event.url || 'Состояние обновлено')}</span></div>`).join('') : '<div class="empty-state">Событий пока нет</div>';
    }

    function updateButtons() {
        const status = state.snapshot?.status;
        const selected = Boolean(state.selectedRunId);
        one('[data-action="open-folder"]').disabled = !selected || state.pendingRunId === state.selectedRunId;
        one('[data-action="pause"]').disabled = status !== 'running';
        one('[data-action="resume"]').disabled = !['paused', 'limited', 'ready'].includes(status);
        one('[data-action="stop"]').disabled = !selected || ['completed', 'stopped', 'invalid'].includes(status);
        one('[data-action="export"]').disabled = status !== 'completed';
    }

    async function loadSelectedRun() {
        if (!state.selectedRunId) return renderEmpty();
        const runId = encodeURIComponent(state.selectedRunId);
        const [snapshot, products] = await Promise.all([
            api(`/api/runs/${runId}`), api(`/api/runs/${runId}/products?page=${state.productPage}&perPage=24`),
        ]);
        state.snapshot = snapshot;
        renderSnapshot(snapshot);
        renderProducts(products);
    }

    async function selectRun(runId) {
        state.selectedRunId = runId;
        state.productPage = 1;
        renderRuns();
        showNotice();
        try { await loadSelectedRun(); } catch (error) { showNotice(error.message); }
    }

    async function refresh() {
        try {
            const result = await api('/api/runs');
            state.runs = result.runs || [];
            if (state.pendingRunId && !state.runs.some((run) => run.id === state.pendingRunId)) {
                state.runs.unshift({ id: state.pendingRunId, status: 'starting' });
                renderRuns();
                renderStarting(state.pendingRunId);
                showNotice();
                return;
            }
            if (state.pendingRunId) state.pendingRunId = null;
            if (state.selectedRunId && !state.runs.some((run) => run.id === state.selectedRunId)) state.selectedRunId = state.runs[0]?.id || null;
            renderRuns();
            await loadSelectedRun();
            showNotice();
        } catch (error) {
            showNotice(error.message);
        } finally {
            clearTimeout(state.pollTimer);
            state.pollTimer = setTimeout(refresh, state.snapshot?.status === 'running' ? 2000 : 6000);
        }
    }

    async function mutate(action) {
        showNotice();
        try {
            if (action === 'start') {
                const result = await api('/api/runs', { method: 'POST' });
                state.selectedRunId = result.runId;
                state.pendingRunId = result.runId;
                state.runs = [{ id: result.runId, status: 'starting' }, ...state.runs];
                renderRuns();
                renderStarting(result.runId);
                clearTimeout(state.pollTimer);
                state.pollTimer = setTimeout(refresh, 800);
                return;
            } else {
                if (!state.selectedRunId) return;
                if (action === 'stop' && !confirm('Остановить этот запуск навсегда? Продолжить его будет нельзя.')) return;
                await api(`/api/runs/${encodeURIComponent(state.selectedRunId)}/${action}`, { method: 'POST' });
            }
            await new Promise((resolve) => setTimeout(resolve, 180));
            await refresh();
        } catch (error) { showNotice(error.message); }
    }

    function bindActions() {
        all('[data-action]').forEach((button) => button.addEventListener('click', () => button.dataset.action === 'refresh' ? refresh() : mutate(button.dataset.action)));
        all('[data-tab]').forEach((button) => button.addEventListener('click', () => {
            all('[data-tab]').forEach((tab) => tab.classList.toggle('is-active', tab === button));
            all('[data-panel]').forEach((panel) => panel.classList.toggle('is-hidden', panel.dataset.panel !== button.dataset.tab));
        }));
        all('[data-page-action]').forEach((button) => button.addEventListener('click', async () => {
            const delta = button.dataset.pageAction === 'next' ? 1 : -1;
            state.productPage = Math.min(state.productPages, Math.max(1, state.productPage + delta));
            try { await loadSelectedRun(); } catch (error) { showNotice(error.message); }
        }));
    }

    async function bootstrap() {
        bindActions();
        try {
            const data = await api('/api/bootstrap');
            state.token = data.sessionToken;
            state.runs = data.runs || [];
            state.selectedRunId = state.runs[0]?.id || null;
            one('[data-role="data-root"]').textContent = data.dataRoot;
            renderRuns();
            await loadSelectedRun();
            state.pollTimer = setTimeout(refresh, state.snapshot?.status === 'running' ? 2000 : 6000);
        } catch (error) { showNotice(error.message); renderEmpty(); }
    }

    window.rimskieGuiReady = bootstrap();
})();
