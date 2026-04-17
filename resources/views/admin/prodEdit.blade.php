<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Редактировать товар {{ $product->h1 }}</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Первый экран</h5>
            <div class="card-body">
                <form id="prodFirstForm" enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <input type="hidden" id="slug" name="slug" value="{{ $product->slug }}">
                    <div class="form-group">
                        <label for="title">Заголовок(meta)</label>
                        <input id="title" name="title" type="text" class="form-control"
                            value="{{ $product->title }}">
                    </div>
                    <div class="form-group">
                        <label for="description">Описание(meta)</label>
                        <input id="description" name="description" type="text" class="form-control"
                            value="{{ $product->description }}">
                    </div>
                    <div class="form-group">
                        <label for="slug">slug</label>
                        <input id="slug" name="slug" type="text" class="form-control"
                            value="{{ $product->slug }}">
                    </div>
                    <div class="form-group">
                        <label for="h1">Заголовок h1</label>
                        <input id="h1" name="h1" type="text" class="form-control"
                            value="{{ $product->h1 }}">
                    </div>
                    <div class="form-group">
                        <label for="coef">Коэффициент</label>
                        <input id="coef" name="coef" type="text" class="form-control"
                            value="{{ $product->coef }}">
                    </div>
                    <div class="form-group">
                        <label for="first_screenn_description">Текст</label>
                        <textarea class="form-control" name="first_screenn_description" id="first_screenn_description">{{ $product->first_screenn_description }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="subcategory">Выберите подкатегорию</label>
                        <select id="subcategory" name="subcategory" class="form-control">
                            <option value="">-- Выберите подкатегорию --</option>
                            @foreach ($subcategories as $subcategory)
                                <option value="{{ $subcategory->id }}"
                                    {{ $subcategory->id == $product->subcategory_id ? 'selected' : '' }}>
                                    {{ $subcategory->titleh1 }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button id="prodFirstFormBtn" class="btn btn-primary" type="button">Сохранить</button>
                </form>

            </div>

        </div>
    </div>
</div>

{{-- Фото --}}
@php
    $resolveProductImageUrl = function (?string $path): ?string {
        if (!$path) {
            return null;
        }
        $clean = ltrim($path, '/');
        if (\Illuminate\Support\Str::startsWith($clean, ['http://', 'https://'])) {
            return $clean;
        }
        if (\Illuminate\Support\Str::startsWith($clean, 'storage/')) {
            return asset($clean);
        }
        return \Illuminate\Support\Facades\Storage::url($clean);
    };
@endphp

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Product Photos</h5>
            <div class="card-body">
                <form id="productPhotosForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="product_main_image">Main photo</label>
                        <input id="product_main_image" name="image_path" type="file" class="form-control" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="product_fabric_photo">Material photo</label>
                        <input id="product_fabric_photo" name="fabric_photo" type="file" class="form-control" accept="image/*">
                    </div>
                    <button id="saveProductPhotosBtn" class="btn btn-primary" type="button"
                        data-url="{{ route('product.update.photos', ['product_slug' => $product->slug]) }}">
                        Save photos
                    </button>
                </form>

                <div class="mt-4">
                    <p class="mb-2"><strong>Main</strong></p>
                    <img id="mainPhotoPreview" src="{{ $resolveProductImageUrl($product->image_path) }}" alt=""
                        style="max-width: 220px; margin-right: 12px;">
                    <img id="mainThumbPreview" src="{{ $resolveProductImageUrl($product->image_thumb_path) }}" alt=""
                        style="max-width: 120px;">
                </div>
                <div class="mt-3">
                    <p class="mb-2"><strong>Material</strong></p>
                    <img id="fabricPhotoPreview" src="{{ $resolveProductImageUrl($product->fabric_photo) }}" alt=""
                        style="max-width: 220px; margin-right: 12px;">
                    <img id="fabricThumbPreview" src="{{ $resolveProductImageUrl($product->fabric_thumb_path) }}" alt=""
                        style="max-width: 120px;">
                </div>
            </div>
        </div>
    </div>
</div>

{{-- <div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Параметры товара</h5>
            <div class="card-body">
                <form id="prodParametrs" enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <input type="hidden" id="product-id" value="{{ $product->id }}">
                    <div class="form-group">
                        <select id="model" name="model" class="form-control">
                            <option value="">-- Выберите модель --</option>
                            @foreach ($models as $model)
                                <option value="{{ $model->id }}">{{ $model->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="color">Цвет</label>
                        <input class="form-control" type="color" name="color" id="color">
                    </div>
                    <div class="form-group">
                        <label for="opacity_img">Прозрачность %</label>
                        <input class="form-control" type="number" name="opacity_img" id="opacity_img" max="100"
                            min="0" value="100">
                    </div>
               
                    <div id="canvas-container" style="border: 1px solid black;"></div>

                    <button type="button" id="save-image" class="btn btn-primary">Сохранить</button>
                </form>
                <img class="prodPrev" src="{{ Storage::url($product->image_path) }}" alt="">
            </div>
        </div>
    </div>
</div> --}}

<style>
    .prodPrev {
        width: 200px;
        margin-top: 20px;
    }

    #prodParametrs .btn {
        margin-top: 20px;
    }
</style>

{{-- Для калькулятора --}}

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Опции для калькулятора</h5>
            <div class="card-body">
                <form id="">
                    @csrf
                    @method('POST')
                    <input type="hidden" id="product-id" value="{{ $product->id }}">

                    <div class="form-group">
                        <label for="xlscolor">Цвет</label>
                        <input class="form-control" type="text" name="xlscolor" id="xlscolor"
                            value="{{ $product->xlscolor }}">
                    </div>



                    <div class="form-group">
                        <label for="cloth">Материал (Категория)</label>

                        <select id="cloth" name="cloth" class="form-control">
                            <option value="0 категория" {{ $product->cloth === '0 категория' ? 'selected' : '' }}>0
                                категория</option>
                            <option value="1 категория" {{ $product->cloth === '1 категория' ? 'selected' : '' }}>1
                                категория</option>
                            <option value="2 категория" {{ $product->cloth === '2 категория' ? 'selected' : '' }}>2
                                категория</option>
                            <option value="3 категория" {{ $product->cloth === '3 категория' ? 'selected' : '' }}>3
                                категория</option>
                            <option value="4 категория" {{ $product->cloth === '4 категория' ? 'selected' : '' }}>4
                                категория</option>
                            <option value="5 категория" {{ $product->cloth === '5 категория' ? 'selected' : '' }}>5
                                категория</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="material">Материал</label>
                        <select id="material" name="material" class="form-control">
                            <!-- Опции будут добавлены через JavaScript -->
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="control">Управление</label>
                        <input type="checkbox" id="control" name="control"
                            {{ $product->control ? 'checked' : '' }}>
                    </div>



                    <button type="button" id="saveCalcParametrs" class="btn btn-primary">Сохранить</button>
                </form>

            </div>
        </div>
    </div>
</div>

{{-- Параметры рольставен --}}
<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <h5 class="card-header">Параметры рольставен</h5>
            <div class="card-body">
                <form id="rolshveniParamsForm">
                    @csrf
                    @method('POST')
                    <input type="hidden" id="product-id" value="{{ $product->id }}">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="installation_type">Тип монтажа</label>
                                <select id="installation_type" name="installation_type" class="form-control">
                                    <option value="">-- Выберите тип монтажа --</option>
                                    <option value="overhead" {{ $product->installation_type == 'overhead' ? 'selected' : '' }}>Накладной монтаж</option>
                                    <option value="built-in" {{ $product->installation_type == 'built-in' ? 'selected' : '' }}>Встроенный монтаж</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="control_type">Тип управления</label>
                                <select id="control_type" name="control_type" class="form-control">
                                    <option value="">-- Выберите тип управления --</option>
                                    <option value="strap" {{ $product->control_type == 'strap' ? 'selected' : '' }}>Ленточный или шнуровой привод</option>
                                    <option value="cardan" {{ $product->control_type == 'cardan' ? 'selected' : '' }}>Воротковый привод (кардан)</option>
                                    <option value="pim" {{ $product->control_type == 'pim' ? 'selected' : '' }}>Пружинно-инерционный механизм (ПИМ)</option>
                                    <option value="electric" {{ $product->control_type == 'electric' ? 'selected' : '' }}>Автоматическое управление (электропривод)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="lock_device">Блокирующее устройство</label>
                                <select id="lock_device" name="lock_device" class="form-control">
                                    <option value="">-- Выберите блокирующее устройство --</option>
                                    <option value="rigel" {{ $product->lock_device == 'rigel' ? 'selected' : '' }}>Ригельный замок (с ключом)</option>
                                    <option value="shchyolka" {{ $product->lock_device == 'shchyolka' ? 'selected' : '' }}>Ручной ригель (щеколда)</option>
                                    <option value="upper" {{ $product->lock_device == 'upper' ? 'selected' : '' }}>Верхний ригель (верхние замки)</option>
                                    <option value="none" {{ $product->lock_device == 'none' ? 'selected' : '' }}>Без блокировки</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Цены монтажа</h6>
                            <div class="form-group">
                                <label for="overhead_price">Цена за накладной монтаж</label>
                                <input type="number" id="overhead_price" name="overhead_price" class="form-control" 
                                       value="{{ $product->overhead_price ?? 0 }}" step="0.01" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="builtin_price">Цена за встроенный монтаж</label>
                                <input type="number" id="builtin_price" name="builtin_price" class="form-control" 
                                       value="{{ $product->builtin_price ?? 0 }}" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Цены управления</h6>
                            <div class="form-group">
                                <label for="strap_price">Цена за ленточный привод</label>
                                <input type="number" id="strap_price" name="strap_price" class="form-control" 
                                       value="{{ $product->strap_price ?? 0 }}" step="0.01" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="cardan_price">Цена за воротковый привод</label>
                                <input type="number" id="cardan_price" name="cardan_price" class="form-control" 
                                       value="{{ $product->cardan_price ?? 0 }}" step="0.01" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="pim_price">Цена за ПИМ</label>
                                <input type="number" id="pim_price" name="pim_price" class="form-control" 
                                       value="{{ $product->pim_price ?? 0 }}" step="0.01" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="electric_price">Цена за электропривод</label>
                                <input type="number" id="electric_price" name="electric_price" class="form-control" 
                                       value="{{ $product->electric_price ?? 0 }}" step="0.01" min="0">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Цены блокирующих устройств</h6>
                            <div class="form-group">
                                <label for="rigel_price">Цена за ригельный замок</label>
                                <input type="number" id="rigel_price" name="rigel_price" class="form-control" 
                                       value="{{ $product->rigel_price ?? 0 }}" step="0.01" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="shchyolka_price">Цена за щеколду</label>
                                <input type="number" id="shchyolka_price" name="shchyolka_price" class="form-control" 
                                       value="{{ $product->shchyolka_price ?? 0 }}" step="0.01" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="upper_price">Цена за верхние замки</label>
                                <input type="number" id="upper_price" name="upper_price" class="form-control" 
                                       value="{{ $product->upper_price ?? 0 }}" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" id="ral_paint" name="ral_paint" class="form-check-input" 
                                           {{ $product->ral_paint ? 'checked' : '' }}>
                                    <label for="ral_paint" class="form-check-label">Покраска по RAL</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="ral_price">Цена за покраску по RAL</label>
                                <input type="number" id="ral_price" name="ral_price" class="form-control" 
                                       value="{{ $product->ral_price ?? 0 }}" step="0.01" min="0">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" id="photo_print" name="photo_print" class="form-check-input" 
                                           {{ $product->photo_print ? 'checked' : '' }}>
                                    <label for="photo_print" class="form-check-label">Нанесение фотопечати</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="photo_price">Цена за фотопечать</label>
                                <input type="number" id="photo_price" name="photo_price" class="form-control" 
                                       value="{{ $product->photo_price ?? 0 }}" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" id="saveRolshveniParams" class="btn btn-primary">Сохранить параметры рольставен</button>
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
                            {{ $product->show_in_menu ? 'checked' : '' }}>
                    </div>

                    <div class="form-group">
                        <label for="show_in_catalog">Показывать в каталоге</label>
                        <input type="checkbox" id="show_in_catalog" name="show_in_catalog"
                            {{ $product->show_in_catalog ? 'checked' : '' }}>
                    </div>

                    <div class="form-group">
                        <label for="related_product_ids">Сопутствующие товары</label>
                        <select id="related_product_ids" name="related_product_ids[]" multiple class="form-control">
                            @foreach ($products as $productItem)
                                <option value="{{ $productItem->id }}"
                                    @if (is_array($product->related_product_ids) && in_array($productItem->id, $product->related_product_ids)) selected @endif>
                                    {{ $productItem->h1 }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="alternative_product_ids">Альтернативные товары</label>
                        <select id="alternative_product_ids" name="alternative_product_ids[]" multiple
                            class="form-control">
                            @foreach ($products as $productItem)
                                <option value="{{ $productItem->id }}"
                                    @if (is_array($product->alternative_product_ids) && in_array($productItem->id, $product->alternative_product_ids)) selected @endif>
                                    {{ $productItem->h1 }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div class="form-group">
                        <label for="discount">Скидка</label>
                        <input class="form-control" type="number" name="discount" id="discount" max="100"
                            min="0" value="{{ $product->discount }}">
                    </div>

                    <div class="form-group">
                        <label for="home_actions">Акции на главной</label>
                        <input type="checkbox" id="home_actions" name="home_actions"
                            {{ $product->home_actions ? 'checked' : '' }}>
                    </div>
                    <div class="form-group">
                        <label for="home_populars">Популярные на главной</label>
                        <input type="checkbox" id="home_populars" name="home_populars"
                            {{ $product->home_populars ? 'checked' : '' }}>
                    </div>
                    <button class="btn btn-primary" type="button" id="saveProdWidgets">Сохранить</button>
                </form>
            </div>

        </div>
    </div>
</div>



{{-- Табы --}}
<input type="hidden" name="product-slug" value="{{ $product->slug }}">

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Табы</h5>
            <div class="card-body">

                <div class="faq-cards-container">
                    @foreach ($tabs as $tab)
                        <form class="faq-card" data-id="{{ $tab->id }}">
                            <input type="hidden" name="product-slug" value="{{ $product->slug }}">

                            <div class="form-group">
                                <label for="title">Заголовок</label>
                                <input name="title" type="text" class="form-control"
                                    value="{{ $tab->title }}">
                            </div>
                            <div class="form-group">
                                <label for="tab">Ответ</label>
                                <textarea name="tab" class="form-control">{{ $tab->tab }}</textarea>
                            </div>
                            <button class="btn btn-primary save-tab-button" type="button">Сохранить</button>
                            <a class="btn btn-outline-secondary delete-tab-button">Удалить</a>
                        </form>
                    @endforeach
                </div>
                <button class="btn btn-primary add-faq-button">Добавить таб</button>
            </div>

        </div>
    </div>
</div>



{{-- Секция Сео --}}

{{-- <div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Секция СЕО текст</h5>
            <div class="card-body">
                <form>
                    <div class="form-group">
                        <label for="seoEditor">Редактировать</label>
                        <div id="seoEditor">
                            {!! $product->seo !!}
                        </div>
                    </div>
                    <button class="btn btn-primary" type="button" id="saveSeoButton">Сохранить</button>
                </form>
            </div>

        </div>
    </div>
</div> --}}


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
                                {!! $product->seo !!}
                            </div>
                        </div>

                    </div>
                    <button class="btn btn-primary" type="button" id="saveSeoButton">Сохранить</button>
                </form>
            </div>

        </div>
    </div>
</div>

{{-- заголовок и тд --}}

<script>
    document.getElementById('prodFirstFormBtn').addEventListener('click', function() {
        let form = document.getElementById('prodFirstForm');
        let formData = new FormData(form);
        let productSlug = form.querySelector('input[name="slug"]').value;

        let updateUrl = `{{ url('/admin/product/${productSlug}/update') }}`;
        console.log('Updating product with slug:', updateUrl); // Debugging line

        fetch(updateUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    alert(data.message); // Показать сообщение об успешном обновлении
                } else {
                    alert('Произошла ошибка при обновлении продукта.');
                }
            })
            .catch(error => console.error('Ошибка:', error));
    });
</script>





{{-- Меню --}}
<script>
    document.getElementById('saveProdWidgets').addEventListener('click', function() {
        const showInMenu = document.getElementById('show_in_menu').checked ? 1 : 0;
        const showInCatalog = document.getElementById('show_in_catalog').checked ? 1 : 0;

        // Получаем выбранные значения из мультиселектов
        const relatedProds = Array.from(document.getElementById('related_product_ids').selectedOptions).map(
            option => option.value);
        const altProds = Array.from(document.getElementById('alternative_product_ids').selectedOptions).map(
            option => option.value);

        const discount = document.getElementById('discount').value;
        const home_actions = document.getElementById('home_actions').value;
        const home_populars = document.getElementById('home_populars').value;

        const productSlug = document.querySelector('input[name="slug"]').value;

        fetch(`/admin/product/${productSlug}/update-visibility`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                        'content')
                },
                body: JSON.stringify({
                    show_in_menu: showInMenu,
                    show_in_catalog: showInCatalog,
                    related_product_ids: relatedProds, // Теперь это массив
                    alternative_product_ids: altProds, // Теперь это массив
                    discount: discount,
                    home_actions: home_actions,
                    home_populars: home_populars,
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                alert('Товар обновлен');
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Произошла ошибка при обновлении Товара');
            });
    });
</script>


{{-- Параметры калькулятора --}}
<script>
    document.getElementById('saveCalcParametrs').addEventListener('click', function() {
        // const showInMenu = document.getElementById('show_in_menu').checked ? 1 : 0;
        // const showInCatalog = document.getElementById('show_in_catalog').checked ? 1 : 0;



        const xlscolor = document.getElementById('xlscolor').value;
        const cloth = document.getElementById('cloth').value;
        const control = document.getElementById('control').value;
        const productSlug = document.querySelector('input[name="slug"]').value;
        const material = document.getElementById('material').value;


        fetch(`/admin/product/${productSlug}/update-calc-parametrs`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                        'content')
                },
                body: JSON.stringify({
                    xlscolor: xlscolor,
                    cloth: cloth,
                    control: control,
                    material: material,
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                alert('Товар обновлен');
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Произошла ошибка при обновлении Товара');
            });
    });
</script>


{{-- Фото товара --}}

<script>
    document.getElementById('saveProductPhotosBtn')?.addEventListener('click', async function() {
        const form = document.getElementById('productPhotosForm');
        const button = this;
        const formData = new FormData(form);
        const hasMain = formData.get('image_path') && formData.get('image_path').size > 0;
        const hasFabric = formData.get('fabric_photo') && formData.get('fabric_photo').size > 0;

        if (!hasMain && !hasFabric) {
            alert('Select at least one file');
            return;
        }

        button.disabled = true;

        try {
            const response = await fetch(button.dataset.url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Failed to save photos');
            }

            if (data.image_path) {
                document.getElementById('mainPhotoPreview').src = data.image_path;
            }
            if (data.image_thumb_path) {
                document.getElementById('mainThumbPreview').src = data.image_thumb_path;
            }
            if (data.fabric_photo) {
                document.getElementById('fabricPhotoPreview').src = data.fabric_photo;
            }
            if (data.fabric_thumb_path) {
                document.getElementById('fabricThumbPreview').src = data.fabric_thumb_path;
            }

            form.reset();
            alert(data.message || 'Saved');
        } catch (error) {
            console.error(error);
            alert(error.message || 'Failed to save photos');
        } finally {
            button.disabled = false;
        }
    });
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"
    integrity="sha512-CeIsOAsgJnmevfCi2C7Zsyy6bQKi43utIjdA87Q0ZY84oDqnI0uwfM9+bKiIkI75lUeI00WG/+uJzOmuHlesMA=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
    // Инициализация холста fabric.js
    let canvas; // Объявляем переменную без инициализации

    // Загрузка модели при выборе
    document.getElementById('model').addEventListener('change', function() {
        const modelId = this.value;
        if (modelId) {
            fetch(`/get-model-image/${modelId}`)
                .then(response => response.json())
                .then(data => {
                    // Очистка холста перед загрузкой новой модели
                    if (canvas) {
                        canvas.clear(); // Очищаем старые фигуры
                    }

                    loadImageToCanvas(data.image, data.mask_coordinates); // Передаем mask_coordinates
                });
        }
    });

    function loadImageToCanvas(imageUrl, maskCoordinates) {
        const imgElement = new Image();
        imgElement.src = imageUrl;
        const prevImg = document.querySelector('.prodPrev')
        prevImg.remove()
        imgElement.onload = function() {
            // Создаем новый элемент канваса
            const canvasElement = document.createElement('canvas');
            canvasElement.width = imgElement.width; // Ширина изображения
            canvasElement.height = imgElement.height; // Высота изображения

            // Добавляем канвас в контейнер
            const container = document.getElementById('canvas-container');
            container.innerHTML = ''; // Очищаем контейнер перед добавлением нового канваса
            container.appendChild(canvasElement); // Добавляем новый канвас

            // Инициализируем fabric.js на новом канвасе
            canvas = new fabric.Canvas(canvasElement); // Здесь инициализируем глобальный экземпляр canvas

            // Создаем изображение fabric.js и устанавливаем его на канвас
            fabric.Image.fromURL(imageUrl, function(img) {
                canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas));

                // Если есть маска, рисуем фигуры
                if (maskCoordinates) {
                    const parsedCoordinates = JSON.parse(maskCoordinates);
                    drawShapeFromCoordinates(parsedCoordinates);
                }
            });
        };
    }

    function drawShapeFromCoordinates(coordinatesArray) {
        coordinatesArray.forEach((coordinates) => {
            // Генерация строки для Path
            const pathData = coordinates.map(command => {
                return `${command[0]} ${command.slice(1).join(' ')}`;
            }).join(' ');

            const path = new fabric.Path(pathData);
            path.set({
                fill: document.getElementById('color').value, // Используем выбранный цвет
                opacity: 1, // Прозрачность полностью непрозрачная
                stroke: null, // Убираем обводку
                selectable: true
            });

            canvas.add(path); // Добавление фигуры на холст
        });

        canvas.renderAll(); // Обновляем холст
    }




    // Обновление всех фигур при изменении цвета или прозрачности
    function updateShapes() {
        const color = document.getElementById('color').value;
        const opacity = parseInt(document.getElementById('opacity_img').value) / 100;

        canvas.getObjects().forEach(function(obj) {
            if (obj instanceof fabric.Path) { // Проверяем, что объект — это фигура
                obj.set('fill', color);
                obj.set('opacity', opacity);
            }
        });

        canvas.renderAll(); // Обновляем холст
    }

    // Изменение цвета фигуры при выборе
    document.getElementById('color').addEventListener('input', updateShapes);

    // Изменение прозрачности фигуры
    document.getElementById('opacity_img').addEventListener('input', updateShapes);

    // Сохранение итогового изображения (фото + фигура)
    document.getElementById('save-image').addEventListener('click', function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        canvas.getElement().toBlob(function(blob) {
            const formData = new FormData();
            formData.append('image', blob);
            formData.append('model_id', document.querySelector('#model').value);
            formData.append('color', document.querySelector('#color').value);

            fetch(`/save-product-image/${document.querySelector('#product-id').value}`, { // Используем ID продукта из input
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => alert(data.message))
                .catch(error => console.error('Error saving image:', error));
        }, 'image/png');
    });
</script>


{{-- Табы --}}

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Function to save or update Tab
        function saveTabCard(card) {
            const title = card.querySelector('[name="title"]').value;
            const tabContent = card.querySelector('[name="tab"]').value;
            const cardId = card.getAttribute('data-id');
            const productSlug = document.querySelector('input[name="product-slug"]').value;

            const url = cardId ? `/admin/tabs/${cardId}` : `/admin/tabs/${productSlug}`;
            const method = cardId ? 'PUT' : 'POST';

            fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        title,
                        tab: tabContent,
                        product_slug: productSlug // Передаем slug товара для создания нового таба
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.errors) {
                        alert('Ошибки: ' + JSON.stringify(data.errors));
                    } else {
                        alert(data.message);
                        if (!cardId) {
                            card.setAttribute('data-id', data.tab.id); // Присваиваем новый ID таба
                        }
                    }
                })
                .catch(error => console.error('Ошибка:', error));
        }

        // Function to delete Tab
        function deleteTabCard(card) {
            const cardId = card.getAttribute('data-id');
            if (!cardId) {
                card.remove();
                return;
            }

            fetch(`/admin/tabs/${cardId}`, {
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

        function addNewTabCard() {
            const newTabCard = document.createElement('form');
            newTabCard.classList.add('faq-card'); // Можно переименовать класс, если необходимо

            newTabCard.innerHTML = `
        <input type="hidden" name="product-slug" value="${document.querySelector('input[name="product-slug"]').value}">
        <div class="form-group">
            <label for="title">Заголовок</label>
            <input name="title" type="text" class="form-control">
        </div>
        <div class="form-group">
            <label for="tab">Таб</label>
            <textarea name="tab" class="form-control"></textarea>
        </div>
        <button class="btn btn-primary save-tab-button" type="button">Сохранить</button>
        <a class="btn btn-outline-secondary delete-tab-button">Удалить</a>
    `;

            document.querySelector('.faq-cards-container').appendChild(newTabCard);
        }

        // Event delegation for save and delete buttons
        document.querySelector('.faq-cards-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('save-tab-button')) {
                const card = e.target.closest(
                    '.faq-card'); // Можете поменять на другой класс, если требуется
                saveTabCard(card);
            } else if (e.target.classList.contains('delete-tab-button')) {
                const card = e.target.closest('.faq-card');
                deleteTabCard(card);
            }
        });

        // Add new Tab event listener
        document.querySelector('.add-faq-button').addEventListener('click', addNewTabCard);
    });
</script>



<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
{{-- Сео текст --}}
{{-- <script>
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
            fetch('{{ route('prodseo.update', ['product_slug' => $product->slug]) }}', {
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
</script> --}}
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

        let quill = new Quill('#seoEditor', {
            modules: {
                toolbar: toolbarOptions
            },
            theme: 'snow'
        });

        let toggleButton = document.getElementById("toggle-editor");
        let htmlEditor = document.createElement("textarea");
        htmlEditor.id = "html-editor";
        htmlEditor.style.display = "none";
        htmlEditor.style.width = "100%";
        htmlEditor.style.height = "300px";

        // Вставим textarea сразу после редактора
        document.getElementById("editor-container").appendChild(htmlEditor);

        let quillContainer = document.querySelector(".ql-container");
        let isHtmlMode = false;

        toggleButton.addEventListener("click", function() {
            if (!isHtmlMode) {
                // Переключение в HTML
                htmlEditor.value = quill.root.innerHTML;
                htmlEditor.style.display = "block";
                quillContainer.style.display = "none";
                toggleButton.textContent = "Редактировать в Quill";
            } else {
                // Переключение обратно в Quill
                quill.root.innerHTML = htmlEditor.value;
                htmlEditor.style.display = "none";
                quillContainer.style.display = "block";
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

            fetch(`{{ route('prodseo.update', ['product_slug' => $product->slug]) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
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

<script>
    // Данные материалов по категориям
    const materialsByCategory = {
        "0 категория": ["Шелк"],
        "1 категория": ["Дарина", "Мадагаскар", "Респект блэкаут", "Тэффи", "Сахара", "Дриада", "Ниагара", "Оливия",
            "Рябина", "Арабика", "Либерика", "Подсолнух", "Оливка", "Эмилия"
        ],
        "2 категория": ["Аллегро перл", "Монако", "Дарина блэкаут", "Подсолнух блэкаут", "Оливка блэкаут",
            "Кейптаун ФР", "Респект ФР блэкаут", "Эмилия блэкаут", "Нуар", "Севилья", "Металлик", "Корсо",
            "Анже", "Дарина металлик", "Дарина перл", "Эклипс", "Кастелло"
        ],
        "3 категория": ["Скрин 102", "Скрин К 304", "Скрин 311", "Скрин 3% 315", "Скрин 3% 317", "Севилья блэкаут",
            "Лусто", "Корсо перл", "Корсо блэкаут", "Анже блэкаут", "Калипсо", "Лэйси", "Ажур", "Форио"
        ],
        "4 категория": ["Скрин алю 311", "Скрин Алю 312", "Скрин Алю 313", "Амальфи", "Баски димаут", "Шейд",
            "Ницца", "Палау"
        ],
        "5 категория": ["Сиена", "Атико", "Лэйси блэкаут", "Ницца блэкаут", "Шерни"]
    };

    // Селекты
    const clothSelect = document.getElementById('cloth');
    const materialSelect = document.getElementById('material');

    // Функция для обновления материалов
    function updateMaterials(selectedCategory) {
        // Очистить текущие опции
        materialSelect.innerHTML = "";

        // Добавить новые опции для выбранной категории
        const materials = materialsByCategory[selectedCategory] || [];
        materials.forEach(material => {
            const option = document.createElement('option');
            option.value = material;
            option.textContent = material;
            if (material === "{{ $product->material }}") { // Предварительно выбранный материал
                option.selected = true;
            }
            materialSelect.appendChild(option);
        });
    }

    // Обновить материалы при загрузке страницы
    updateMaterials(clothSelect.value);

    // Добавить обработчик событий для изменения категории
    clothSelect.addEventListener('change', () => {
        updateMaterials(clothSelect.value);
    });
</script>

{{-- Параметры рольставен --}}
<script>
    document.getElementById('saveRolshveniParams').addEventListener('click', function() {
        const productId = document.getElementById('product-id').value;
        
        const formData = {
            installation_type: document.getElementById('installation_type').value,
            control_type: document.getElementById('control_type').value,
            lock_device: document.getElementById('lock_device').value,
            // Цены монтажа
            overhead_price: parseFloat(document.getElementById('overhead_price').value) || 0,
            builtin_price: parseFloat(document.getElementById('builtin_price').value) || 0,
            // Цены управления
            strap_price: parseFloat(document.getElementById('strap_price').value) || 0,
            cardan_price: parseFloat(document.getElementById('cardan_price').value) || 0,
            pim_price: parseFloat(document.getElementById('pim_price').value) || 0,
            electric_price: parseFloat(document.getElementById('electric_price').value) || 0,
            // Цены блокирующих устройств
            rigel_price: parseFloat(document.getElementById('rigel_price').value) || 0,
            shchyolka_price: parseFloat(document.getElementById('shchyolka_price').value) || 0,
            upper_price: parseFloat(document.getElementById('upper_price').value) || 0,
            // Дополнительные опции
            ral_paint: document.getElementById('ral_paint').checked,
            photo_print: document.getElementById('photo_print').checked,
            ral_price: parseFloat(document.getElementById('ral_price').value) || 0,
            photo_price: parseFloat(document.getElementById('photo_price').value) || 0,
        };

        fetch(`/admin/product/${productId}/update-rolshveni-params`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(formData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                alert('Параметры рольставен сохранены');
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Произошла ошибка при сохранении параметров рольставен');
            });
    });
</script>

<x-admin.footer></x-admin.footer>
