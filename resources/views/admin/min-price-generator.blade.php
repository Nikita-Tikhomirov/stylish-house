<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" tabindex="-1">
            <h1 class="section-title">Генератор минимальных цен</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Параметры запуска</h5>
            <div class="card-body">
                <div class="form-group">
                    <label for="category_id">Категория</label>
                    <select id="category_id" class="form-control">
                        <option value="">Все категории</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->titleh1 }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
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

                <div class="form-group">
                    <label for="model_ids">Модели (множественный выбор)</label>
                    <select id="model_ids" class="form-control" multiple size="10">
                        @foreach($models as $model)
                            <option value="{{ $model->id }}">{{ $model->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="batch_size">Размер пакета</label>
                    <input id="batch_size" type="number" class="form-control" value="{{ $activeRun?->batch_size ?? 200 }}"
                        min="50" max="500">
                </div>

                <div class="d-flex" style="gap: 8px; flex-wrap: wrap;">
                    <button id="start_run" class="btn btn-primary" type="button">Start</button>
                    <button id="next_batch" class="btn btn-success" type="button">Next batch</button>
                    <button id="pause_run" class="btn btn-warning" type="button">Pause</button>
                    <button id="resume_run" class="btn btn-info" type="button">Resume</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Статус запуска</h5>
            <div class="card-body">
                <p><strong>Run ID:</strong> <span id="run_id">{{ $activeRun?->id ?? '-' }}</span></p>
                <p><strong>Статус:</strong> <span id="run_status">{{ $activeRun?->status ?? 'нет активного запуска' }}</span></p>
                <p><strong>Обработано:</strong> <span id="run_processed">{{ $activeRun?->processed ?? 0 }}</span></p>
                <p><strong>Обновлено:</strong> <span id="run_updated">{{ $activeRun?->updated ?? 0 }}</span></p>
                <p><strong>Пропущено:</strong> <span id="run_skipped">{{ $activeRun?->skipped ?? 0 }}</span></p>
                <p><strong>Последний product_id:</strong> <span id="run_last_product_id">{{ $activeRun?->last_product_id ?? 0 }}</span></p>
                <div>
                    <strong>Ошибки последнего пакета:</strong>
                    <pre id="batch_errors" style="background:#f8f9fa;padding:12px;border-radius:6px;min-height:80px;">[]</pre>
                </div>
                <div id="status_message" class="mt-2"></div>
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

        const runIdNode = document.getElementById('run_id');
        const runStatusNode = document.getElementById('run_status');
        const runProcessedNode = document.getElementById('run_processed');
        const runUpdatedNode = document.getElementById('run_updated');
        const runSkippedNode = document.getElementById('run_skipped');
        const runLastProductIdNode = document.getElementById('run_last_product_id');
        const batchErrorsNode = document.getElementById('batch_errors');
        const statusMessageNode = document.getElementById('status_message');

        const startButton = document.getElementById('start_run');
        const nextButton = document.getElementById('next_batch');
        const pauseButton = document.getElementById('pause_run');
        const resumeButton = document.getElementById('resume_run');

        const allSubcategoryOptions = Array.from(subcategorySelect.querySelectorAll('option'));

        function updateSubcategoryOptions() {
            const categoryId = categorySelect.value;
            subcategorySelect.innerHTML = '';

            allSubcategoryOptions.forEach(option => {
                if (!option.value) {
                    subcategorySelect.appendChild(option.cloneNode(true));
                    return;
                }
                if (!categoryId || option.dataset.categoryId === categoryId) {
                    subcategorySelect.appendChild(option.cloneNode(true));
                }
            });
        }

        function getSelectedModelIds() {
            return Array.from(modelSelect.selectedOptions).map(option => parseInt(option.value, 10));
        }

        function setStatusMessage(text, isError) {
            statusMessageNode.textContent = text;
            statusMessageNode.style.color = isError ? '#dc3545' : '#198754';
        }

        function updateRunView(run, batchErrors) {
            runIdNode.textContent = run?.id ?? '-';
            runStatusNode.textContent = run?.status ?? '-';
            runProcessedNode.textContent = run?.processed ?? 0;
            runUpdatedNode.textContent = run?.updated ?? 0;
            runSkippedNode.textContent = run?.skipped ?? 0;
            runLastProductIdNode.textContent = run?.last_product_id ?? 0;
            if (Array.isArray(batchErrors)) {
                batchErrorsNode.textContent = JSON.stringify(batchErrors, null, 2);
            }
        }

        async function postJson(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();
            if (!response.ok) {
                const message = data?.message || 'Ошибка запроса';
                throw new Error(message);
            }
            return data;
        }

        startButton.addEventListener('click', async () => {
            try {
                const data = await postJson('{{ route('admin.prices.min.start') }}', {
                    category_id: categorySelect.value || null,
                    subcategory_id: subcategorySelect.value || null,
                    model_ids: getSelectedModelIds(),
                    batch_size: parseInt(batchSizeInput.value, 10) || 200,
                });
                updateRunView(data.run, []);
                setStatusMessage(data.message || 'Запуск создан', false);
            } catch (error) {
                setStatusMessage(error.message, true);
            }
        });

        nextButton.addEventListener('click', async () => {
            try {
                const runId = parseInt(runIdNode.textContent, 10);
                if (!runId) {
                    throw new Error('Сначала создайте запуск');
                }
                const data = await postJson('{{ route('admin.prices.min.next') }}', { run_id: runId });
                updateRunView(data.run, data.batch?.errors || []);
                setStatusMessage(data.message || 'Пакет обработан', false);
            } catch (error) {
                setStatusMessage(error.message, true);
            }
        });

        pauseButton.addEventListener('click', async () => {
            try {
                const runId = parseInt(runIdNode.textContent, 10);
                if (!runId) {
                    throw new Error('Нет активного запуска');
                }
                const data = await postJson('{{ route('admin.prices.min.pause') }}', { run_id: runId });
                updateRunView(data.run, null);
                setStatusMessage(data.message || 'Пауза включена', false);
            } catch (error) {
                setStatusMessage(error.message, true);
            }
        });

        resumeButton.addEventListener('click', async () => {
            try {
                const runId = parseInt(runIdNode.textContent, 10);
                if (!runId) {
                    throw new Error('Нет активного запуска');
                }
                const data = await postJson('{{ route('admin.prices.min.resume') }}', { run_id: runId });
                updateRunView(data.run, null);
                setStatusMessage(data.message || 'Запуск продолжен', false);
            } catch (error) {
                setStatusMessage(error.message, true);
            }
        });

        categorySelect.addEventListener('change', updateSubcategoryOptions);
        updateSubcategoryOptions();
    })();
</script>

<x-admin.footer></x-admin.footer>
