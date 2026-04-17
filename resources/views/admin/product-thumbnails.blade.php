<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" tabindex="-1">
            <h1 class="section-title">Product Thumbnails Queue</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Run Batch Generation</h5>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label for="thumbCategory">Category</label>
                        <select id="thumbCategory" class="form-control">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->titleh1 }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="thumbSubcategory">Subcategory</label>
                        <select id="thumbSubcategory" class="form-control">
                            <option value="">All subcategories</option>
                            @foreach ($subcategories as $subcategory)
                                <option value="{{ $subcategory->id }}" data-category-id="{{ $subcategory->category_id }}">
                                    {{ $subcategory->titleh1 }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="thumbChunk">Chunk size</label>
                        <input id="thumbChunk" type="number" min="1" max="500" value="100" class="form-control">
                    </div>
                    <div class="col-md-4 mt-3">
                        <label for="thumbStartFrom">Start from product ID</label>
                        <input id="thumbStartFrom" type="number" min="0" value="0" class="form-control">
                    </div>
                    <div class="col-md-2 mt-3">
                        <label for="thumbWidth">Thumb width</label>
                        <input id="thumbWidth" type="number" min="50" max="2000" value="400" class="form-control">
                    </div>
                    <div class="col-md-2 mt-3">
                        <label for="thumbHeight">Thumb height</label>
                        <input id="thumbHeight" type="number" min="50" max="2000" value="400" class="form-control">
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-3">
                        <label><input id="thumbIncludeMain" type="checkbox" checked> Main photo</label>
                    </div>
                    <div class="col-md-3">
                        <label><input id="thumbIncludeFabric" type="checkbox" checked> Material photo</label>
                    </div>
                    <div class="col-md-3">
                        <label><input id="thumbForce" type="checkbox"> Force regenerate</label>
                    </div>
                </div>

                <div class="mt-3">
                    <button id="thumbStartBtn" class="btn btn-primary">Start</button>
                    <button id="thumbStopBtn" class="btn btn-outline-secondary">Stop</button>
                </div>

                <div class="progress mt-4" style="height: 20px;">
                    <div id="thumbProgressBar" class="progress-bar progress-bar-striped" role="progressbar"
                        style="width: 0%">0%</div>
                </div>

                <div class="mt-3">
                    <div id="thumbStatus">Idle</div>
                    <div id="thumbStats"></div>
                </div>

                <pre id="thumbErrors" class="mt-3" style="max-height: 240px; overflow: auto;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
    const thumbRoute = @json(route('admin.product_thumbnails.process'));
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const subcatSelect = document.getElementById('thumbSubcategory');
    const allSubcatOptions = Array.from(subcatSelect.querySelectorAll('option'));
    const cursorKey = 'product_thumbs_last_id';

    let running = false;
    let stopRequested = false;

    function renderSubcategoriesByCategory() {
        const selectedCategory = document.getElementById('thumbCategory').value;
        subcatSelect.innerHTML = '';
        allSubcatOptions.forEach((option) => {
            if (!option.value || !selectedCategory || option.dataset.categoryId === selectedCategory) {
                subcatSelect.appendChild(option.cloneNode(true));
            }
        });
    }

    function setProgress(processedTotal, total) {
        const percent = total > 0 ? Math.min(100, Math.round((processedTotal / total) * 100)) : 0;
        const bar = document.getElementById('thumbProgressBar');
        bar.style.width = percent + '%';
        bar.textContent = percent + '%';
    }

    function appendErrorText(items) {
        if (!items || !items.length) return;
        const block = document.getElementById('thumbErrors');
        const lines = items.map((item) => `product:${item.product_id} type:${item.type} reason:${item.reason}`);
        block.textContent += (block.textContent ? '\n' : '') + lines.join('\n');
    }

    async function runBatch() {
        const includeMain = document.getElementById('thumbIncludeMain').checked;
        const includeFabric = document.getElementById('thumbIncludeFabric').checked;
        if (!includeMain && !includeFabric) {
            alert('Select at least one image type');
            return;
        }

        running = true;
        stopRequested = false;
        document.getElementById('thumbErrors').textContent = '';
        document.getElementById('thumbStatus').textContent = 'Starting...';

        let lastId = Math.max(0, parseInt(document.getElementById('thumbStartFrom').value || '0', 10));
        let total = 0;
        let processedTotal = 0;
        let generatedMainTotal = 0;
        let generatedFabricTotal = 0;
        let skippedTotal = 0;
        let errorsTotal = 0;

        const limit = Math.max(1, Math.min(500, parseInt(document.getElementById('thumbChunk').value || '100', 10)));
        const width = Math.max(50, Math.min(2000, parseInt(document.getElementById('thumbWidth').value || '400', 10)));
        const height = Math.max(50, Math.min(2000, parseInt(document.getElementById('thumbHeight').value || '400', 10)));

        while (!stopRequested) {
            const payload = {
                last_id: lastId,
                limit: limit,
                width: width,
                height: height,
                category_id: document.getElementById('thumbCategory').value || null,
                subcategory_id: document.getElementById('thumbSubcategory').value || null,
                force: document.getElementById('thumbForce').checked,
                include_main: includeMain,
                include_fabric: includeFabric,
            };

            const response = await fetch(thumbRoute, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(payload)
            });

            const rawText = await response.text();
            let data = null;
            try {
                data = JSON.parse(rawText);
            } catch (e) {
                throw new Error(`Server returned non-JSON response (HTTP ${response.status}). Check laravel.log`);
            }

            if (!response.ok) {
                throw new Error(data.message || 'Batch step failed');
            }

            total = data.total || total;
            processedTotal += data.processed || 0;
            lastId = data.next_last_id || lastId;
            localStorage.setItem(cursorKey, String(lastId));
            document.getElementById('thumbStartFrom').value = String(lastId);

            const stepStats = data.stats || {};
            generatedMainTotal += stepStats.generated_main || 0;
            generatedFabricTotal += stepStats.generated_fabric || 0;
            skippedTotal += stepStats.skipped || 0;
            errorsTotal += stepStats.errors || 0;

            appendErrorText(data.errors_sample || []);
            setProgress(processedTotal, total);
            document.getElementById('thumbStatus').textContent = data.done ? 'Completed' : `Processing... id>${lastId}`;
            document.getElementById('thumbStats').textContent =
                `Processed: ${processedTotal}/${total} | Main: ${generatedMainTotal} | Fabric: ${generatedFabricTotal} | Skipped: ${skippedTotal} | Errors: ${errorsTotal}`;

            if (data.done) {
                break;
            }
        }

        if (stopRequested) {
            document.getElementById('thumbStatus').textContent = 'Stopped';
        }

        running = false;
    }

    document.getElementById('thumbCategory').addEventListener('change', renderSubcategoriesByCategory);
    renderSubcategoriesByCategory();
    const savedCursor = parseInt(localStorage.getItem(cursorKey) || '0', 10);
    if (!Number.isNaN(savedCursor) && savedCursor > 0) {
        document.getElementById('thumbStartFrom').value = String(savedCursor);
    }

    document.getElementById('thumbStartBtn').addEventListener('click', async function() {
        if (running) return;
        this.disabled = true;
        document.getElementById('thumbStopBtn').disabled = false;

        try {
            await runBatch();
        } catch (error) {
            document.getElementById('thumbStatus').textContent = 'Error';
            alert(error.message || 'Batch failed');
        } finally {
            this.disabled = false;
            document.getElementById('thumbStopBtn').disabled = true;
            running = false;
        }
    });

    document.getElementById('thumbStopBtn').addEventListener('click', function() {
        stopRequested = true;
    });
    document.getElementById('thumbStopBtn').disabled = true;
</script>

<x-admin.footer></x-admin.footer>
