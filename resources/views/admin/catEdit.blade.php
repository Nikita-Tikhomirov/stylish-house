<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Редактировать категорию {{ $category->titleh1 }}</h1>
        </div>
    </div>
</div>



<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Первый экран</h5>
            <div class="card-body">
                <form id="categoryEditForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="title">Заголовок(meta)</label>
                        <input id="title" name="title" type="text" class="form-control"
                            value="{{ $category->title }}">
                    </div>
                    <div class="form-group">
                        <label for="description">Описание(meta)</label>
                        <input id="description" name="description" type="text" class="form-control"
                            value="{{ $category->description }}">
                    </div>
                    <div class="form-group">
                        <label for="slug">slug</label>
                        <input id="slug" name="slug" type="text" class="form-control"
                            value="{{ $category->slug }}">
                    </div>
                    <div class="form-group">
                        <label for="titleh1">Заголовок h1</label>
                        <input id="titleh1" name="titleh1" type="text" class="form-control"
                            value="{{ $category->titleh1 }}">
                    </div>
                    <div class="form-group">
                        <label for="first_screen_text">Текст</label>
                        <textarea class="form-control" name="first_screen_text" id="first_screen_text">{{ $category->first_screen_text }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="img">Фото категории</label>
                        <input name="img" type="file" class="form-control">
                        <img src="{{ Storage::url($category->img) }}" alt="">
                    </div>

                    <div class="form-group">
                        <label for="subcat_title">Заголовок для блока с подкатегорияи</label>
                        <input id="subcat_title" name="subcat_title" type="text" class="form-control"
                            value="{{ $category->subcat_title }}">
                    </div>

                    <div class="form-group">
                        <label for="faq">FAQ для рольставен (HTML)</label>
                        <textarea class="form-control" name="faq" id="faq" rows="10">{{ $category->faq }}</textarea>
                        <small class="form-text text-muted">Вставляйте HTML-код аккордеона. Используйте стили как в товаре.</small>
                    </div>

                    <button class="btn btn-primary" type="button" id="saveCategoryBtn">Сохранить</button>
                </form>
            </div>

        </div>
    </div>
</div>
{{-- Видео обзоры --}}
<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Видеообзоры</h5>
            <div class="card-body">
                <div class="videoReviewCards" data-category-id="{{ $category->id }}">

                    @foreach ($videoReviews as $videoReview)
                        @if ($videoReview->category_id == $category->id && !$videoReview->subcategory_id)
                            <!-- Проверяем, что подкатегория не указана -->
                            <form class="videoReviewCards_card" data-id="{{ $videoReview->id }}"
                                enctype="multipart/form-data">
                                <input type="hidden" name="category_id" value="{{ $videoReview->category_id }}">
                                <input type="hidden" name="subcategory_id"
                                    value="{{ $videoReview->subcategory_id ?? '' }}">
                                <!-- Скрытые поля для ID категории и подкатегории -->
                                <div class="form-group">
                                    <label for="cover_image" class="col-form-label">Обложка</label>
                                    <input name="cover_image" type="file" class="form-control">
                                    @if ($videoReview->cover_image)
                                        <img src="{{ Storage::url($videoReview->cover_image) }}" alt="Cover Image"
                                            style="max-width: 100px;">
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="video" class="col-form-label">Видео</label>
                                    <input name="video" type="file" class="form-control">
                                    @if ($videoReview->video)
                                        @if (\Illuminate\Support\Str::startsWith($videoReview->video, ['http://', 'https://']))
                                            <a href="{{ $videoReview->video }}" target="_blank" rel="noopener">Открыть текущее видео</a>
                                        @else
                                            <video controls style="max-width: 200px;">
                                                <source src="{{ Storage::url($videoReview->video) }}" type="video/mp4">
                                            </video>
                                        @endif
                                    @endif
                                    <input name="video_url" type="url" class="form-control mt-2"
                                        placeholder="Или ссылка на видео (Google Drive)"
                                        value="{{ \Illuminate\Support\Str::startsWith($videoReview->video, ['http://', 'https://']) ? $videoReview->video : '' }}">
                                </div>
                                <div class="form-group">
                                    <label for="title" class="col-form-label">Заголовок</label>
                                    <input name="title" type="text" class="form-control"
                                        value="{{ $videoReview->title }}">
                                </div>
                                <div class="form-group">
                                    <label for="description" class="col-form-label">Описание</label>
                                    <textarea name="description" class="form-control">{{ $videoReview->description }}</textarea>
                                </div>
                                <button class="btn btn-primary save-video-review-button"
                                    type="button">Сохранить</button>
                                <div class="btn btn-outline-secondary delete-video-review-button">Удалить</div>
                            </form>
                        @endif
                    @endforeach

                    <div class="videoReviewCards__addCard btn btn-primary">Добавить видеообзор</div>
                </div>
            </div>
        </div>
    </div>
</div>





{{-- Галлерея --}}
<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Примеры работ</h5>
            <div class="card-body">
                <div class="work-examples-gallery" data-category-id="{{ $category->id }}">


                    <div id="dropzone" class="dropzone">
                        Перетащите файлы сюда или нажмите, чтобы выбрать.
                        <input type="file" id="workExamplesInput" multiple style="display: none;">
                    </div>

                    <button id="uploadWorkExamplesBtn" class="btn btn-primary">Загрузить изображения</button>

                    <div id="workExamplesContainer">
                        <!-- Уже загруженные изображения -->
                        @if ($workExamples->isNotEmpty())

                            @foreach ($workExamples as $workExample)
                                <div class="work-example-card" data-id="{{ $workExample->id }}">
                                    <img src="/storage/{{ $workExample->thumb ?? $workExample->image }}" alt="Work Example Image"
                                        style="max-width: 100px;">
                                    <input placeholder="Название" name="title" type="text" class="form-control"
                                        value="{{ $workExample->title }}">
                                    <label for="description">Описание</label>
                                    <textarea name="description" class="form-control">{{ $workExample->description }}</textarea>
                                    <button class="btn btn-primary save-work-example">Сохранить</button>
                                    <button class="btn btn-danger delete-work-example">Удалить</button>
                                </div>
                            @endforeach
                        @else
                            <p>Нет загруженных примеров работ</p>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($category->id === 16)
{{-- Типы установки рольставней (только для категории id=16) --}}
        <style>
            .ql-container{
                height: auto !important;
            }
        </style>
<div class="row">
    <div class="col-12">
        <div class="card">
            <h5 class="card-header">Виды монтажа рольставней</h5>
            <div class="card-body installation-types" data-category-slug="{{ $category->slug }}">
                <button class="btn btn-primary add-installation-type mb-3">Добавить тип монтажа</button>
                <div class="installation-types-container">
                    @foreach ($installationTypes as $type)
                        <form class="installation-type-card card mb-3 p-3" data-id="{{ $type->id }}">
                            <div class="row">
                                <div class="col-md-2">
                                    <label>Иконка (50x50)</label>
                                    <input name="image" type="file" class="form-control-file">
                                    @if ($type->image)
                                        <img src="{{ Storage::url($type->image) }}" style="max-width:50px; margin-top:5px;">
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <label>Большое фото</label>
                                    <input name="detail_image" type="file" class="form-control-file">
                                    @if ($type->detail_image)
                                        <img src="{{ Storage::url($type->detail_image) }}" style="max-width:100px; margin-top:5px;">
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <label>Заголовок</label>
                                    <input name="title" type="text" class="form-control" value="{{ $type->title }}">
                                </div>
                                <div class="col-md-1">
                                    <label>Порядок</label>
                                    <input name="sort_order" type="number" class="form-control" value="{{ $type->sort_order }}">
                                </div>
                                <div class="col-md-3 d-flex align-items-end gap-2">
                                    <button class="btn btn-primary save-installation-type" type="button">Сохранить</button>
                                    <button class="btn btn-danger delete-installation-type" type="button">Удалить</button>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <label>Описание (HTML)</label>
                                    <div class="quill-editor installation-desc-editor" style="min-height:150px;">{!! $type->description !!}</div>
                                    <input type="hidden" name="description" value="{{ $type->description }}">
                                </div>
                            </div>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Системы управления рольставнями (только для категории id=16) --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <h5 class="card-header">Системы управления рольставнями</h5>
            <div class="card-body roller-shutter-systems" data-category-slug="{{ $category->slug }}">
                <button class="btn btn-primary add-roller-system mb-3">Добавить систему</button>
                <div class="roller-systems-container">
                    @foreach ($rollerShutterSystems as $system)
                        <form class="roller-system-card card mb-3 p-3" data-id="{{ $system->id }}">
                            <div class="row">
                                <div class="col-md-2">
                                    <label>Изображение</label>
                                    <input name="image" type="file" class="form-control-file">
                                    @if ($system->image)
                                        <img src="{{ Storage::url($system->image) }}" style="max-width:100px; margin-top:5px;">
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <label>Название системы</label>
                                    <input name="title" type="text" class="form-control" value="{{ $system->title }}">
                                </div>
                                <div class="col-md-1">
                                    <label>Порядок</label>
                                    <input name="sort_order" type="number" class="form-control" value="{{ $system->sort_order }}">
                                </div>
                                <div class="col-md-3 d-flex align-items-end gap-2">
                                    <button class="btn btn-primary save-roller-system" type="button">Сохранить</button>
                                    <button class="btn btn-danger delete-roller-system" type="button">Удалить</button>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <label>Описание (HTML)</label>
                                    <div class="quill-editor system-desc-editor" style="min-height:150px;">{!! $system->description !!}</div>
                                    <input type="hidden" name="description" value="{{ $system->description }}">
                                </div>
                                <div class="col-md-6">
                                    <label>Список компонентов (по одному на строку)</label>
                                    <textarea name="components" class="form-control" rows="5">{{ $system->components }}</textarea>
                                </div>
                            </div>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Вопросы и ответы --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <h5 class="card-header">Секция Вопросы и ответы</h5>
            <div class="card-body faq">
                <button class="btn btn-primary add-faq-button">Добавить вопрос</button>
                <div class="faq-cards-container">
                    @foreach ($faqs as $faq)
                        <form class="faq-card" data-id="{{ $faq->id }}">
                            <div class="form-group">
                                <label for="title">Вопрос</label>
                                <input name="title" type="text" class="form-control"
                                    value="{{ $faq->question }}">
                            </div>
                            <div class="form-group">
                                <label for="text">Ответ</label>
                                <textarea name="text" class="form-control">{{ $faq->answer }}</textarea>
                            </div>
                            <button class="btn btn-primary save-faq-button" type="button">Сохранить</button>
                            <a class="btn btn-outline-secondary delete-faq-button">Удалить</a>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>



{{-- Добавьте скрытое поле для subcategory_slug (если это подкатегория) --}}
<input type="hidden" name="category-slug" value="{{ $category->slug }}">
@if ($subcategory)
    <input type="hidden" name="subcategory-slug" value="{{ $subcategory->slug }}">
@endif

{{-- Секция Сео --}}

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Секция СЕО текст</h5>
            <div class="card-body">
                <form>
                    <div class="form-group">
                        <label for="seoEditor">Редактировать</label>
                        <div id="seoEditor">
                            {!! $category->seo !!}
                        </div>
                    </div>
                    <button class="btn btn-primary" type="button" id="saveSeoButton">Сохранить</button>
                </form>
            </div>

        </div>
    </div>
</div>

{{-- вывод в меню --}}
<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Вывод в виджетах</h5>
            <div class="card-body">
                <form id="categoryEditForm">
                    <!-- Другие поля для редактирования категории -->

                    <div class="form-group">
                        <label for="show_in_menu">Показывать в меню</label>
                        <input type="checkbox" id="show_in_menu" name="show_in_menu"
                            {{ $category->show_in_menu ? 'checked' : '' }}>
                    </div>

                    <div class="form-group">
                        <label for="show_in_catalog">Показывать в каталоге</label>
                        <input type="checkbox" id="show_in_catalog" name="show_in_catalog"
                            {{ $category->show_in_catalog ? 'checked' : '' }}>
                    </div>

                    <div class="form-group">
                        <label for="related_items_ids">Связанные Категории и Подкатегории</label>
                        <select style="min-height: 300px;" class="form-control" name="related_items_ids[]"
                            id="related_items_ids" multiple>
                            <optgroup label="Категории">
                                @foreach ($categories as $categoryOption)
                                    <option value="{{ $categoryOption->id }}"
                                        @if (in_array($categoryOption->id, $relatedIds ?? [])) selected @endif>
                                        {{ $categoryOption->titleh1 }}
                                    </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Подкатегории">
                                @foreach ($subcategories as $subcategoryOption)
                                    <option value="{{ $subcategoryOption->id }}"
                                        @if (in_array($subcategoryOption->id, $relatedIds ?? [])) selected @endif>
                                        {{ $subcategoryOption->titleh1 ?? $subcategoryOption->title }}
                                    </option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="calc_prod">ID товара в блоке "Как заказать"</label>
                        <input type="number" class="form-control" id="calc_prod" name="calc_prod"
                            value="{{ $category->calc_prod }}">
                    </div>

                    <button class="btn btn-primary" type="button" id="saveCategoryButton">Сохранить</button>
                </form>
            </div>

        </div>
    </div>
</div>




<!-- Включение Dropzone.js -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>


<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<script>
    document.getElementById('saveCategoryBtn').addEventListener('click', function() {
        let form = document.getElementById('categoryEditForm');
        let formData = new FormData(form);

        fetch('{{ route('categories.update', ['slug' => $category->slug]) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => {
                if (!response.ok && response.status === 422) {
                    // Ошибка валидации — парсим и показываем
                    return response.json().then(data => {
                        const errors = data.errors || {};
                        const messages = Object.values(errors).flat().join('\n');
                        alert('Ошибки валидации:\n' + (messages || data.message || 'Неизвестная ошибка'));
                        throw new Error('VALIDATION_FAILED');
                    });
                }
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Сервер ответил ошибкой:', text);
                        alert('Ошибка сервера (' + response.status + '). Попробуйте позже.');
                        throw new Error('SERVER_ERROR');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    alert(data.success);
                }
            })
            .catch(error => {
                if (error.message !== 'VALIDATION_FAILED' && error.message !== 'SERVER_ERROR') {
                    console.error('Ошибка:', error);
                    alert('Произошла ошибка при обновлении категории.');
                }
            });
    });
</script>

{{-- Видеообзоры --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function saveVideoReviewCard(card) {
            let formData = new FormData(card);

            const cardId = card.getAttribute('data-id');
            const categoryId = document.querySelector('.videoReviewCards').getAttribute('data-category-id');
            const subcategoryId = document.querySelector('.videoReviewCards').getAttribute(
                'data-subcategory-id');
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const url = cardId ? `/admin/video-reviews/update/${cardId}` : `/admin/video-reviews/store`;
            const method = cardId ? 'PUT' : 'POST';

            formData.append('_method', method);
            formData.append('category_id', categoryId);
            formData.append('subcategory_id', subcategoryId ? subcategoryId : '');

            fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                            .getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.errors) {
                        alert('Ошибки: ' + JSON.stringify(data.errors));
                    } else {
                        alert('Видеообзор успешно сохранен');
                        if (!cardId) {
                            card.setAttribute('data-id', data.videoReview ? data.videoReview.id : null);
                        }
                    }
                })
                .catch(error => console.error('Ошибка:', error));
        }

        function addNewVideoReviewCard() {
            const categoryId = document.querySelector('.videoReviewCards').getAttribute('data-category-id');
            const subcategoryId = document.querySelector('.videoReviewCards').getAttribute(
                'data-subcategory-id');
            const newVideoReviewCard = document.createElement('form');
            newVideoReviewCard.classList.add('videoReviewCards_card');

            newVideoReviewCard.innerHTML = `
        <div class="form-group">
            <label for="cover_image">Обложка</label>
            <input name="cover_image" type="file" class="form-control">
        </div>
        <div class="form-group">
            <label for="video">Видео</label>
            <input name="video" type="file" class="form-control">
            <input name="video_url" type="url" class="form-control mt-2" placeholder="Или ссылка на видео (Google Drive)">
        </div>
        <div class="form-group">
            <label for="title">Заголовок</label>
            <input name="title" type="text" class="form-control">
        </div>
        <div class="form-group">
            <label for="description">Описание</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
        <input type="hidden" name="category_id" value="${categoryId}">
        <input type="hidden" name="subcategory_id" value="${subcategoryId ? subcategoryId : ''}">
        <button class="btn btn-primary save-video-review-button" type="button">Сохранить</button>
        <a class="btn btn-outline-secondary delete-video-review-button">Удалить</a>
    `;

            document.querySelector('.videoReviewCards').appendChild(newVideoReviewCard);
        }

        function deleteVideoReviewCard(card) {
            const cardId = card.getAttribute('data-id');
            if (!cardId) {
                card.remove();
                return;
            }

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`/admin/video-reviews/destroy/${cardId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Видеообзор успешно удален');
                        card.remove();
                    } else {
                        alert('Ошибка при удалении видеообзора');
                    }
                })
                .catch(error => console.error('Ошибка:', error));
        }

        document.querySelector('.videoReviewCards').addEventListener('click', function(e) {
            if (e.target.classList.contains('save-video-review-button')) {
                const card = e.target.closest('.videoReviewCards_card');
                saveVideoReviewCard(card);
            } else if (e.target.classList.contains('delete-video-review-button')) {
                const card = e.target.closest('.videoReviewCards_card');
                deleteVideoReviewCard(card);
            }
        });

        document.querySelector('.videoReviewCards__addCard').addEventListener('click', addNewVideoReviewCard);
    });
</script>


{{-- галерея --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('workExamplesInput');
        const dropzone = document.getElementById('dropzone');
        const uploadBtn = document.getElementById('uploadWorkExamplesBtn');
        const container = document.getElementById('workExamplesContainer');
        let filesToUpload = [];

        // Highlight dropzone when dragging over
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });

        // Remove highlight when leaving dropzone
        dropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            dropzone.classList.remove('dragover');
        });

        // Client-side image resizer — shrinks huge photos before upload
        function resizeImage(file) {
            return new Promise((resolve, reject) => {
                if (!file.type.startsWith('image/')) { resolve(file); return; }
                const img = new Image();
                const url = URL.createObjectURL(file);
                img.onload = function() {
                    URL.revokeObjectURL(url);
                    const MAX = 2000;
                    let w = img.width, h = img.height;
                    if (w <= MAX && h <= MAX) { resolve(file); return; }
                    if (w > h) { h = Math.round(h * MAX / w); w = MAX; }
                    else       { w = Math.round(w * MAX / h); h = MAX; }
                    const canvas = document.createElement('canvas');
                    canvas.width = w; canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, w, h);
                    canvas.toBlob(function(blob) {
                        if (!blob) { resolve(file); return; }
                        resolve(new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() }));
                    }, 'image/jpeg', 0.88);
                };
                img.onerror = function() { URL.revokeObjectURL(url); resolve(file); };
                img.src = url;
            });
        }

        // Process files sequentially to avoid browser memory crash with 50MB+ photos
        async function addFiles(newFiles) {
            const total = newFiles.length;
            for (let i = 0; i < total; i++) {
                dropzone.textContent = 'Сжатие фото ' + (i + 1) + '/' + total + '...';
                const resized = await resizeImage(newFiles[i]);
                filesToUpload.push(resized);
            }
            dropzone.textContent = 'Перетащите файлы сюда или нажмите, чтобы выбрать.';
            displayFiles(filesToUpload);
        }

        // Handle drop files
        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            addFiles(Array.from(e.dataTransfer.files));
        });

        // Open file manager on click
        dropzone.addEventListener('click', function() {
            input.click();
        });

        // Handle file input selection
        input.addEventListener('change', function(e) {
            addFiles(Array.from(e.target.files));
        });

        // Display file preview and remove option
        function displayFiles(files) {
            container.innerHTML = ''; // Clear container before rendering

            files.forEach((file, index) => {
                const fileElement = document.createElement('div');
                fileElement.classList.add('work-example-card');

                // Create preview using FileReader
                const reader = new FileReader();
                reader.onload = function(e) {
                    fileElement.innerHTML = `
                    <img src="${e.target.result}" alt="Image Preview" style="max-width: 100px;">
                    <span>${file.name}</span>
                    <button class="remove-file" data-index="${index}">Удалить</button>
                `;
                    container.appendChild(fileElement);
                };
                reader.readAsDataURL(file);
            });
        }

        // Handle removing files from the list
        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-file')) {
                const index = e.target.getAttribute('data-index');
                filesToUpload.splice(index, 1); // Remove the file from the list
                displayFiles(filesToUpload); // Re-render file list
            }
        });

        uploadBtn.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('category_id', '{{ $category->id }}'); // Добавляем ID категории

            filesToUpload.forEach((file) => {
                formData.append('images[]', file);
            });

            fetch('/work-examples', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Очищаем файлы из списка после успешной загрузки
                    filesToUpload = [];

                    // Очищаем поле input для файлов
                    input.value = '';

                    // Очищаем контейнер только для предварительного просмотра загружаемых файлов
                    container.innerHTML = '';

                    // Отрисовываем загруженные файлы из ответа
                    data.forEach(renderWorkExample);
                })
                .catch(error => console.error('Error:', error));
        });

        // Render image preview after uploading
        function renderWorkExample(workExample) {
            const card = document.createElement('div');
            card.classList.add('work-example-card');
            card.setAttribute('data-id', workExample.id);

            card.innerHTML = `
            <img src="/storage/${workExample.thumb || workExample.image}" alt="Work Example Image" style="max-width: 100px;">
            <input placeholder="Название" name="title" type="text" class="form-control" value="${workExample.title}"><label for="description">Описание</label>
            <textarea name="description" class="form-control">${workExample.description}</textarea>
            <button class="btn btn-primary save-work-example">Сохранить</button>
            <button class="btn btn-danger delete-work-example">Удалить</button>
        `;
            container.appendChild(card);
        }

        // Save changes to work example
        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('save-work-example')) {
                const card = e.target.closest('.work-example-card');
                const id = card.getAttribute('data-id');
                const title = card.querySelector('input[name="title"]').value;
                const description = card.querySelector('textarea[name="description"]').value;

                fetch(`/work-examples/${id}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            title,
                            description
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        alert('Changes saved');
                    })
                    .catch(error => console.error('Error:', error));
            }
        });

        // Handle deleting work example
        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-work-example')) {
                const card = e.target.closest('.work-example-card');
                const id = card.getAttribute('data-id');

                fetch(`/work-examples/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            card.remove();
                        } else {
                            alert('Error deleting');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    });
</script>



{{-- FAQ --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Function to save or update FAQ
        function saveFaqCard(card) {
            const question = card.querySelector('[name="title"]').value;
            const answer = card.querySelector('[name="text"]').value;
            const cardId = card.getAttribute('data-id');
            const slug = document.querySelector('input[name="category-slug"]').value;

            // Проверяем существование поля subcategory-slug
            const subcategorySlugElement = document.querySelector('input[name="subcategory-slug"]');
            const subcategorySlug = subcategorySlugElement ? subcategorySlugElement.value : null;

            const url = cardId ? `/categories/${slug}/questions/${cardId}` : `/categories/${slug}/questions`;
            const method = cardId ? 'PUT' : 'POST';

            fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        question,
                        answer,
                        subcategory_slug: subcategorySlug // Передаем slug подкатегории, если он есть
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.errors) {
                        alert('Ошибки: ' + JSON.stringify(data.errors));
                    } else {
                        alert(data.message);
                        if (!cardId) {
                            card.setAttribute('data-id', data.faq.id); // Set new ID for the card
                        }
                    }
                })
                .catch(error => console.error('Ошибка:', error));
        }

        // Function to delete FAQ
        function deleteFaqCard(card) {
            const cardId = card.getAttribute('data-id');
            if (!cardId) {
                card.remove();
                return;
            }

            const slug = document.querySelector('input[name="category-slug"]').value;

            fetch(`/categories/${slug}/questions/${cardId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    }
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    card.remove();
                })
                .catch(error => console.error('Ошибка:', error));
        }

        // Add new FAQ card
        function addNewFaqCard() {
            const newFaqCard = document.createElement('form');
            newFaqCard.classList.add('faq-card');

            newFaqCard.innerHTML = `
        <div class="form-group">
            <label for="title">Вопрос</label>
            <input name="title" type="text" class="form-control">
        </div>
        <div class="form-group">
            <label for="text">Ответ</label>
            <textarea name="text" class="form-control"></textarea>
        </div>
        <button class="btn btn-primary save-faq-button" type="button">Сохранить</button>
        <a class="btn btn-outline-secondary delete-faq-button">Удалить</a>
    `;

            document.querySelector('.faq-cards-container').appendChild(newFaqCard);
        }

        // Event delegation for save and delete buttons
        document.querySelector('.faq-cards-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('save-faq-button')) {
                const card = e.target.closest('.faq-card');
                saveFaqCard(card);
            } else if (e.target.classList.contains('delete-faq-button')) {
                const card = e.target.closest('.faq-card');
                deleteFaqCard(card);
            }
        });

        // Add new FAQ event listener
        document.querySelector('.add-faq-button').addEventListener('click', addNewFaqCard);
    });
</script>



{{-- Сео текст --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toolbarOptions = [
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            ['link', 'image', 'video', 'formula'],
            [{
                'header': 1
            }, {
                'header': 2
            }],
            [{
                'list': 'ordered'
            }, {
                'list': 'bullet'
            }, {
                'list': 'check'
            }],
            [{
                'script': 'sub'
            }, {
                'script': 'super'
            }],
            [{
                'indent': '-1'
            }, {
                'indent': '+1'
            }],
            [{
                'direction': 'rtl'
            }],
            [{
                'size': ['small', false, 'large', 'huge']
            }],
            [{
                'header': [1, 2, 3, 4, 5, 6, false]
            }],
            [{
                'color': []
            }, {
                'background': []
            }],
            [{
                'font': []
            }],
            [{
                'align': []
            }],
            ['clean']
        ];

        var quill = new Quill('#seoEditor', {
            modules: {
                toolbar: toolbarOptions
            },
            theme: 'snow',
        });

        document.getElementById('saveSeoButton').addEventListener('click', function() {
            const content = quill.root.innerHTML;

            // Проверяем, пуст ли текст (без учета форматирования)
            if (quill.getText().trim() === '') {
                alert('Контент пустой, введите текст.');
                return;
            }

            // Отправляем запрос на сервер
            fetch(`/categories/{{ $category->slug }}/edit-seo`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({
                        seo: content // SEO текст
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.error('HTML response:', text);
                            throw new Error('Network response was not ok');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.message) {
                        alert(data.message);
                    } else {
                        alert('Ошибка при обновлении контента');
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    alert('Произошла ошибка при отправке данных');
                });
        });
    });
</script>

{{-- Меню --}}
<script>
    document.getElementById('saveCategoryButton').addEventListener('click', function() {
        const showInMenu = document.getElementById('show_in_menu').checked ? 1 : 0;
        const showInCatalog = document.getElementById('show_in_catalog').checked ? 1 : 0;
        const selectedAllSubcategoryIds = Array.from(document.getElementById('related_items_ids')
                .selectedOptions)
            .map(option => option.value);
        const calc_prod = document.getElementById('calc_prod').value
        fetch(`/admin/categories/{{ $category->slug }}/update-visibility`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                        'content')
                },
                body: JSON.stringify({
                    show_in_menu: showInMenu,
                    show_in_catalog: showInCatalog,
                    related_items_ids: selectedAllSubcategoryIds,
                    calc_prod: calc_prod,
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                alert('Категория успешно обновлена');
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Произошла ошибка при обновлении категории');
            });
    });
</script>





{{-- CRUD: Типы установки --}}
@if ($category->id === 16)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.installation-types-container');
    const slug = document.querySelector('.installation-types').dataset.categorySlug;
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Quill instances registry
    const quillInstances = [];

    function initQuillInCard(card) {
        const editors = card.querySelectorAll('.quill-editor');
        editors.forEach(function(editorDiv) {
            // Skip if already initialized
            if (editorDiv.classList.contains('ql-container')) return;
            if (editorDiv.querySelector('.ql-editor')) return;

            const hiddenInput = card.querySelector('input[type="hidden"][name="description"]');
            const initialContent = hiddenInput ? hiddenInput.value : '';

            const quill = new Quill(editorDiv, {
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        [{ 'header': [1, 2, 3, false] }],
                        ['link', 'clean']
                    ]
                },
                theme: 'snow'
            });

            // Set initial content from hidden input
            if (initialContent) {
                quill.root.innerHTML = initialContent;
            }

            quillInstances.push({ quill: quill, card: card, hiddenInput: hiddenInput });
        });
    }

    function syncQuillToHidden(card) {
        quillInstances.forEach(function(entry) {
            if (entry.card === card && entry.hiddenInput) {
                entry.hiddenInput.value = entry.quill.root.innerHTML;
            }
        });
    }

    // Init existing cards
    document.querySelectorAll('.installation-type-card').forEach(initQuillInCard);

    function saveCard(card) {
        const id = card.dataset.id;
        syncQuillToHidden(card);
        const formData = new FormData(card);
        const url = id
            ? `/admin/categories/${slug}/installation-types/${id}`
            : `/admin/categories/${slug}/installation-types`;

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(id ? 'Тип обновлён' : 'Тип создан');
                if (!id && data.type) {
                    card.dataset.id = data.type.id;
                }
            } else {
                alert('Ошибка сохранения');
            }
        })
        .catch(e => console.error('Ошибка:', e));
    }

    function deleteCard(card) {
        const id = card.dataset.id;
        if (!id) { card.remove(); return; }
        if (!confirm('Удалить тип монтажа?')) return;

        fetch(`/admin/categories/${slug}/installation-types/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                card.remove();
                alert('Удалено');
            } else {
                alert('Ошибка удаления');
            }
        })
        .catch(e => console.error('Ошибка:', e));
    }

    function addNewCard() {
        const card = document.createElement('form');
        card.className = 'installation-type-card card mb-3 p-3';
        card.innerHTML = `
            <div class="row">
                <div class="col-md-2">
                    <label>Иконка (50x50)</label>
                    <input name="image" type="file" class="form-control-file">
                </div>
                <div class="col-md-3">
                    <label>Большое фото</label>
                    <input name="detail_image" type="file" class="form-control-file">
                </div>
                <div class="col-md-3">
                    <label>Заголовок</label>
                    <input name="title" type="text" class="form-control">
                </div>
                <div class="col-md-1">
                    <label>Порядок</label>
                    <input name="sort_order" type="number" class="form-control" value="0">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button class="btn btn-primary save-installation-type" type="button">Сохранить</button>
                    <button class="btn btn-danger delete-installation-type" type="button">Удалить</button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <label>Описание (HTML)</label>
                    <div class="quill-editor installation-desc-editor" style="min-height:150px;"></div>
                    <input type="hidden" name="description" value="">
                </div>
            </div>`;
        container.appendChild(card);
        initQuillInCard(card);
    }

    container.addEventListener('click', function(e) {
        const card = e.target.closest('.installation-type-card');
        if (!card) return;
        if (e.target.classList.contains('save-installation-type')) {
            saveCard(card);
        } else if (e.target.classList.contains('delete-installation-type')) {
            deleteCard(card);
        }
    });

    document.querySelector('.add-installation-type').addEventListener('click', addNewCard);
});

// CRUD: Системы управления рольставнями
(function() {
    const sysContainer = document.querySelector('.roller-systems-container');
    const sysSlug = document.querySelector('.roller-shutter-systems')?.dataset.categorySlug;
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    if (!sysContainer || !sysSlug) return;

    // Quill instances for systems
    const sysQuillInstances = [];

    function initQuillInSystemCard(card) {
        const editors = card.querySelectorAll('.quill-editor');
        editors.forEach(function(editorDiv) {
            if (editorDiv.classList.contains('ql-container')) return;
            if (editorDiv.querySelector('.ql-editor')) return;

            const hiddenInput = card.querySelector('input[type="hidden"][name="description"]');
            const initialContent = hiddenInput ? hiddenInput.value : '';

            const quill = new Quill(editorDiv, {
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        [{ 'header': [1, 2, 3, false] }],
                        ['link', 'clean']
                    ]
                },
                theme: 'snow'
            });

            if (initialContent) {
                quill.root.innerHTML = initialContent;
            }

            sysQuillInstances.push({ quill: quill, card: card, hiddenInput: hiddenInput });
        });
    }

    function syncSystemQuillToHidden(card) {
        sysQuillInstances.forEach(function(entry) {
            if (entry.card === card && entry.hiddenInput) {
                entry.hiddenInput.value = entry.quill.root.innerHTML;
            }
        });
    }

    // Init existing system cards
    document.querySelectorAll('.roller-system-card').forEach(initQuillInSystemCard);

    function saveSystem(card) {
        const id = card.dataset.id;
        syncSystemQuillToHidden(card);
        const formData = new FormData(card);
        const url = id
            ? `/admin/categories/${sysSlug}/roller-shutter-systems/${id}`
            : `/admin/categories/${sysSlug}/roller-shutter-systems`;

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(id ? 'Система обновлена' : 'Система создана');
                if (!id && data.system) card.dataset.id = data.system.id;
            } else {
                alert('Ошибка сохранения');
            }
        })
        .catch(e => console.error('Ошибка:', e));
    }

    function deleteSystem(card) {
        const id = card.dataset.id;
        if (!id) { card.remove(); return; }
        if (!confirm('Удалить систему?')) return;
        fetch(`/admin/categories/${sysSlug}/roller-shutter-systems/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { card.remove(); alert('Удалено'); }
            else alert('Ошибка удаления');
        })
        .catch(e => console.error('Ошибка:', e));
    }

    function addNewSystem() {
        const card = document.createElement('form');
        card.className = 'roller-system-card card mb-3 p-3';
        card.innerHTML = `
            <div class="row">
                <div class="col-md-2">
                    <label>Изображение</label>
                    <input name="image" type="file" class="form-control-file">
                </div>
                <div class="col-md-3">
                    <label>Название системы</label>
                    <input name="title" type="text" class="form-control">
                </div>
                <div class="col-md-1">
                    <label>Порядок</label>
                    <input name="sort_order" type="number" class="form-control" value="0">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button class="btn btn-primary save-roller-system" type="button">Сохранить</button>
                    <button class="btn btn-danger delete-roller-system" type="button">Удалить</button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <label>Описание (HTML)</label>
                    <div class="quill-editor system-desc-editor" style="min-height:150px;"></div>
                    <input type="hidden" name="description" value="">
                </div>
                <div class="col-md-6">
                    <label>Список компонентов (по одному на строку)</label>
                    <textarea name="components" class="form-control" rows="5"></textarea>
                </div>
            </div>`;
        sysContainer.appendChild(card);
        initQuillInSystemCard(card);
    }

    sysContainer.addEventListener('click', function(e) {
        const card = e.target.closest('.roller-system-card');
        if (!card) return;
        if (e.target.classList.contains('save-roller-system')) saveSystem(card);
        else if (e.target.classList.contains('delete-roller-system')) deleteSystem(card);
    });

    document.querySelector('.add-roller-system')?.addEventListener('click', addNewSystem);
})();
</script>
@endif

<x-admin.footer></x-admin.footer>
