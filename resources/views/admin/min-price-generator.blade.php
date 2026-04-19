<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<style>
    .mpg-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 12px;
    }

    .mpg-col-3 {
        grid-column: span 3;
    }

    .mpg-col-4 {
        grid-column: span 4;
    }

    .mpg-col-6 {
        grid-column: span 6;
    }

    .mpg-col-12 {
        grid-column: span 12;
    }

    .mpg-progress {
        width: 100%;
        height: 14px;
        border-radius: 999px;
        background: #e9ecef;
        overflow: hidden;
    }

    .mpg-progress__bar {
        height: 100%;
        width: 0%;
        background: #28a745;
        transition: width .2s linear;
    }

    .mpg-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .mpg-muted {
        color: #6c757d;
        font-size: 12px;
    }

    @media (max-width: 1024px) {
        .mpg-col-3, .mpg-col-4, .mpg-col-6, .mpg-col-12 {
            grid-column: span 12;
        }
    }
</style>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" tabindex="-1">
            <h1 class="section-title">Оператор обновления минимальных цен</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Параметры запуска</h5>
            <div class="card-body">
                <div class="mpg-grid">
                    <div class="form-group mpg-col-4">
                        <label for="category_id">Категория</label>
                        <select id="category_id" class="form-control">
                            <option value="">Все категории</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->titleh1 }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mpg-col-4">
                        <label for="subcategory_id">Подкатегория</label>
                        <select id="subcategory_id" class="form-control">
                            <option value="">Все подкатегории</option>
                            @foreach($subcategories as $subcategory)
                                <option value="{{ $subcategory->id }}" data-category-id="{{ $subcategory->category_id }}">
                                    {{ $subcategory->titleh1 }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mpg-col-4">
                        <label for="batch_size">Размер пакета</label>
                        <input id="batch_size" type="number" class="form-control" value="{{ $activeRun?->batch_size ?? 200 }}"
                            min="25" max="1000">
                    </div>

                    <div class="form-group mpg-col-6">
                        <label for="model_ids">Модели (множественный выбор)</label>
                        <select id="model_ids" class="form-control" multiple size="8">
                            @foreach($models as $model)
                                <option value="{{ $model->id }}">{{ $model->title }}</option>
                            @endforeach
                        </select>
                        <div class="mpg-muted">Ctrl/Cmd + click для множественного выбора</div>
                    </div>

                    <div class="mpg-col-6 mpg-grid">
                        <div class="form-group mpg-col-6">
                            <label for="start_id">Start ID (с какого)</label>
                            <input id="start_id" type="number" class="form-control" min="1" placeholder="например 1000">
                        </div>
                        <div class="form-group mpg-col-6">
                            <label for="end_id">End ID (по какой)</label>
                            <input id="end_id" type="number" class="form-control" min="1" placeholder="например 5000">
                        </div>

                        <div class="form-group mpg-col-6">
                            <label for="mode">Режим</label>
                            <select id="mode" class="form-control">
                                <option value="manual">Manual</option>
                                <option value="auto">Auto</option>
                            </select>
                        </div>
                        <div class="form-group mpg-col-6">
                            <label>&nbsp;</label>
                            <div class="d-flex" style="gap:14px;align-items:center;">
                                <label class="mb-0"><input id="skip_filled" type="checkbox" checked> Skip filled</label>
                                <label class="mb-0"><input id="overwrite_existing" type="checkbox"> Force overwrite</label>
                            </div>
                        </div>

                        <div class="mpg-col-12 mpg-actions">
                            <button id="start_run" class="btn btn-primary" type="button">Start</button>
                            <button id="next_batch" class="btn btn-success" type="button">Next batch</button>
                            <button id="pause_run" class="btn btn-warning" type="button">Pause</button>
                            <button id="resume_run" class="btn btn-info" type="button">Resume</button>
                            <button id="stop_run" class="btn btn-danger" type="button">Stop</button>
                            <button id="clone_params" class="btn btn-outline-secondary" type="button">Клонировать параметры run</button>
                            <button id="refresh_state" class="btn btn-outline-secondary" type="button">Обновить состояние</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Прогресс и состояние</h5>
            <div class="card-body">
                <div class="mpg-grid">
                    <div class="mpg-col-3"><strong>Run ID:</strong> <span id="run_id">{{ $activeRun?->id ?? '-' }}</span></div>
                    <div class="mpg-col-3"><strong>Статус:</strong> <span id="run_status">{{ $activeRun?->status ?? 'нет активного run' }}</span></div>
                    <div class="mpg-col-3"><strong>Mode:</strong> <span id="run_mode">{{ $activeRun?->mode ?? '-' }}</span></div>
                    <div class="mpg-col-3"><strong>Текущий ID:</strong> <span id="run_current_id">{{ $activeRun?->current_id ?? 0 }}</span></div>

                    <div class="mpg-col-3"><strong>Processed:</strong> <span id="run_processed">{{ $activeRun?->processed ?? 0 }}</span></div>
                    <div class="mpg-col-3"><strong>Updated:</strong> <span id="run_updated">{{ $activeRun?->updated ?? 0 }}</span></div>
                    <div class="mpg-col-3"><strong>Skipped:</strong> <span id="run_skipped">{{ $activeRun?->skipped ?? 0 }}</span></div>
                    <div class="mpg-col-3"><strong>Total:</strong> <span id="run_total">{{ $activeRun?->total_candidates ?? 0 }}</span></div>

                    <div class="mpg-col-12"><strong>Диапазон run:</strong> <span id="run_range_target">-</span> | <strong>Фактически обработано:</strong> <span id="run_range_processed">-</span></div>
                    <div class="mpg-col-12"><strong>ETA:</strong> <span id="run_eta">-</span></div>
                    <div class="mpg-col-12">
                        <strong>Последние ошибки:</strong>
                        <div id="run_last_errors" class="mpg-muted">-</div>
                    </div>
                    <div class="mpg-col-12">
                        <div class="mpg-progress">
                            <div id="run_progress_bar" class="mpg-progress__bar"></div>
                        </div>
                        <div class="mpg-muted mt-1">Progress: <span id="run_progress_percent">{{ $activeRun?->progress_percent ?? 0 }}</span>%</div>
                    </div>
                    <div class="mpg-col-12" id="status_message"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">История запусков</h5>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Status</th>
                                <th>Mode</th>
                                <th>Range</th>
                                <th>Processed</th>
                                <th>Updated</th>
                                <th>Skipped</th>
                                <th>Started</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="runs_table_body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Результаты по товарам</h5>
            <div class="card-body">
                <div class="mpg-grid">
                    <div class="form-group mpg-col-3">
                        <label for="result_status">Статус</label>
                        <select id="result_status" class="form-control">
                            <option value="">Все</option>
                            <option value="updated">updated</option>
                            <option value="skipped">skipped</option>
                            <option value="error">error</option>
                        </select>
                    </div>
                    <div class="form-group mpg-col-6">
                        <label for="result_query">Поиск (ID или название)</label>
                        <input id="result_query" type="text" class="form-control" placeholder="например 1234 или Римские">
                    </div>
                    <div class="form-group mpg-col-3">
                        <label>&nbsp;</label>
                        <div class="mpg-actions">
                            <button id="load_results" class="btn btn-outline-primary" type="button">Загрузить</button>
                            <button id="export_results" class="btn btn-outline-success" type="button">CSV export</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mt-2">
                    <table class="table table-sm table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>product_id</th>
                                <th>title</th>
                                <th>status</th>
                                <th>old</th>
                                <th>new</th>
                                <th>error_code</th>
                                <th>error_message</th>
                                <th>processed_at</th>
                            </tr>
                        </thead>
                        <tbody id="results_table_body"></tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="mpg-muted" id="results_meta">-</div>
                    <div class="mpg-actions">
                        <button id="prev_page" class="btn btn-sm btn-light" type="button">Prev</button>
                        <button id="next_page" class="btn btn-sm btn-light" type="button">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const categorySelect = document.getElementById('category_id');
        const subcategorySelect = document.getElementById('subcategory_id');
        const modelSelect = document.getElementById('model_ids');
        const batchSizeInput = document.getElementById('batch_size');
        const startIdInput = document.getElementById('start_id');
        const endIdInput = document.getElementById('end_id');
        const modeSelect = document.getElementById('mode');
        const skipFilledInput = document.getElementById('skip_filled');
        const overwriteInput = document.getElementById('overwrite_existing');

        const runIdNode = document.getElementById('run_id');
        const runStatusNode = document.getElementById('run_status');
        const runModeNode = document.getElementById('run_mode');
        const runCurrentIdNode = document.getElementById('run_current_id');
        const runProcessedNode = document.getElementById('run_processed');
        const runUpdatedNode = document.getElementById('run_updated');
        const runSkippedNode = document.getElementById('run_skipped');
        const runTotalNode = document.getElementById('run_total');
        const runProgressNode = document.getElementById('run_progress_percent');
        const runProgressBar = document.getElementById('run_progress_bar');
        const runRangeTargetNode = document.getElementById('run_range_target');
        const runRangeProcessedNode = document.getElementById('run_range_processed');
        const runEtaNode = document.getElementById('run_eta');
        const runLastErrorsNode = document.getElementById('run_last_errors');
        const statusMessageNode = document.getElementById('status_message');

        const runsTableBody = document.getElementById('runs_table_body');
        const resultsTableBody = document.getElementById('results_table_body');
        const resultsMetaNode = document.getElementById('results_meta');
        const resultStatusInput = document.getElementById('result_status');
        const resultQueryInput = document.getElementById('result_query');

        const startButton = document.getElementById('start_run');
        const nextButton = document.getElementById('next_batch');
        const pauseButton = document.getElementById('pause_run');
        const resumeButton = document.getElementById('resume_run');
        const stopButton = document.getElementById('stop_run');
        const cloneButton = document.getElementById('clone_params');
        const refreshStateButton = document.getElementById('refresh_state');
        const loadResultsButton = document.getElementById('load_results');
        const exportResultsButton = document.getElementById('export_results');
        const prevPageButton = document.getElementById('prev_page');
        const nextPageButton = document.getElementById('next_page');

        const allSubcategoryOptions = Array.from(subcategorySelect.querySelectorAll('option'));

        let autoTimer = null;
        let currentRun = null;
        let resultsPage = 1;
        let resultsLastPage = 1;

        function updateSubcategoryOptions() {
            const categoryId = categorySelect.value;
            const prev = subcategorySelect.value;
            subcategorySelect.innerHTML = '';

            allSubcategoryOptions.forEach(option => {
                if (!option.value) {
                    subcategorySelect.appendChild(option.cloneNode(true));
                    return;
                }
                if (!categoryId || option.dataset.categoryId === categoryId) {
                    const clone = option.cloneNode(true);
                    if (clone.value === prev) {
                        clone.selected = true;
                    }
                    subcategorySelect.appendChild(clone);
                }
            });
        }

        function selectedModelIds() {
            return Array.from(modelSelect.selectedOptions).map(option => parseInt(option.value, 10));
        }

        function setStatusMessage(text, isError = false) {
            statusMessageNode.textContent = text || '';
            statusMessageNode.style.color = isError ? '#dc3545' : '#198754';
        }

        function boolToText(value) {
            return value ? 'yes' : 'no';
        }

        function formatEta(seconds) {
            if (!seconds || seconds < 1) return '-';
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = Math.floor(seconds % 60);
            if (h > 0) return `${h}h ${m}m ${s}s`;
            if (m > 0) return `${m}m ${s}s`;
            return `${s}s`;
        }

        function targetRangeText(run) {
            const from = run.start_id ?? '-';
            const to = run.end_id ?? '∞';
            return `${from} .. ${to}`;
        }

        function processedRangeText(range) {
            if (!range || range.from === null || range.to === null) return '-';
            return `${range.from} .. ${range.to}`;
        }

        function updateButtonsByState() {
            const status = currentRun?.status;
            const isRunning = status === 'running';
            const isPaused = status === 'paused';
            const hasRun = !!currentRun?.id;

            startButton.disabled = isRunning || isPaused;
            nextButton.disabled = !hasRun || !isRunning;
            pauseButton.disabled = !hasRun || !isRunning;
            resumeButton.disabled = !hasRun || !isPaused;
            stopButton.disabled = !hasRun || (status !== 'running' && status !== 'paused');
        }

        function setRunFormFromRun(run) {
            categorySelect.value = run.category_id || '';
            updateSubcategoryOptions();
            subcategorySelect.value = run.subcategory_id || '';
            batchSizeInput.value = run.batch_size || 200;
            startIdInput.value = run.start_id || '';
            endIdInput.value = run.end_id || '';
            modeSelect.value = run.mode || 'manual';
            skipFilledInput.checked = !!run.skip_filled;
            overwriteInput.checked = !!run.overwrite_existing;

            const selected = new Set((run.model_ids || []).map(id => parseInt(id, 10)));
            Array.from(modelSelect.options).forEach(option => {
                option.selected = selected.has(parseInt(option.value, 10));
            });
        }

        function renderLastErrors(errors) {
            if (!Array.isArray(errors) || errors.length === 0) {
                runLastErrorsNode.textContent = '-';
                return;
            }

            runLastErrorsNode.innerHTML = errors
                .map(error => `#${error.product_id}: ${error.error_code || 'error'} (${error.error_message || 'no message'})`)
                .join('<br>');
        }

        function renderRunState(statePayload) {
            const run = statePayload.run || statePayload;
            const rangeProcessed = statePayload.range_processed || null;
            const lastErrors = statePayload.last_errors || [];

            currentRun = run;
            runIdNode.textContent = run?.id ?? '-';
            runStatusNode.textContent = run?.status ?? '-';
            runModeNode.textContent = run?.mode ?? '-';
            runCurrentIdNode.textContent = run?.current_id ?? 0;
            runProcessedNode.textContent = run?.processed ?? 0;
            runUpdatedNode.textContent = run?.updated ?? 0;
            runSkippedNode.textContent = run?.skipped ?? 0;
            runTotalNode.textContent = run?.total_candidates ?? 0;
            runProgressNode.textContent = Number(run?.progress_percent || 0).toFixed(2);
            runProgressBar.style.width = `${Number(run?.progress_percent || 0)}%`;
            runRangeTargetNode.textContent = targetRangeText(run);
            runRangeProcessedNode.textContent = processedRangeText(rangeProcessed);
            runEtaNode.textContent = formatEta(run?.eta_seconds);
            renderLastErrors(lastErrors);

            updateButtonsByState();
        }

        function stopAutoLoop() {
            if (autoTimer) {
                clearTimeout(autoTimer);
                autoTimer = null;
            }
        }

        function scheduleAutoNext() {
            stopAutoLoop();
            if (!currentRun || currentRun.mode !== 'auto' || currentRun.status !== 'running') {
                return;
            }
            autoTimer = setTimeout(async () => {
                await nextBatch(true);
            }, 300);
        }

        async function fetchJson(url, options = {}) {
            const response = await fetch(url, options);
            const data = await response.json();
            if (!response.ok) {
                const error = new Error(data?.message || 'Ошибка запроса');
                error.payload = data;
                throw error;
            }
            return data;
        }

        async function postJson(url, payload) {
            return fetchJson(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });
        }

        async function refreshRuns() {
            try {
                const data = await fetchJson('{{ route('admin.prices.min.runs') }}');
                runsTableBody.innerHTML = '';

                (data.runs || []).forEach(run => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${run.id}</td>
                        <td>${run.status}</td>
                        <td>${run.mode || '-'}</td>
                        <td>${(run.start_id ?? '-') + ' .. ' + (run.end_id ?? '∞')}</td>
                        <td>${run.processed}</td>
                        <td>${run.updated}</td>
                        <td>${run.skipped}</td>
                        <td>${run.started_at ?? '-'}</td>
                        <td><button class="btn btn-sm btn-light open-run" data-id="${run.id}">Open</button></td>
                    `;
                    runsTableBody.appendChild(tr);
                });
            } catch (error) {
                setStatusMessage(error.message, true);
            }
        }

        async function refreshState(runId = null) {
            const id = runId || parseInt(runIdNode.textContent, 10);
            if (!id) {
                currentRun = null;
                updateButtonsByState();
                return;
            }
            try {
                const data = await fetchJson(`{{ route('admin.prices.min.state') }}?run_id=${id}`);
                renderRunState(data);
                scheduleAutoNext();
            } catch (error) {
                stopAutoLoop();
                setStatusMessage(error.message, true);
            }
        }

        function buildStartPayload() {
            return {
                category_id: categorySelect.value || null,
                subcategory_id: subcategorySelect.value || null,
                model_ids: selectedModelIds(),
                batch_size: parseInt(batchSizeInput.value, 10) || 200,
                mode: modeSelect.value || 'manual',
                start_id: startIdInput.value ? parseInt(startIdInput.value, 10) : null,
                end_id: endIdInput.value ? parseInt(endIdInput.value, 10) : null,
                skip_filled: skipFilledInput.checked,
                overwrite_existing: overwriteInput.checked,
            };
        }

        async function startRun() {
            stopAutoLoop();
            try {
                const payload = buildStartPayload();
                const data = await postJson('{{ route('admin.prices.min.start') }}', payload);
                setStatusMessage(data.message || 'Запуск создан');
                await refreshState(data.run.id);
                await refreshRuns();
                await loadResults(1);
            } catch (error) {
                if (error?.payload?.active_run_id) {
                    const activeId = parseInt(error.payload.active_run_id, 10);
                    if (activeId) {
                        await refreshState(activeId);
                        await refreshRuns();
                        await loadResults(1);
                        setStatusMessage(`${error.message} Открыт активный run #${activeId}.`, true);
                        return;
                    }
                }
                setStatusMessage(error.message, true);
            }
        }

        async function nextBatch(isAuto = false) {
            const runId = currentRun?.id || parseInt(runIdNode.textContent, 10);
            if (!runId) {
                setStatusMessage('Сначала создайте или откройте run', true);
                return;
            }

            try {
                const data = await postJson('{{ route('admin.prices.min.next') }}', { run_id: runId });
                renderRunState(data.state);
                if (!isAuto) {
                    setStatusMessage(data.message || 'Пакет обработан');
                }
                await loadResults(resultsPage);
                await refreshRuns();

                if (data.batch?.done) {
                    stopAutoLoop();
                    setStatusMessage('Пересчет завершен');
                } else {
                    scheduleAutoNext();
                }
            } catch (error) {
                stopAutoLoop();
                setStatusMessage(error.message, true);
            }
        }

        async function pauseRun() {
            const runId = currentRun?.id;
            if (!runId) return;
            stopAutoLoop();
            try {
                const data = await postJson('{{ route('admin.prices.min.pause') }}', { run_id: runId });
                currentRun = data.run;
                await refreshState(runId);
                await refreshRuns();
                setStatusMessage(data.message || 'Пауза включена');
            } catch (error) {
                setStatusMessage(error.message, true);
            }
        }

        async function resumeRun() {
            const runId = currentRun?.id;
            if (!runId) return;
            try {
                const data = await postJson('{{ route('admin.prices.min.resume') }}', { run_id: runId });
                currentRun = data.run;
                await refreshState(runId);
                await refreshRuns();
                setStatusMessage(data.message || 'Запуск продолжен');
            } catch (error) {
                setStatusMessage(error.message, true);
            }
        }

        async function stopRun() {
            const runId = currentRun?.id;
            if (!runId) return;
            stopAutoLoop();
            try {
                const data = await postJson('{{ route('admin.prices.min.stop') }}', {
                    run_id: runId,
                    reason: 'stopped_by_operator',
                });
                currentRun = data.run;
                await refreshState(runId);
                await refreshRuns();
                setStatusMessage(data.message || 'Запуск остановлен');
            } catch (error) {
                setStatusMessage(error.message, true);
            }
        }

        async function loadResults(page = 1) {
            const runId = currentRun?.id || parseInt(runIdNode.textContent, 10);
            if (!runId) {
                resultsTableBody.innerHTML = '';
                resultsMetaNode.textContent = 'Нет выбранного run';
                return;
            }

            try {
                const status = resultStatusInput.value || '';
                const q = encodeURIComponent(resultQueryInput.value || '');
                const url = `{{ route('admin.prices.min.results') }}?run_id=${runId}&status=${status}&q=${q}&page=${page}`;
                const data = await fetchJson(url);

                resultsPage = data.pagination.current_page;
                resultsLastPage = data.pagination.last_page;

                resultsTableBody.innerHTML = '';
                (data.items || []).forEach(item => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${item.product_id}</td>
                        <td>${item.product_title ?? ''}</td>
                        <td>${item.status}</td>
                        <td>${item.old_min_price ?? ''}</td>
                        <td>${item.new_min_price ?? ''}</td>
                        <td>${item.error_code ?? ''}</td>
                        <td>${item.error_message ?? ''}</td>
                        <td>${item.processed_at ?? ''}</td>
                    `;
                    resultsTableBody.appendChild(tr);
                });

                resultsMetaNode.textContent = `Страница ${resultsPage}/${resultsLastPage}, всего ${data.pagination.total}`;
            } catch (error) {
                setStatusMessage(error.message, true);
            }
        }

        function exportResults() {
            const runId = currentRun?.id || parseInt(runIdNode.textContent, 10);
            if (!runId) {
                setStatusMessage('Нет выбранного run', true);
                return;
            }
            const status = resultStatusInput.value || '';
            const url = `{{ route('admin.prices.min.export') }}?run_id=${runId}&status=${status}`;
            window.open(url, '_blank');
        }

        runsTableBody.addEventListener('click', async (event) => {
            const button = event.target.closest('.open-run');
            if (!button) return;
            const runId = parseInt(button.dataset.id, 10);
            if (!runId) return;
            stopAutoLoop();
            await refreshState(runId);
            if (currentRun) {
                setRunFormFromRun(currentRun);
                await loadResults(1);
                setStatusMessage(`Открыт run #${runId}`);
            }
        });

        startButton.addEventListener('click', startRun);
        nextButton.addEventListener('click', () => nextBatch(false));
        pauseButton.addEventListener('click', pauseRun);
        resumeButton.addEventListener('click', resumeRun);
        stopButton.addEventListener('click', stopRun);
        refreshStateButton.addEventListener('click', async () => {
            await refreshState();
            await refreshRuns();
            await loadResults(resultsPage);
        });
        cloneButton.addEventListener('click', () => {
            if (!currentRun) {
                setStatusMessage('Нет выбранного run для клонирования', true);
                return;
            }
            setRunFormFromRun(currentRun);
            setStatusMessage('Параметры текущего run загружены в форму');
        });
        loadResultsButton.addEventListener('click', () => loadResults(1));
        exportResultsButton.addEventListener('click', exportResults);
        prevPageButton.addEventListener('click', () => {
            if (resultsPage > 1) loadResults(resultsPage - 1);
        });
        nextPageButton.addEventListener('click', () => {
            if (resultsPage < resultsLastPage) loadResults(resultsPage + 1);
        });
        categorySelect.addEventListener('change', updateSubcategoryOptions);

        async function init() {
            updateSubcategoryOptions();
            updateButtonsByState();
            await refreshRuns();
            const initialRunId = parseInt(runIdNode.textContent, 10);
            if (initialRunId) {
                await refreshState(initialRunId);
                if (currentRun) {
                    setRunFormFromRun(currentRun);
                }
                await loadResults(1);
            }
        }

        init().catch(error => setStatusMessage(error.message, true));
    })();
</script>

<x-admin.footer></x-admin.footer>
