<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Редактировать Подкатегорию {{ $subcategory->titleh1 }}</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Первый экран</h5>
            <div class="card-body">
                <form id="subcategoryEditForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="title">Заголовок(meta)</label>
                        <input id="title" name="title" type="text" class="form-control"
                            value="{{ $subcategory->title }}">
                    </div>
                    <div class="form-group">
                        <label for="description">Описание(meta)</label>
                        <input id="description" name="description" type="text" class="form-control"
                            value="{{ $subcategory->description }}">
                    </div>
                    <div class="form-group">
                        <label for="slug">slug</label>
                        <input id="slug" name="slug" type="text" class="form-control"
                            value="{{ $subcategory->slug }}">
                    </div>
                    <div class="form-group">
                        <label for="titleh1">Заголовок h1</label>
                        <input id="titleh1" name="titleh1" type="text" class="form-control"
                            value="{{ $subcategory->titleh1 }}">
                    </div>
                    <div class="form-group">
                        <label for="title">Название в меню</label>
                        <input id="menu_title" name="menu_title" type="text" class="form-control"
                            value="{{ $subcategory->menu_title }}">
                    </div>
                    <div class="form-group">
                        <label for="first_screen_text">Текст</label>
                        <textarea class="form-control" name="first_screen_text" id="first_screen_text">{{ $subcategory->first_screen_text }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="img">Фото подкатегории</label>
                        <input name="img" type="file" class="form-control">
                        <img src="{{ Storage::url($subcategory->img) }}" alt="">
                    </div>


                    <button class="btn btn-primary" type="button" id="saveSubcategoryBtn">Сохранить</button>
                </form>

            </div>

        </div>
    </div>
</div>

{{-- ВидеоОтзывы --}}

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Видеообзоры</h5>
            <div class="card-body">
                <div class="videoReviewCards" data-category-id="{{ $category->id }}"
                    data-subcategory-id="{{ $subcategory->id ?? '' }}">

                    @foreach ($videoReviews as $videoReview)
                        @if (
                            $videoReview->category_id == $category->id &&
                                ($subcategory->id ? $videoReview->subcategory_id == $subcategory->id : true))
                            <form class="videoReviewCards_card" data-id="{{ $videoReview->id }}"
                                id="{{ $videoReview->id }}" enctype="multipart/form-data">
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
                                        <video controls style="max-width: 200px;">
                                            <source src="{{ Storage::url($videoReview->video) }}" type="video/mp4">
                                        </video>
                                    @endif
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
                                <a class="btn btn-outline-secondary delete-video-review-button">Удалить</a>
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
{{-- Форма загрузки примеров работ --}}
<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Примеры работ</h5>
            <div class="card-body">
                <div class="work-examples-gallery">

                    {{-- Поле для загрузки изображений --}}
                    <div id="dropzone" class="dropzone">
                        Перетащите файлы сюда или нажмите, чтобы выбрать.
                        <input type="file" id="workExamplesInput" multiple style="display: none;">
                    </div>

                    <button id="uploadWorkExamplesBtn" class="btn btn-primary">Загрузить изображения</button>

                    <div id="workExamplesContainer">
                        @if ($workExamples->isNotEmpty())

                            @foreach ($workExamples as $workExample)
                                <div class="work-example-card" data-id="{{ $workExample->id }}">
                                    <img src="/storage/{{ $workExample->image }}" alt="Work Example Image"
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

                    {{-- Скрытые поля для передачи category_id или subcategory_id --}}
                    <input type="hidden" id="categoryId" value="{{ $category->id }}">

                </div>
            </div>
        </div>
    </div>
</div>


{{-- Секция Сео --}}

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Секция СЕО текст</h5>
            <div class="card-body">
                <form>
                    <div class="form-group">
                        <label for="seoEditor">Редактировать</label>
                        <button id="toggle-editor" type="button" style="margin-bottom: 10px;">Редактировать
                            HTML</button>
                        <div id="editor-container">
                            <div id="seoEditor">
                                {!! $subcategory->seo !!}
                            </div>
                        </div>

                    </div>
                    <button class="btn btn-primary" type="button" id="saveSeoButton">Сохранить</button>
                </form>
            </div>

        </div>
    </div>
</div>

{{-- Секция FAQ HTML --}}

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Секция Вопросы и ответы (HTML)</h5>
            <div class="card-body">
                <form>
                    <div class="form-group">
                        <label for="faqHtmlEditor">HTML контент</label>
                        <textarea name="faq_html" id="faqHtmlEditor" class="form-control" rows="20">{!! $subcategory->faq_html !!}</textarea>
                    </div>
                    <button class="btn btn-primary" type="button" id="saveFaqHtmlButton">Сохранить</button>
                </form>
            </div>

        </div>
    </div>
</div>

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


@if ((int) $subcategory->category_id === 16)
<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Выбор шаблона </h5>
            <div class="card-body">
                <div class="form-group">
                    <label for="template_variant">Шаблон (редактирование/фронтент)</label>
                    <select class="form-control" name="template_variant" id="template_variant">
                        <option value="1" {{ (int) ($subcategory->template_variant ?? 1) === 1 ? 'selected' : '' }}>Сантех роллеты 1</option>
                        <option value="2" {{ (int) ($subcategory->template_variant ?? 1) === 2 ? 'selected' : '' }}>Шаблон 2</option>
                    </select>
                </div>
                <button class="btn btn-primary" type="button" id="applyTemplateButton">Сохранить</button>
            </div>
        </div>
    </div>
</div>
@endif

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
                            {{ $subcategory->show_in_menu ? 'checked' : '' }}>
                    </div>

                    <div class="form-group">
                        <label for="show_in_catalog">Показывать в каталоге</label>
                        <input type="checkbox" id="show_in_catalog" name="show_in_catalog"
                            {{ $subcategory->show_in_catalog ? 'checked' : '' }}>
                    </div>

                    <div class="form-group">
                        <label for="show_in_catalog">Показывать в блоке большой выбор</label>
                        <input type="checkbox" id="show_in_more_cats" name="show_in_more_cats"
                            {{ $subcategory->show_in_more_cats ? 'checked' : '' }}>
                    </div>

                    <div class="form-group">
                        <label for="show_in_catalog">Показывать в фильтре на странице категории</label>
                        <input type="checkbox" id="show_in_cats_filter" name="show_in_cats_filter"
                            {{ $subcategory->show_in_cats_filter ? 'checked' : '' }}>
                    </div>

                    

                    <div class="form-group">
                        <label for="clone_subcategory_id">Клонировать товары из подкатегории</label>
                        <select class="form-control" name="clone_subcategory_id" id="clone_subcategory_id">
                            <option value="">Не выбрано</option>
                            @foreach ($subcategories as $subcategoryOption)
                                <option value="{{ $subcategoryOption->id }}"
                                    @if (isset($subcategory) && $subcategory->clone_subcategory_id == $subcategoryOption->id) selected @endif>
                                    {{ $subcategoryOption->titleh1 }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="all_subcategory_id">Все категории</label>
                        <select style="min-height: 300px;" class="form-control" name="all_subcategory_id[]"
                            id="all_subcategory_id" multiple>
                            @foreach ($subcategories as $subcategoryOption)
                                <option value="{{ $subcategoryOption->id }}"
                                    @if (in_array($subcategoryOption->id, $relatedIds)) selected @endif>
                                    {{ $subcategoryOption->titleh1 ?? $subcategoryOption->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_material">Материал для старта фильтра</label>
                        <select name="start_material" id="start_material" class="form-control">
                            <option value="">Не выбрано</option>
                            @foreach ($materials as $material)
                                <option value="{{ $material }}"
                                    {{ $subcategory->start_material === $material ? 'selected' : '' }}>
                                    {{ $material }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="filter_color">Цвет для фильтра</label>
                        <input id="filter_color" name="filter_color" type="text" class="form-control"
                            value="{{ $subcategory->filter_color }}">
                    </div>
                    <div class="form-group">
                        <label for="calc_prod">ID товара в блоке "Как заказать"</label>
                        <input type="number" class="form-control" id="calc_prod" name="calc_prod"
                            value="{{ $subcategory->calc_prod }}">
                    </div>

                    <div class="form-group">
                        <label for="model_id_to_filter">Все модели</label>
                        <select style="min-height: 300px;" class="form-control" name="model_id_to_filter[]"
                            id="model_id_to_filter" multiple>
                            
                            @foreach ($models as $model)
                                <option value="{{ $model->id }}" @if (in_array($model->id, $relatedIds)) selected @endif>
                                    {{ $model->h1 ?? $model->title }}
                                </option>
                            @endforeach
                        </select>
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

{{-- Первый экран --}}

<script>
    document.getElementById('saveSubcategoryBtn').addEventListener('click', function() {
        let form = document.getElementById('subcategoryEditForm');
        let formData = new FormData(form);

        fetch('{{ route('subcategories.update', ['category_slug' => $category->slug, 'subcategory_slug' => $subcategory->slug]) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.success);
                } else {
                    alert('Произошла ошибка при обновлении подкатегории.');
                }
            })
            .catch(error => console.error('Ошибка:', error));
    });
</script>
{{-- Видео --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function saveVideoReviewCard(card) {
            let formData = new FormData(card);
            console.log(card);

            const cardId = card.getAttribute('data-id');
            const categoryId = document.querySelector('.videoReviewCards').getAttribute('data-category-id');
            const subcategoryId = document.querySelector('.videoReviewCards').getAttribute(
                'data-subcategory-id');
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const url = cardId ? `/admin/video-reviews/${cardId}` : `/admin/video-reviews`;
            const method = cardId ? 'PUT' : 'POST';

            formData.append('_method', method);
            formData.append('category_id', categoryId || null);
            formData.append('subcategory_id', subcategoryId || null);

            fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': token,
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
            newVideoReviewCard.classList.add('video-review-card');

            newVideoReviewCard.innerHTML = `
        <div class="form-group">
            <label for="cover_image">Обложка</label>
            <input name="cover_image" type="file" class="form-control">
        </div>
        <div class="form-group">
            <label for="video">Видео</label>
            <input name="video" type="file" class="form-control">
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
        <input type="hidden" name="subcategory_id" value="${subcategoryId}">
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
                if (card) saveVideoReviewCard(card);
            }

            if (e.target.classList.contains('delete-video-review-button')) {
                const card = e.target.closest('.videoReviewCards_card');
                if (card) deleteVideoReviewCard(card);
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

        // Fetch existing gallery images on page load
        fetchExistingWorkExamples();

        // Use event delegation for drag and drop
        document.addEventListener('dragover', function(e) {
            if (e.target.id === 'dropzone') {
                e.preventDefault();
                e.target.classList.add('dragover');
            }
        });

        document.addEventListener('dragleave', function(e) {
            if (e.target.id === 'dropzone') {
                e.preventDefault();
                e.target.classList.remove('dragover');
            }
        });

        document.addEventListener('drop', function(e) {
            if (e.target.id === 'dropzone') {
                e.preventDefault();
                e.target.classList.remove('dragover');

                const droppedFiles = e.dataTransfer.files;
                for (let i = 0; i < droppedFiles.length; i++) {
                    filesToUpload.push(droppedFiles[i]);
                }

                displayFiles(filesToUpload);
            }
        });

        // Open file manager on click
        document.addEventListener('click', function(e) {
            if (e.target.id === 'dropzone' || e.target.closest('#dropzone')) {
                input.click();
            }
        });

        // Handle file input selection
        input.addEventListener('change', function(e) {
            const selectedFiles = e.target.files;
            for (let i = 0; i < selectedFiles.length; i++) {
                filesToUpload.push(selectedFiles[i]);
            }

            displayFiles(filesToUpload);
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
            formData.append('subcategory_id', '{{ $subcategory->id }}'); // Добавляем ID категории

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
            <img src="/storage/${workExample.image}" alt="Work Example Image" style="max-width: 100px;">
            <input placeholder="Название" name="title" type="text" class="form-control" value="${workExample.title}"><label for="description">Описание</label>
            <textarea name="description" class="form-control">${workExample.description}</textarea>
            <button class="btn btn-primary save-work-example">Сохранить</button>
            <button class="btn btn-danger delete-work-example">Удалить</button>
        `;
            container.appendChild(card);
        }

        // Fetch existing work examples from the server and render them
        function fetchExistingWorkExamples() {
            fetch('/categories/{category_slug}/{subcategory_slug}/edit') // Adjust URL as needed
                .then(response => response.json())
                .then(data => {
                    data.workExamples.forEach(renderWorkExample); // Render all work examples
                })
                .catch(error => console.error('Error fetching work examples:', error));
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


{{-- Сео текст --}}
<style>
    #editor-container .ql-editor img {
        display: block;
        max-width: min(100%, 520px);
        height: auto;
        margin: 14px auto;
        cursor: pointer;
    }

    #editor-container .ql-editor img.seo-editor-image-selected {
        outline: 3px solid #5969ff;
        outline-offset: 3px;
    }

    .seo-image-tools {
        display: none;
        gap: 10px;
        align-items: end;
        flex-wrap: wrap;
        margin-top: 12px;
        padding: 12px;
        border: 1px solid #d8dbe8;
        border-radius: 6px;
        background: #f8f9ff;
    }

    .seo-image-tools.is-visible {
        display: flex;
    }

    .seo-image-tools label {
        margin-bottom: 0;
        font-size: 12px;
        color: #525f7f;
    }

    .seo-image-tools .seo-image-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .seo-image-tools input {
        min-width: 160px;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const seoImageUploadUrl = "{{ route('subcategory.seo.upload_image') }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
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

        let quill = new Quill('#seoEditor', {
            modules: {
                toolbar: toolbarOptions
            },
            theme: 'snow'
        });
        const toolbar = quill.getModule('toolbar');
        toolbar.addHandler('image', selectAndUploadSeoImage);

        let toggleButton = document.getElementById("toggle-editor");
        let htmlEditor = document.createElement("textarea");
        htmlEditor.id = "html-editor";
        htmlEditor.style.display = "none";
        htmlEditor.style.width = "100%";
        htmlEditor.style.height = "300px";
        htmlEditor.style.fontFamily = "Consolas, Monaco, monospace";
        htmlEditor.style.fontSize = "13px";
        htmlEditor.setAttribute("wrap", "soft");

        let selectedSeoImage = null;
        let imageTools = document.createElement("div");
        imageTools.className = "seo-image-tools";
        imageTools.innerHTML = `
            <div class="seo-image-field">
                <label for="seo-image-alt">Alt</label>
                <input id="seo-image-alt" type="text" class="form-control" placeholder="Описание изображения">
            </div>
            <div class="seo-image-field">
                <label for="seo-image-width">Ширина, px</label>
                <input id="seo-image-width" type="number" min="120" max="1200" step="10" class="form-control" placeholder="Авто">
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="seo-image-apply">Применить</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="seo-image-reset">Авторазмер</button>
            <button type="button" class="btn btn-danger btn-sm" id="seo-image-remove">Удалить</button>
        `;

        // Вставим textarea сразу после редактора
        document.getElementById("editor-container").appendChild(htmlEditor);
        document.getElementById("editor-container").appendChild(imageTools);

        let quillContainer = document.querySelector(".ql-container");
        let isHtmlMode = false;
        const imageAltInput = document.getElementById("seo-image-alt");
        const imageWidthInput = document.getElementById("seo-image-width");

        function selectAndUploadSeoImage() {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/jpeg,image/png,image/gif,image/webp');
            input.click();

            input.addEventListener('change', function() {
                const file = input.files && input.files[0];
                if (!file) {
                    return;
                }

                const formData = new FormData();
                formData.append('image', file);

                fetch(seoImageUploadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: formData
                    })
                    .then(response => response.ok ? response.json() : response.json().then(data => Promise.reject(data)))
                    .then(data => {
                        if (!data.url) {
                            throw new Error('Image URL is missing');
                        }

                        const range = quill.getSelection(true);
                        quill.insertEmbed(range.index, 'image', data.url, 'user');
                        quill.setSelection(range.index + 1, 0, 'silent');
                        const image = Array.from(quill.root.querySelectorAll('img'))
                            .find(item => item.getAttribute('src') === data.url);
                        if (image) {
                            image.setAttribute('alt', '');
                            image.style.maxWidth = '100%';
                            image.style.height = 'auto';
                            selectSeoImage(image);
                        }
                    })
                    .catch(error => {
                        console.error('SEO image upload failed:', error);
                        alert('Не удалось загрузить изображение. Проверьте формат и размер файла.');
                    });
            });
        }

        function selectSeoImage(image) {
            clearSeoImageSelection();
            selectedSeoImage = image;
            selectedSeoImage.classList.add('seo-editor-image-selected');
            imageAltInput.value = selectedSeoImage.getAttribute('alt') || '';
            imageWidthInput.value = parseInt(selectedSeoImage.getAttribute('width') || selectedSeoImage.style.width, 10) || '';
            imageTools.classList.add('is-visible');
        }

        function clearSeoImageSelection() {
            if (selectedSeoImage) {
                selectedSeoImage.classList.remove('seo-editor-image-selected');
            }
            selectedSeoImage = null;
            imageTools.classList.remove('is-visible');
        }

        function applySeoImageSettings() {
            if (!selectedSeoImage) {
                return;
            }

            selectedSeoImage.setAttribute('alt', imageAltInput.value.trim());
            const width = parseInt(imageWidthInput.value, 10);
            if (width > 0) {
                selectedSeoImage.setAttribute('width', width);
                selectedSeoImage.style.width = `${width}px`;
                selectedSeoImage.style.maxWidth = '100%';
                selectedSeoImage.style.height = 'auto';
            }
        }

        quill.root.addEventListener('click', function(event) {
            if (event.target && event.target.tagName === 'IMG') {
                selectSeoImage(event.target);
                return;
            }

            clearSeoImageSelection();
        });

        document.getElementById('seo-image-apply').addEventListener('click', applySeoImageSettings);
        document.getElementById('seo-image-reset').addEventListener('click', function() {
            if (!selectedSeoImage) {
                return;
            }

            selectedSeoImage.removeAttribute('width');
            selectedSeoImage.style.width = '';
            selectedSeoImage.style.maxWidth = '100%';
            selectedSeoImage.style.height = 'auto';
            imageWidthInput.value = '';
        });
        document.getElementById('seo-image-remove').addEventListener('click', function() {
            if (!selectedSeoImage) {
                return;
            }

            selectedSeoImage.remove();
            clearSeoImageSelection();
        });

        toggleButton.addEventListener("click", function() {
            if (!isHtmlMode) {
                // Переключение в HTML
                htmlEditor.value = quill.root.innerHTML;
                htmlEditor.style.display = "block";
                quillContainer.style.display = "none";
                imageTools.classList.remove('is-visible');
                toggleButton.textContent = "Редактировать в Quill";
            } else {
                // Переключение обратно в Quill
                quill.root.innerHTML = htmlEditor.value;
                htmlEditor.style.display = "none";
                quillContainer.style.display = "block";
                clearSeoImageSelection();
                toggleButton.textContent = "Редактировать HTML";
            }
            isHtmlMode = !isHtmlMode;
        });

        document.getElementById('saveSeoButton').addEventListener('click', function() {
            const content = isHtmlMode ? htmlEditor.value : quill.root.innerHTML;

            if (quill.getText().trim() === '') {
                alert('Контент пустой, введите текст.');
                return;
            }

            fetch(`/admin/categories/{{ $category->slug }}/{{ $subcategory->slug }}/edit-seo`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        seo: content
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

{{-- FAQ HTML --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Save FAQ HTML content
        document.getElementById('saveFaqHtmlButton').addEventListener('click', function() {
            const content = document.getElementById('faqHtmlEditor').value;
            
            if (!content.trim()) {
                alert('Контент пустой, введите текст.');
                return;
            }

            fetch(`/admin/categories/{{ $category->slug }}/{{ $subcategory->slug }}/edit-faq`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({
                        faq_html: content
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.message) {
                        alert(data.message);
                    } else {
                        alert('FAQ контент успешно обновлен!');
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    alert('Произошла ошибка при отправке данных');
                });
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



{{-- Меню --}}
<script>
    document.getElementById('saveCategoryButton').addEventListener('click', function() {
        const showInMenu = document.getElementById('show_in_menu').checked ? 1 : 0;
        const showInCatalog = document.getElementById('show_in_catalog').checked ? 1 : 0;
        const clone_subcategory_id = document.getElementById('clone_subcategory_id').value;
        const selectedAllSubcategoryIds = Array.from(document.getElementById('all_subcategory_id')
                .selectedOptions)
            .map(option => option.value);

        const start_material = document.getElementById('start_material').value;
        const filter_color = document.getElementById('filter_color').value;
        const show_in_more_cats = document.getElementById('show_in_more_cats').checked ? 1 : 0;
        const show_in_cats_filter = document.getElementById('show_in_cats_filter').checked ? 1 : 0;

        const calc_prod = document.getElementById('calc_prod').value

        const model_id_to_filter = Array.from(document.getElementById('model_id_to_filter')
                .selectedOptions)
            .map(option => option.value);

        console.log(selectedAllSubcategoryIds);


        fetch(`/admin/categories/{{ $category->slug }}/{{ $subcategory->slug }}/update-visibility`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                        'content')
                },
                body: JSON.stringify({
                    show_in_menu: showInMenu,
                    show_in_catalog: showInCatalog,
                    clone_subcategory_id: clone_subcategory_id,
                    all_subcategory_ids: selectedAllSubcategoryIds,
                    start_material: start_material,
                    filter_color: filter_color,
                    show_in_more_cats: show_in_more_cats,
                    show_in_cats_filter: show_in_cats_filter,
                    calc_prod: calc_prod,
                    model_id_to_filter: model_id_to_filter,


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

<x-admin.footer></x-admin.footer>



<script>
    document.addEventListener('DOMContentLoaded', function() { 
        const applyTemplateButton = document.getElementById('applyTemplateButton');
        if (!applyTemplateButton) {
            return;
        }

        applyTemplateButton.addEventListener('click', function() {
            const templateVariant = parseInt(document.getElementById('template_variant').value, 10);
            const requestUrl = `{{ route('subcategory.update.template', ['category_slug' => $category->slug, 'subcategory_slug' => $subcategory->slug]) }}`;

            fetch(requestUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        template_variant: templateVariant
                    })
                })
                .then(async response => {
                    const responseText = await response.text();
                    let payload = null;

                    try {
                        payload = responseText ? JSON.parse(responseText) : null;
                    } catch (e) {
                        payload = null;
                    }

                    if (!response.ok) {
                        const message = (payload && payload.message)
                            ? payload.message
                            : `HTTP ${response.status}`;
                        throw new Error(`${message} (${requestUrl})`);
                    }

                    return payload || {};
                })
                .then(data => {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return;
                    }
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(`Template switch failed: ${error.message}`);
                });
        });
    });
</script>
