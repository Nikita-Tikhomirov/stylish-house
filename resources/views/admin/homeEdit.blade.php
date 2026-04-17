<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<style>
    .deliveryCards {
        margin-top: 20px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-start;
        width: 100%;
    }

    .deliveryCards .card-header {
        width: 100%;

    }

    .deliveryCards_card {
        width: 100%;
    }

    .deliveryCards__addCard {
        margin-top: 20px;
        order: 10
    }

    .deliveryCards_delete {
        color: #ff407b !important;
    }

    .deliveryCards_delete:hover {
        color: #fff !important
    }

    .faq {
        display: flex;
        flex-direction: column;
        width: 100%;
        align-items: flex-start;
        justify-content: flex-start;

    }

    .add-faq-button {
        order: 10;
        margin-top: 20px;
    }

    .faq-cards-container {
        width: 100%;
    }

    .faq-card:not(:last-child) {
        margin-bottom: 20px;
    }

    .reviews {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-start;
        width: 100%;
    }

    .reviews button {
        order: 10;
        margin-top: 10px;
    }

    .review-card button {
        margin-top: 0;
    }

    .review-cards-container {
        width: 100%;
    }

    .review-cards-container .form-group img {
        margin-top: 20px;
    }
</style>
<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Редактировать Главную</h1>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Первый экран</h5>
            <div class="card-body">
                <form id="categoryEditForm1" enctype="multipart/form-data">
                    @csrf
                    @method('POST')

                    <div class="form-group">
                        <label for="meta_title1">Заголовок(meta)</label>
                        <input id="meta_title1" name="meta_title" type="text" class="form-control"
                            value="{{$homePageFields->meta_title}}">
                    </div>
                    <div class="form-group">
                        <label for="meta_description1">Описание(meta)</label>
                        <textarea id="meta_description1" name="meta_description"
                            class="form-control">{{$homePageFields->meta_description}}</textarea>

                    </div>


                    <button class="btn btn-primary" type="button" id="saveCategoryBtn1">Сохранить</button>
                </form>
            </div>

        </div>
    </div>
</div>


<div class="row">
    <div class="col-12">
        <div class="card">
            <h5 class="card-header">Слайдер главной страницы</h5>
            <div class="card-body slider">
                <div class="slider-cards-container">
                    @foreach ($mainSlider as $slide)
                        <div class="slider-card">
                            <form class="slider-form" data-id="{{ $slide->id }}">
                                @csrf
                                <input type="hidden" name="_method" value="PUT">
                                <div class="form-group">
                                    <label>Заголовок</label>
                                    <input type="text" name="title" class="form-control" value="{{ $slide->title }}">
                                </div>
                                <div class="form-group">
                                    <label>Описание</label>
                                    <textarea name="description" class="form-control">{{ $slide->description }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Ссылка</label>
                                    <input type="url" name="link" class="form-control" value="{{ $slide->link }}">
                                </div>
                                <div class="form-group">
                                    <label>Редактировать Фото<label>
                                            <input type="file" name="image_path" id="image_path">
                                </div>
                                <div class="form-group">
                                    {{-- <label>Фото</label> --}}
                                    <img class="mainSliderImg" src="{{ Storage::url($slide->image_path) }}" alt="">
                                </div>
                                <button type="button" class="btn btn-primary update-slide-button">Обновить</button>
                                <button type="button" class="btn btn-danger delete-slide-button"
                                    data-id="{{ $slide->id }}">Удалить</button>
                            </form>
                        </div>
                    @endforeach
                </div>
                <button class="btn btn-success add-slider-button">Добавить слайд</button>
            </div>
        </div>
    </div>
</div>
<style>
    .mainSliderImg {
        max-width: 100%;
        display: block;
    }
</style>
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sliderCardsContainer = document.querySelector('.slider-cards-container');

        // Генерация новой карточки слайда
        document.querySelector('.add-slider-button').addEventListener('click', function () {
            const slideForm = `
            <div class="slider-card">
                <form class="slider-form">
                    <div class="form-group">
                        <label>Заголовок</label>
                        <input type="text" name="title" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Описание</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Ссылка</label>
                        <input type="url" name="link" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Изображение</label>
                        <input type="file" name="image_path" class="form-control">
                    </div>
                    <button type="button" class="btn btn-primary add-slide-button">Сохранить</button>
                    <button type="button" class="btn btn-danger delete-slide-button">Удалить</button>
                </form>
            </div>
        `;
            sliderCardsContainer.insertAdjacentHTML('beforeend', slideForm);
            addDeleteEventListeners();
            addSaveEventListeners();
        });

        function addSaveEventListeners() {
            // Обработчик для создания нового слайда
            document.querySelectorAll('.add-slide-button').forEach(button => {
                button.addEventListener('click', function () {
                    const form = button.closest('.slider-form');
                    console.log(form);
                    const formData = new FormData(form);
                    console.log(formData);

                    fetch('/admin/first-screen-sliders/save', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Слайд успешно создан.');
                                form.setAttribute('data-id', data.slider.id);
                                // Обновляем кнопку для редактирования
                                button.classList.remove('add-slide-button');
                                button.classList.add('edit-slide-button');
                                button.textContent = 'Сохранить изменения';
                            } else {
                                alert('Ошибка при создании слайда.');
                            }
                        })
                        .catch(error => {
                            console.error('Ошибка:', error);
                            alert('Произошла ошибка при создании слайда.');
                        });
                });
            });

            // Обработчик для редактирования существующего слайда
            document.querySelectorAll('.update-slide-button').forEach(button => {
                button.addEventListener('click', function () {
                    const form = button.closest('.slider-form');
                    const sliderId = form.getAttribute('data-id');
                    const formData = new FormData(form);

                    fetch(`/admin/first-screen-sliders/${sliderId}`, {
                        method: 'POST', // Используем POST, так как передаем файлы
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Слайд успешно обновлен.');
                                if (formData.has('image_path')) {
                                    const newImageUrl = `/storage/${data.image_path}`; // Формируем новый путь
                                    form.querySelector('.mainSliderImg').src = newImageUrl;
                                }
                            } else {
                                alert('Ошибка при обновлении слайда.');
                            }
                        })
                        .catch(error => {
                            console.error('Ошибка:', error);
                            alert('Произошла ошибка при обновлении слайда.');
                        });
                });
            });
        }

        // Функция для добавления обработчиков удаления
        function addDeleteEventListeners() {
            document.querySelectorAll('.delete-slide-button').forEach(button => {
                button.addEventListener('click', function () {
                    const slideCard = button.closest('.slider-card');
                    console.log(slideCard);
                    const slideId = slideCard.querySelector('form').getAttribute('data-id');
                    console.log(slideId);


                    fetch(`/admin/first-screen-sliders/destroy/${slideId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                slideCard.remove();
                                alert('Слайд удален.');
                            } else {
                                alert('Ошибка при удалении.');
                            }
                        })
                        .catch(error => {
                            console.error('Ошибка:', error);
                            alert('Произошла ошибка при удалении.');
                        });


                });
            });
        }

        // Инициализация обработчиков для существующих слайдов
        addDeleteEventListeners();
        addSaveEventListeners();
    });

</script>



{{-- <div class="row">
    <div class="col-12">
        <div class="card">
            <h5 class="card-header">Первый экран</h5>
            <div class="card-body slider">


                <!-- Контейнер для карточек слайдов -->
                <div class="slider-cards-container">
                    <!-- Сюда будут добавляться карточки слайдов -->
                    @foreach ($sliders as $slider)
                    <div class="slider-card">

                        <form class="slider-form" data-id="{{ $slider->id }}">
                            <input type="hidden" name="_method" value="PUT">
                            <div class="form-group">
                                <label for="subtitle">Подзаголовок</label>
                                <input name="subtitle" type="text" class="form-control" value="{{ $slider->subtitle }}">
                            </div>
                            <div class="form-group">
                                <label for="title">Заголовок</label>
                                <textarea name="title" class="form-control">{{ $slider->title }}</textarea>
                            </div>
                            <div class="form-group">
                                <label for="description_start">Описание до желтого текста</label>
                                <input name="description_start" type="text" class="form-control"
                                    value="{{ $slider->description_start }}">
                            </div>
                            <div class="form-group">
                                <label for="description_colored">Желтый текст</label>
                                <input name="description_colored" type="text" class="form-control"
                                    value="{{ $slider->description_colored }}">
                            </div>
                            <div class="form-group">
                                <label for="description_end">Описание после желтого текста</label>
                                <input name="description_end" type="text" class="form-control"
                                    value="{{ $slider->description_end }}">
                            </div>
                            <div class="form-group">
                                <label for="model_id">Модель</label>
                                <select name="model_id" class="form-control">
                                    <option value="">-- Выберите модель --</option>
                                    @foreach ($models as $model)
                                    <option value="{{ $model->id }}" {{ $model->id == $slider->model_id ? 'selected' :
                                        '' }}>
                                        {{ $model->title }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-primary update-slide-button" type="button">Обновить</button>
                            <a class="btn btn-outline-secondary delete-slide-button"
                                data-id="{{ $slider->id }}">Удалить</a>
                        </form>
                    </div>
                    @endforeach
                </div>
                <!-- Кнопка для добавления нового слайда -->
                <button class="btn btn-primary add-slider-button">Добавить слайд</button>
            </div>
        </div>
    </div>
</div> --}}

{{--
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sliderCardsContainer = document.querySelector('.slider-cards-container');

        // Функция для добавления обработчиков
        function addEventListeners() {
            // Обработчик для кнопки сохранения
            document.querySelectorAll('.save-slide-button').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    let formData = new FormData(button.closest('form'));

                    fetch("{{ route('admin.sliders.store') }}", {
                        method: "POST",
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Слайд создан успешно');
                            } else {
                                alert('Ошибка создания слайда');
                            }
                        })
                        .catch(error => console.error('Ошибка:', error));
                });
            });

            // Обработчик для кнопки обновления
            document.querySelectorAll('.update-slide-button').forEach(button => {
                button.addEventListener('click', function () {
                    const form = button.closest('.slider-form');
                    const slideId = form.getAttribute('data-id');
                    let formData = new FormData(form);
                    formData.append('_method', 'PUT'); // Добавляем метод PUT
                    console.log(formData.get('title')); // Логирование для проверки

                    fetch(`{{ route('admin.sliders.update', ':id') }}`.replace(':id',
                        slideId), {
                        method: "POST", // Используем POST
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Слайд обновлен успешно');
                            } else {
                                alert(data.message || 'Ошибка обновления слайда');
                            }
                        })
                        .catch(error => console.error('Ошибка:', error));
                });
            });



            // Обработчик для кнопки удаления
            document.querySelectorAll('.delete-slide-button').forEach(button => {
                button.addEventListener('click', function () {
                    const slideId = button.getAttribute('data-id');

                    fetch(`/admin/sliders/${slideId}`, {
                        method: "DELETE",
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]').getAttribute('content'),
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                button.closest('.slider-card').remove();
                                alert('Слайд удален успешно');
                            } else {
                                alert('Ошибка удаления слайда');
                            }
                        })
                        .catch(error => console.error('Ошибка:', error));
                });
            });
        }

        // Инициализируем обработчики на старте
        addEventListeners();

        // Кнопка добавления слайда
        document.querySelector('.add-slider-button').addEventListener('click', function () {
            const slideForm = `
            <div class="slider-card">
                <form class="slider-form">
                    <div class="form-group">
                        <label for="subtitle">Подзаголовок</label>
                        <input name="subtitle" type="text" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="title">Заголовок</label>
                        <textarea name="title" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="description_start">Описание до желтого текста</label>
                        <input name="description_start" type="text" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="description_colored">Желтый текст</label>
                        <input name="description_colored" type="text" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="description_end">Описание после желтого текста</label>
                        <input name="description_end" type="text" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="model_id">Модель</label>
                        <select name="model_id" class="form-control">
                            <option value="">-- Выберите модель --</option>
                            @foreach ($models as $model)
                                <option value="{{ $model->id }}">{{ $model->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-primary save-slide-button" type="submit">Сохранить</button>
                    <a class="btn btn-outline-secondary delete-slide-button">Удалить</a>
                </form>
            </div>
        `;

            sliderCardsContainer.insertAdjacentHTML('beforeend', slideForm);

            // Обновляем обработчики после добавления нового слайда
            addEventListeners();
        });
    });
</script> --}}





{{-- Как заказать --}}

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Секция Как заказать</h5>
            <div class="card-body">
                <form>
                    <div class="form-group">
                        <label for="section_request_title">Заголовок</label>
                        <input id="section_request_title" name="section_request_title" type="text" class="form-control"
                            value="{{ $homePageFields->section_request_title }}">
                    </div>
                    <div class="form-group">
                        <label for="section_request_subtitle">Подзаголовок</label>
                        <input id="section_request_subtitle" name="section_request_subtitle" type="text"
                            class="form-control" value="{{ $homePageFields->section_request_subtitle }}">
                    </div>
                    <div class="form-group">
                        <label for="section_request_text">Текст</label>
                        <input id="section_request_text" name="section_request_text" type="text" class="form-control"
                            value="{{ $homePageFields->section_request_text }}">
                    </div>
                    <button class="btn btn-primary" type="button" id="saveSectionRequestButton">Сохранить</button>
                </form>
            </div>

        </div>
    </div>
</div>

{{-- Доставка --}}

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">


        <div class="card">
            <h5 class="card-header">Оплата и доставка</h5>
            <div class="card-body">
                <form>
                    <div class="form-group">
                        <label for="section_delivery_title" class="col-form-label">Заголовок</label>
                        <input id="section_delivery_title" name="section_delivery_title" type="text"
                            class="form-control" value="{{ $homePageFields->section_delivery_title }}">
                    </div>
                    <div class="form-group">
                        <label for="section_delivery_top_text" class="col-form-label">Текст после заголовка</label>
                        <textarea id="section_delivery_top_text" name="section_delivery_top_text"
                            class="form-control">{{ $homePageFields->section_delivery_top_text }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="section_delivery_bottom_text" class="col-form-label">Текст после карточек</label>
                        <textarea id="section_delivery_bottom_text" name="section_delivery_bottom_text"
                            class="form-control">{{ $homePageFields->section_delivery_bottom_text }}</textarea>
                    </div>

                    <button class="btn btn-primary" type="button" id="saveDeliveryTextButton">Сохранить</button>

                </form>

                <div class="deliveryCards">
                    <h5 class="card-header">Карточки</h5>
                    @foreach ($iconCards as $iconCard)
                        <form class="deliveryCards_card" data-id="{{ $iconCard->id }}">
                            <div class="form-group">
                                <label for="icon_class" class="col-form-label">Иконка</label>
                                <input name="icon_class" type="text" class="form-control"
                                    value="{{ $iconCard->icon_class }}">
                            </div>
                            <div class="form-group">
                                <label for="title" class="col-form-label">Заголовок</label>
                                <input name="title" type="text" class="form-control" value="{{ $iconCard->title }}">
                            </div>
                            <div class="form-group">
                                <label for="text" class="col-form-label">Текст</label>
                                <input name="text" type="text" class="form-control" value="{{ $iconCard->text }}">
                            </div>
                            <button class="btn btn-primary" type="button" id="saveDeliveryTextButton">Сохранить</button>
                            <a class="deliveryCards_delete btn btn-outline-secondary">Удалить</a>
                        </form>
                    @endforeach
                    <div class="deliveryCards__addCard deliveryCards__addCardJs btn btn-primary">Добавить карточку
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>


{{-- FAQ --}}

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
                                <input name="title" type="text" class="form-control" value="{{ $faq->title }}">
                            </div>
                            <div class="form-group">
                                <label for="text">Ответ</label>
                                <textarea name="text" class="form-control">{{ $faq->text }}</textarea>
                            </div>
                            <button class="btn btn-primary save-faq-button" type="button">Сохранить</button>
                            <div class="btn btn-outline-secondary delete-faq-button">Удалить</div>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Отзывы --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <h5 class="card-header">Отзывы</h5>
            <div class="card-body reviews">
                <button class="btn btn-primary add-review-button">Добавить отзыв</button>
                <div class="review-cards-container">
                    @foreach ($reviews as $review)
                        <form class="review-card" data-id="{{ $review->id }}">
                            <div class="form-group">
                                <label for="title">Имя клиента</label>
                                <input name="title" type="text" class="form-control" value="{{ $review->title }}">
                            </div>
                            <div class="form-group">
                                <label for="text">Отзыв</label>
                                <textarea name="text" class="form-control">{{ $review->text }}</textarea>
                            </div>
                            <div class="form-group">
                                <label for="avatar">Аватар</label>
                                <input name="avatar" type="file" class="form-control">
                                @if ($review->avatar)
                                    <img src="{{ Storage::url($review->avatar) }}" alt="Avatar" class="img-thumbnail"
                                        style="width: 100px;">
                                @endif
                            </div>
                            <button class="btn btn-primary save-review-button" type="button">Сохранить</button>
                            <a class="btn btn-outline-secondary delete-review-button">Удалить</a>
                        </form>
                    @endforeach
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
                        <div id="seoEditor">
                            {!! $seoSection->content !!}
                        </div>
                    </div>
                    <button class="btn btn-primary" type="button" id="saveSeoButton">Сохранить</button>
                </form>
            </div>

        </div>
    </div>
</div>





<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
{{-- Как заказать --}}
<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById('saveSectionRequestButton').addEventListener('click', function () {

            // Извлечение значений из полей формы
            const sectionDeliveryTitle = document.getElementById('section_request_title').value;
            const sectionDeliveryTopText = document.getElementById('section_request_subtitle').value;
            const sectionDeliveryBottomText = document.getElementById('section_request_text')
                .value;

            // Проверка, что все поля заполнены
            if (!sectionDeliveryTitle || !sectionDeliveryTopText || !sectionDeliveryBottomText) {
                alert('Все поля должны быть заполнены.');
                return;
            }

            // Отправка данных на сервер через fetch
            fetch('{{ route('home.update.requesttext') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                        .getAttribute('content')
                },
                body: JSON.stringify({
                    section_request_title: sectionDeliveryTitle,
                    section_request_subtitle: sectionDeliveryTopText,
                    section_request_text: sectionDeliveryBottomText
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

{{-- Оплата и доставка текст --}}
<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById('saveDeliveryTextButton').addEventListener('click', function () {

            // Извлечение значений из полей формы
            const sectionDeliveryTitle = document.getElementById('section_delivery_title').value;
            const sectionDeliveryTopText = document.getElementById('section_delivery_top_text').value;
            const sectionDeliveryBottomText = document.getElementById('section_delivery_bottom_text')
                .value;

            // Проверка, что все поля заполнены
            if (!sectionDeliveryTitle || !sectionDeliveryTopText || !sectionDeliveryBottomText) {
                alert('Все поля должны быть заполнены.');
                return;
            }

            // Отправка данных на сервер через fetch
            fetch('{{ route('home.update.deliverytext') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                        .getAttribute('content')
                },
                body: JSON.stringify({
                    section_delivery_title: sectionDeliveryTitle,
                    section_delivery_top_text: sectionDeliveryTopText,
                    section_delivery_bottom_text: sectionDeliveryBottomText
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

{{-- Достака карточки --}}

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Сохранение новой или обновленной карточки
        function saveCard(card) {
            let iconClass = card.querySelector('[name="icon_class"]').value;
            let title = card.querySelector('[name="title"]').value;
            let text = card.querySelector('[name="text"]').value;
            let cardId = card.getAttribute('data-id');
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            let url = cardId ? `/admin/icon-cards/${cardId}` : '/admin/icon-cards';
            let method = cardId ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    icon_class: iconClass,
                    title: title,
                    text: text
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.errors) {
                        alert('Ошибки: ' + JSON.stringify(data.errors));
                    } else {
                        alert('Карточка успешно сохранена');
                        if (!cardId) {
                            card.setAttribute('data-id', data.iconCard.id); // Присвоить новый ID карточке
                        }
                    }
                })
                .catch(error => console.error('Ошибка:', error));
        }

        document.querySelectorAll('#saveDeliveryTextButton').forEach(button => {
            button.addEventListener('click', function () {
                let card = this.closest('.deliveryCards_card');
                saveCard(card);
            });
        });

        // Добавление новой карточки (создание новой формы)
        // Добавление новой карточки (создание новой формы)
        document.querySelector('.deliveryCards__addCardJs').addEventListener('click', function () {
            // Создание новой формы карточки с нуля
            let newCard = document.createElement('form');
            newCard.classList.add('deliveryCards_card');

            // Создание поля для ввода иконки
            let iconClassDiv = document.createElement('div');
            iconClassDiv.classList.add('form-group');
            let iconLabel = document.createElement('label');
            iconLabel.setAttribute('for', 'icon_class');
            iconLabel.classList.add('col-form-label');
            iconLabel.textContent = 'Иконка';
            let iconInput = document.createElement('input');
            iconInput.id = 'icon_class';
            iconInput.name = 'icon_class';
            iconInput.type = 'text';
            iconInput.classList.add('form-control');
            iconInput.value = '';
            iconClassDiv.appendChild(iconLabel);
            iconClassDiv.appendChild(iconInput);

            // Создание поля для ввода заголовка
            let titleDiv = document.createElement('div');
            titleDiv.classList.add('form-group');
            let titleLabel = document.createElement('label');
            titleLabel.setAttribute('for', 'title');
            titleLabel.classList.add('col-form-label');
            titleLabel.textContent = 'Заголовок';
            let titleInput = document.createElement('input');
            titleInput.id = 'title';
            titleInput.name = 'title';
            titleInput.type = 'text';
            titleInput.classList.add('form-control');
            titleInput.value = '';
            titleDiv.appendChild(titleLabel);
            titleDiv.appendChild(titleInput);

            // Создание поля для ввода текста
            let textDiv = document.createElement('div');
            textDiv.classList.add('form-group');
            let textLabel = document.createElement('label');
            textLabel.setAttribute('for', 'text');
            textLabel.classList.add('col-form-label');
            textLabel.textContent = 'Текст';
            let textInput = document.createElement('input');
            textInput.id = 'text';
            textInput.name = 'text';
            textInput.type = 'text';
            textInput.classList.add('form-control');
            textInput.value = '';
            textDiv.appendChild(textLabel);
            textDiv.appendChild(textInput);

            // Создание кнопок сохранить и удалить
            let saveButton = document.createElement('button');
            saveButton.classList.add('btn', 'btn-primary');
            saveButton.type = 'button';
            saveButton.id = 'saveDeliveryTextButton';
            saveButton.textContent = 'Сохранить';

            let deleteButton = document.createElement('a');
            deleteButton.classList.add('deliveryCards_delete', 'btn', 'btn-outline-secondary');
            deleteButton.textContent = 'Удалить';

            // Добавляем обработчики событий
            saveButton.addEventListener('click', function () {
                saveCard(newCard);
            });

            deleteButton.addEventListener('click', function () {
                deleteCard(newCard);
            });

            // Вставка всех элементов в форму
            newCard.appendChild(iconClassDiv);
            newCard.appendChild(titleDiv);
            newCard.appendChild(textDiv);
            newCard.appendChild(saveButton);
            newCard.appendChild(deleteButton);

            // Добавление новой карточки в контейнер
            document.querySelector('.deliveryCards').appendChild(newCard);
        });

        // Удаление карточки
        function deleteCard(card) {
            let cardId = card.getAttribute('data-id');
            if (!cardId) {
                card.remove();
                return;
            }

            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`/admin/icon-cards/${cardId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Карточка успешно удалена');
                        card.remove();
                    } else {
                        alert('Ошибка при удалении карточки');
                    }
                })
                .catch(error => console.error('Ошибка:', error));
        }

        document.querySelectorAll('.deliveryCards_delete').forEach(button => {
            button.addEventListener('click', function () {
                let card = this.closest('.deliveryCards_card');
                deleteCard(card);
            });
        });
    });
</script>


{{-- FAQ --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Функция сохранения карточки FAQ
        function saveFaqCard(card) {
            const title = card.querySelector('[name="title"]').value;
            const text = card.querySelector('[name="text"]').value;
            const cardId = card.getAttribute('data-id');
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const url = cardId ? `/admin/faqs/${cardId}` : '/admin/faqs';
            const method = cardId ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    title: title,
                    text: text
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.errors) {
                        alert('Ошибки: ' + JSON.stringify(data.errors));
                    } else {
                        alert('Вопрос успешно сохранен');
                        if (!cardId) {
                            card.setAttribute('data-id', data.faq.id); // Присвоить новый ID карточке
                        }
                    }
                })
                .catch(error => console.error('Ошибка:', error));
        }

        // Функция удаления карточки FAQ
        function deleteFaqCard(card) {
            const cardId = card.getAttribute('data-id');
            if (!cardId) {
                card.remove();
                return;
            }

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`/admin/faqs/delete/${cardId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Вопрос успешно удален');
                        card.remove();
                    } else {
                        alert('Ошибка при удалении вопроса');
                    }
                })
                .catch(error => console.error('Ошибка:', error));
        }

        // Добавление новой карточки FAQ
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

        // Использование делегирования событий для кнопок
        document.querySelector('.faq-cards-container').addEventListener('click', handleCardAction);


        function handleCardAction(e) {
            if (e.target.classList.contains('save-faq-button')) {
                const card = e.target.closest('.faq-card');
                saveFaqCard(card);
            } else if (e.target.classList.contains('delete-faq-button')) {
                const card = e.target.closest('.faq-card');
                deleteFaqCard(card);
            }
        }

        // Обработчик для кнопки "Добавить вопрос"
        // document.querySelector('.add-faq-button').removeEventListener('click', addNewFaqCard);
        document.querySelector('.add-faq-button').addEventListener('click', addNewFaqCard);
    });
</script>

{{-- Отзывы --}}

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Сохранение новой или обновленной карточки отзыва
        // Сохранение новой или обновленной карточки отзыва
        function saveReviewCard(card) {
            let formData = new FormData(card); // Используем FormData для формы

            const cardId = card.getAttribute('data-id');
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const url = cardId ? `/admin/reviews/${cardId}` : '/admin/reviews';
            const method = cardId ? 'POST' : 'POST'; // Используем POST, т.к. FormData не поддерживает PUT

            formData.append('_method', method === 'POST' && cardId ? 'PUT' :
                'POST'); // добавляем метод для симуляции PUT

            fetch(url, {
                method: 'POST', // Всегда используем POST
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
                        alert('Отзыв успешно сохранен');
                        if (!cardId) {
                            card.setAttribute('data-id', data.faq.id); // Присвоить новый ID карточке
                        }
                    }
                })
                .catch(error => console.error('Ошибка:', error));
        }


        // Добавление новой карточки отзыва
        function addNewReviewCard() {
            const newReviewCard = document.createElement('form');
            newReviewCard.classList.add('review-card');


            // Поля для ввода заголовка, текста и аватарки
            newReviewCard.innerHTML = `
                <div class="form-group">
                    <label for="title">Имя клиента</label>
                    <input name="title" type="text" class="form-control">
                </div>
                <div class="form-group">
                    <label for="text">Отзыв</label>
                    <textarea name="text" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label for="avatar">Аватар</label>
                    <input name="avatar" type="file" class="form-control">
                </div>
                <button class="btn btn-primary save-review-button" type="button">Сохранить</button>
                <a class="btn btn-outline-secondary delete-review-button">Удалить</a>
            `;

            document.querySelector('.review-cards-container').appendChild(newReviewCard);
        }

        function deleteReviewCard(card) {
            const cardId = card.getAttribute('data-id');
            if (!cardId) {
                card.remove();
                return;
            }

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`/admin/reviews/${cardId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Вопрос успешно удален');
                        card.remove();
                    } else {
                        alert('Ошибка при удалении вопроса');
                    }
                })
                .catch(error => console.error('Ошибка:', error));
        }


        // Использование делегирования событий для кнопок
        document.querySelector('.review-cards-container').addEventListener('click', function (e) {
            if (e.target.classList.contains('save-review-button')) {
                const card = e.target.closest('.review-card');
                saveReviewCard(card);
            } else if (e.target.classList.contains('delete-review-button')) {
                const card = e.target.closest('.review-card');
                deleteReviewCard(card)
            }
        });

        // Обработчик для кнопки "Добавить отзыв"
        document.querySelector('.add-review-button').addEventListener('click', addNewReviewCard);
    });
</script>

{{-- Сео текст --}}
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const toolbarOptions = [
            ['bold', 'italic', 'underline', 'strike'], // toggled buttons
            ['blockquote', 'code-block'],
            ['link', 'image', 'video', 'formula'],

            [{
                'header': 1
            }, {
                'header': 2
            }], // custom button values
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
            }], // superscript/subscript
            [{
                'indent': '-1'
            }, {
                'indent': '+1'
            }], // outdent/indent
            [{
                'direction': 'rtl'
            }], // text direction

            [{
                'size': ['small', false, 'large', 'huge']
            }], // custom dropdown
            [{
                'header': [1, 2, 3, 4, 5, 6, false]
            }],

            [{
                'color': []
            }, {
                'background': []
            }], // dropdown with defaults from theme
            [{
                'font': []
            }],
            [{
                'align': []
            }],

            ['clean'] // remove formatting button
        ];
        var quill = new Quill('#seoEditor', {
            modules: {
                toolbar: toolbarOptions
            },
            theme: 'snow',

        });

        document.getElementById('saveSeoButton').addEventListener('click', function () {
            const content = quill.root.innerHTML;

            if (!content) {
                alert('Контент пустой, введите текст.');
                return;
            }

            fetch('{{ route('home.update.texteditor') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                        .getAttribute('content')
                },
                body: JSON.stringify({
                    content: content
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




<script>
    document.getElementById('saveCategoryBtn1').addEventListener('click', function () {
        let form = document.getElementById('categoryEditForm1');
        let formData = new FormData(form);

        fetch('{{ route('admin.homepage.savemeta') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.success);
                    console.log(formData);


                } else {
                    alert('Произошла ошибка при обновлении Мета.');
                }
            })
            .catch(error => console.error('Ошибка:', error));
    });
</script>



<x-admin.footer></x-admin.footer>