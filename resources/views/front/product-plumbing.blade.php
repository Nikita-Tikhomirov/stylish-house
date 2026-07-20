{{-- @include('front.head') --}}
<x-front.head title="{{ $product->title }}" description="{{ $product->description }}"></x-front.head>
@vite('resources/css/prod.css')

<style>
    /* .tabs__item:first-child.active {
        display: grid;
        grid-template-columns: 1fr 1fr;

        width: 50%;
        margin: auto;
    } */
    .tabs.s-seo {
        padding-bottom: 0;
    }

    .tabs__item:first-child p {}

    /* .tabs__item:first-child p:nth-child(4n-2),
    .tabs__item:first-child p:nth-child(4n-3) {
        background-color: rgba(9, 136, 255, 0.1);
    }

    .tabs__item:first-child p:nth-child(odd) {
        font-weight: 600;
    }

    .tabs__item:first-child p:nth-child(even) {
        text-align: right;
    } */

    /* Стили для аккордеона параметров товара */
    .product-params-accordion .accordion-item {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin-bottom: 10px;
        overflow: hidden;
    }

    .product-params-accordion .accordion-header {
        background: #f8f9fa;
        padding: 15px 20px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.3s ease;
    }

    .product-params-accordion .accordion-header:hover {
        background: #e9ecef;
    }

    .product-params-accordion .accordion-header h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }

    .product-params-accordion .accordion-arrow {
        font-size: 14px;
        color: #0989ff;
        transition: transform 0.3s ease;
    }

    .product-params-accordion .accordion-item.active .accordion-arrow {
        transform: rotate(180deg);
    }

    .product-params-accordion .accordion-content {
        padding: 0;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease, padding 0.3s ease;
    }

    .product-params-accordion .accordion-item.active .accordion-content {
        padding: 20px;
        max-height: 500px;
    }

    .product-params-accordion .param-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .product-params-accordion .param-row:last-child {
        border-bottom: none;
    }

    .product-params-accordion .param-name {
        font-weight: 500;
        color: #666;
    }

    .product-params-accordion .param-value {
        font-weight: 600;
        color: #333;
    }

    .product-params-accordion .param-description {
        font-size: 13px;
        color: #666;
        margin: 8px 0 16px 0;
        padding: 8px 12px;
        background: #f8f9fa;
        border-radius: 4px;
        border-left: 3px solid #0989ff;
    }

    /* Стили для интерактивных опций */
    .product-params-accordion .option-label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        margin-bottom: 8px;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #fff;
    }

    .product-params-accordion .option-label:hover {
        border-color: #0989ff;
        background: #f8f9fa;
    }

    .product-params-accordion .option-label input[type="radio"],
    .product-params-accordion .option-label input[type="checkbox"] {
        margin-right: 12px;
        width: 18px;
        height: 18px;
        accent-color: #0989ff;
    }

    .product-params-accordion .option-name {
        flex: 1;
        font-weight: 500;
        color: #333;
    }

    .product-params-accordion .option-price {
        font-weight: 600;
        color: #0989ff;
        font-size: 14px;
    }

    .product-params-accordion .option-description {
        font-size: 13px;
        color: #666;
        margin: 0 0 16px 30px;
        padding: 8px 12px;
        background: #f8f9fa;
        border-radius: 4px;
        border-left: 3px solid #0989ff;
    }

    /* Основной контейнер */
    .charTableWrap {
        width: 100%;
        margin-top: 20px;

    }

    /* Таблица */
    .charTableWrap table {
        width: 100%;
        border-collapse: collapse;
        /* cellspacing="0" теперь через CSS */
    }

    /* Заголовки таблицы */
    .charTableWrap thead th {
        background-color: #0989ff;
        color: #fff;
        font-weight: 700;
        padding: 8px 10px;

    }

    /* Чередование строк в tbody */
    .charTableWrap tbody tr:nth-child(odd) {
        background-color: rgba(9, 136, 255, 0.1);
    }

    .charTableWrap tbody tr:nth-child(even) {
        background-color: #fff;
    }

    /* Ячейки */
    .charTableWrap tbody td {
        padding: 5px 10px;
        border-bottom: 1px solid #0989ff;
    }

    /* Жирный первый столбец */
    .charTableWrap tbody td:first-child {
        font-weight: 600;
    }

    /* Выравнивание второго столбца по правому краю */
    .charTableWrap tbody td:last-child {
        text-align: right;
    }

    .prodForm__imgWrap {
        position: relative;
        max-height: 400px;

    }

    .prodForm__imgWrap img:first-child {
        z-index: 1;
        height: 100%;
        width: 100%;
        object-fit: cover;
    }

    .prodForm__imgWrap img:nth-child(2) {
        position: absolute;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 2;
        left: 0;
        opacity: 0;
        transition: 0.2s;
        min-height: 400px;
    }

    .prodForm__imgWrap:hover img:nth-child(2) {
        opacity: 1;
    }

    .tabs__item .faq-block .faq-item {
        position: relative;
        z-index: 2;
        margin-bottom: 26px;
        transition: .2s;
        border: 1px solid #0989ff;
        border-radius: 5px;
        box-shadow: 5px 10px 6px #2c5b811a;
        background: #fff;
        padding: 8px 8px 8px 18px;

    }

    .tabs__item .faq-block .faq-item .faq-question {
        position: relative;
        z-index: 3;
        padding-right: 10px;
        list-style: none;
        cursor: pointer;
    }

    .tabs__item .faq-block .faq-item .faq-answer {
        padding-top: 10px;
    }

    .tabs__item .faq-block .faq-item .faq-arrow {
        display: none;
    }

    .tabs__item .faq-block .faq-item:before {

        content: "";
        display: block;
        width: 17px;
        height: 2px;
        position: absolute;
        top: 21px;
        right: 20px;
        background: #0989ff;
        z-index: -1;
    }

    .tabs__item .faq-block .faq-item:after {
        content: "";
        display: block;
        width: 2px;
        height: 17px;
        position: absolute;
        top: 14px;
        right: 27px;
        background: #0989ff;
        -webkit-transition: .2s;
        -o-transition: .2s;
        transition: .2s;
        z-index: -1;
    }

    .tabs__item .faq-block .faq-item .faq-question::marker {
        display: none;
    }
</style>

<body class="p-index">
    @php
        $renderOptionPrice = static function ($value, $fallback = 0): string {
            $amount = (int) ($value ?? $fallback);
            return $amount > 0 ? '<span class="option-price">+' . number_format($amount, 0, '', ' ') . '₽</span>' : '';
        };
    @endphp

    <x-front.header :categoriesInCatalogMenu="$categoriesInCatalogMenu" :categoriesInHeaderMenu="$categoriesInHeaderMenu" :cart="$cart" :headerInfo="$headerInfo"></x-front.header>

    <main class="layout">


        <section class="prodMain wrapper">
            <div class="breadcrumbs">
                <ul class="breadcrumbs__list">
                    <li class="breadcrumbs__item"><a class="breadcrumbs__link" href="/">Главная</a></li>
                    <li class="breadcrumbs__item"><svg class="breadcrumbs__arrow" width="5" height="9"
                            viewBox="0 0 5 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#000"
                                d="M3.4575 4.5L2.97878 5.0625L5.28601e-05 8.2125L0.744734 9L5.00005 4.5L0.744733 3.72007e-07L5.2211e-05 0.7875L2.97878 3.9375L3.4575 4.5Z">
                            </path>
                        </svg><a class="breadcrumbs__link"
                            href="/{{ $product->category->slug }}">{{ $product->category->titleh1 }}</a></li>
                    <li class="breadcrumbs__item"><svg class="breadcrumbs__arrow" width="5" height="9"
                            viewBox="0 0 5 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#000"
                                d="M3.4575 4.5L2.97878 5.0625L5.28601e-05 8.2125L0.744734 9L5.00005 4.5L0.744733 3.72007e-07L5.2211e-05 0.7875L2.97878 3.9375L3.4575 4.5Z">
                            </path>
                        </svg><a class="breadcrumbs__link"
                            href="/{{ $product->category->slug }}/{{ $product->subcategory->slug }}">{{ $product->subcategory->titleh1 }}</a>
                    </li>
                    <li class="breadcrumbs__item"><svg class="breadcrumbs__arrow" width="5" height="9"
                            viewBox="0 0 5 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#000"
                                d="M3.4575 4.5L2.97878 5.0625L5.28601e-05 8.2125L0.744734 9L5.00005 4.5L0.744733 3.72007e-07L5.2211e-05 0.7875L2.97878 3.9375L3.4575 4.5Z">
                            </path>
                        </svg><span class="breadcrumbs__active">{{ $product->h1 }}</span></li>
                </ul>
            </div>

            <h2 class="prodMain__title title"> <span>{{ $product->h1 }}</span><svg width="114" height="35"
                    viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                        stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                </svg></h2>
            <div class="prodForm">
                <div class="prodForm__galleryWrapOuter">
                    <div class="prodForm__galleryWrap">
                        <div class="prodForm__imgWrap">
                            @php
                                $productMainImage = $product->image_path ?: $product->image_thumb_path;
                                $productFabricImage = $product->fabric_photo ?: $product->fabric_thumb_path;
                            @endphp
                            @if ($productMainImage)
                                <img src="{{ asset($productMainImage) }}" alt="{{ $product->h1 }}" />
                            @endif
                            @if ($productFabricImage)
                                <img src="{{ asset($productFabricImage) }}"
                                    alt="{{ $product->h1 }}" />
                            @endif

                        </div>



                        <div class="prodForm__bar">
                            @foreach ($sameModelProducts as $sameProduct)
                                @php
                                    $sameMainImage = $sameProduct->image_path ?: $sameProduct->image_thumb_path;
                                    $sameFabricImage = $sameProduct->fabric_photo ?: $sameProduct->fabric_thumb_path;
                                @endphp
                                @if ($sameMainImage)
                                    <img src="{{ asset($sameMainImage) }}"
                                        alt="{{ $sameProduct->h1 }}" />
                                @elseif ($sameFabricImage)
                                    <img src="{{ asset($sameFabricImage) }}"
                                        alt="{{ $sameProduct->h1 }}" />
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="prodForm__calcFormWrap">
                    <div class="prodForm__formSubtitle">{{ $category->titleh1 }}</div>
                    <div class="prodForm__formTitle">{{ $product->h1 }}</div>
                    <div class="prodForm__description">
                        <p>{{ $product->first_screenn_description }}...</p><span class="more">Подробнее</span>
                    </div>

                    <input type="hidden" name="modelSelect" class="modelSelect" value="{{ $product->model_title }}">

                    <input type="hidden" name="cloth" class="cloth" value="{{ $product->cloth }}">

                    @if ($model)
                        <input type="hidden" name="model" class="model" value="{{ $model->title }}">
                    @endif

                    <input type="hidden" class="discount" value="{{ $product->discount }}">

                    <div class="prodForm__sizeWrap">
                        <label class="prodForm__label">
                            <p>Ширина, мм</p>
                            @if ($product->min_width)
                                <input class="prodForm__input width-input" type="number" name="width"
                                    value="{{ $product->min_width }}" required />
                            @else
                                <input class="prodForm__input width-input" type="number" name="width" value="500"
                                    required />
                            @endif

                        </label>
                        <label class="prodForm__label">
                            <p>Высота, мм</p>
                            @if ($product->min_height)
                                <input class="prodForm__input height-input" type="number" name="height"
                                    value="{{ $product->min_height }}" required />
                            @else
                                <input class="prodForm__input height-input" type="number" name="height"
                                    value="500" required />
                            @endif

                        </label>
                    </div>

                    <div class="calcWidhType">
                        <div class="cartForm__optionsList">
                            <div class="cartForm__listTitle">Тип монтажа</div>
                            <ul>
                                <li>
                                    <label>
                                        <input class="widthType" type="radio" name="widhType{{ $product->id }}"
                                            value="Ширина по ткани" checked>
                                        <span>Короб внутри (скрытый)</span>
                                    </label>
                                </li>
                                <li>
                                    <label>
                                        <input class="widthType" type="radio" name="widhType{{ $product->id }}"
                                            value="Ширина по габариту">
                                        <span>Короб снаружи</span>

                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="calcWidhType">
                        <div class="cartForm__optionsList">
                            <div class="cartForm__listTitle">Тип запорного устройства</div>
                            <ul>
                                <li>
                                    <label>
                                        <input class="widthType" type="radio" name="lock-type" value="sliders" data-price="0" checked>
                                        <span>Задвижки</span>
                                    </label>
                                </li>
                                <li>
                                    <label>
                                        <input class="widthType" type="radio" name="lock-type" value="lock" data-price="1600">
                                        <span>Замок +1600р</span>
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Аккордеон с параметрами товара -->
                    <div class="product-params-accordion" style="margin-top: 30px;">
                        <div class="cartForm__optionsList">
                            <div class="cartForm__listTitle">Характеристики товара</div>
                            
                            <div class="accordion-item">
                                <div class="accordion-header">
                                    <h4>Вид монтажа</h4>
                                    <span class="accordion-arrow">▼</span>
                                </div>
                                <div class="accordion-content">
                                    <div class="installation-options">
                                        <label class="option-label">
                                            <input type="radio" name="installation-type" value="overhead" data-price="{{ $product->overhead_price ?? 0 }}" {{ $product->installation_type == 'overhead' ? 'checked' : '' }}>
                                            <span class="option-name">Накладной монтаж</span>
                                            {!! $renderOptionPrice($product->overhead_price ?? 0) !!}
                                        </label>
                                        <div class="option-description">
                                            При выборе накладного монтажа необходимо к размерам проема добавьте 110мм по ширине и 250мм по высоте.
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="radio" name="installation-type" value="built-in" data-price="{{ $product->builtin_price ?? 0 }}" {{ $product->installation_type == 'built-in' ? 'checked' : '' }}>
                                            <span class="option-name">Встроенный монтаж</span>
                                            {!! $renderOptionPrice($product->builtin_price ?? 0) !!}
                                        </label>
                                        <div class="option-description">
                                            При выборе встроенного монтажа рекомендуется от размеров проема отнять по ширине и высоте 5 мм.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <div class="accordion-header">
                                    <h4>Тип управления рольставни</h4>
                                    <span class="accordion-arrow">▼</span>
                                </div>
                                <div class="accordion-content">
                                    <div class="control-options">
                                        <label class="option-label">
                                            <input type="radio" name="control-type" value="strap" data-price="{{ $product->strap_price ?? 0 }}" {{ $product->control_type == 'strap' ? 'checked' : '' }}>
                                            <span class="option-name">Ленточный или шнуровой инерционный привод</span>
                                            {!! $renderOptionPrice($product->strap_price ?? 0) !!}
                                        </label>
                                        <div class="option-description">
                                            Грузоподъемность до 15 кг. Ручное управление.
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="radio" name="control-type" value="cardan" data-price="{{ $product->cardan_price ?? 0 }}" {{ $product->control_type == 'cardan' ? 'checked' : '' }}>
                                            <span class="option-name">Воротковый привод (кардан)</span>
                                            {!! $renderOptionPrice($product->cardan_price ?? 0) !!}
                                        </label>
                                        <div class="option-description">
                                            Грузоподъемность до 35 кг. Ручное управление.
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="radio" name="control-type" value="pim" data-price="{{ $product->pim_price ?? 0 }}" {{ $product->control_type == 'pim' ? 'checked' : '' }}>
                                            <span class="option-name">Пружинно-инерционный механизм (ПИМ)</span>
                                            {!! $renderOptionPrice($product->pim_price ?? 0) !!}
                                        </label>
                                        <div class="option-description">
                                            Грузоподъемность от 6 до 80 кг. Ручное управление.
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="radio" name="control-type" value="electric" data-price="{{ $product->electric_price ?? 7000 }}" {{ $product->control_type == 'electric' ? 'checked' : '' }}>
                                            <span class="option-name">Автоматическое управление (электропривод)</span>
                                            {!! $renderOptionPrice($product->electric_price ?? 7000) !!}
                                        </label>
                                        <div class="option-description">
                                            Тип управление: выключатель настенный или мини пульт.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <div class="accordion-header">
                                    <h4>Блокирующие устройства</h4>
                                    <span class="accordion-arrow">▼</span>
                                </div>
                                <div class="accordion-content">
                                    <div class="lock-options">
                                        <label class="option-label">
                                            <input type="radio" name="lock-device" value="rigel" data-price="{{ $product->rigel_price ?? 0 }}" {{ $product->lock_device == 'rigel' ? 'checked' : '' }}>
                                            <span class="option-name">Ригельный замок (с ключом)</span>
                                            {!! $renderOptionPrice($product->rigel_price ?? 0) !!}
                                        </label>
                                        <div class="option-description">
                                            Используется при ручном управлении. Рольставни запираются на замок. Не рекомендуется использовать при электроприводе.
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="radio" name="lock-device" value="shchyolka" data-price="{{ $product->shchyolka_price ?? 0 }}" {{ $product->lock_device == 'shchyolka' ? 'checked' : '' }}>
                                            <span class="option-name">Ручной ригель (щеколда)</span>
                                            {!! $renderOptionPrice($product->shchyolka_price ?? 0) !!}
                                        </label>
                                        <div class="option-description">
                                            Используется при ручном управлении. Любой желающий сможет открыть роллету. Не рекомендуется использовать при электроприводе.
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="radio" name="lock-device" value="upper" data-price="{{ $product->upper_price ?? 0 }}" {{ $product->lock_device == 'upper' ? 'checked' : '' }}>
                                            <span class="option-name">Верхний ригель (верхние замки)</span>
                                            {!! $renderOptionPrice($product->upper_price ?? 0) !!}
                                        </label>
                                        <div class="option-description">
                                            Используется при управлении воротковый привод или автоматическом управлении для ограничения ручного подъема полотна.
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="radio" name="lock-device" value="none" data-price="0" {{ $product->lock_device == 'none' ? 'checked' : '' }}>
                                            <span class="option-name">Без блокировки</span>
                                        </label>
                                        <div class="option-description">
                                            Применяется в основном при изделии для сантехнических проемов.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <div class="accordion-header">
                                    <h4>Дополнительные опции</h4>
                                    <span class="accordion-arrow">▼</span>
                                </div>
                                <div class="accordion-content">
                                    <div class="additional-options">
                                        <label class="option-label">
                                            <input type="checkbox" name="ral-paint" value="ral-paint" data-price="{{ $product->ral_price ?? 0 }}" {{ $product->ral_paint ? 'checked' : '' }}>
                                            <span class="option-name">Покраска по RAL</span>
                                            {!! $renderOptionPrice($product->ral_price ?? 0) !!}
                                        </label>
                                        <div class="option-description">
                                            Возможна покраска профиля AER44m/S или AER55m/S. Профиль с пенным заполнением покраске не подлежит!
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="checkbox" name="photo-print" value="photo-print" data-price="{{ $product->photo_price ?? 0 }}" {{ $product->photo_print ? 'checked' : '' }}>
                                            <span class="option-name">Нанесение фотопечати</span>
                                            {!! $renderOptionPrice($product->photo_price ?? 0) !!}
                                        </label>
                                        <div class="option-description">
                                            Возможна нанесение любого рисунка путем фотопечати на всем изделии.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <meta name="csrf-token" content="{{ csrf_token() }}">

                    <div class="prodForm__howMany"> <button class="minus">-</button><input type="text"
                            class="quantity-input" placeholder="1" value="1" /><button
                            class="plus">+</button></div>
                    <div class="prodForm__priceAndAddToCart">
                        @php
                            $calcBasePrice = (int) ($product->min_price ?? 0);
                            $calcDiscount = (float) ($product->discount ?? 0);
                            $calcDisplayPrice = $calcBasePrice > 0 ? (int) floor($calcBasePrice * (1 - $calcDiscount / 100)) : null;
                        @endphp
                        <div class="prodForm__price" data-base-price="{{ $calcBasePrice }}">
                            {{ $calcDisplayPrice ? 'Цена: ' . number_format($calcDisplayPrice, 0, '', ' ') . '₽' : 'Цена по запросу' }}
                        </div>
                        <button class="prodForm__addToCart" data-id="{{ $product->id }}"> Добавить в
                            корзину </button>
                    </div>
                </div>
            </div>
            <section class="prodTabs ">
                <h2 class="prodTabs__title title"> <span>Подробнее о товаре</span><svg width="114" height="35"
                        viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                            stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                    </svg></h2>
            </section>


            <div class="tabs tabsWrapJs s-seo">

                <div class="tabs__nav tabsNavJs">
                    <div class="tabs__link">Характеристики</div>
                    @foreach ($tabs as $tab)
                        <div class="tabs__link">{{ $tab->title }}</div>
                    @endforeach
                    @if ($product->seo)
                        <div class="tabs__link">Подробнее</div>
                    @endif

                </div>

                <div class="tabs__container tabsJs">
                    <div class="tabs__item">{!! $product->characteristic !!}</div>
                    @foreach ($tabs as $tab)
                        <div class="tabs__item">{!! $tab->tab !!}</div>
                    @endforeach
                    @if ($product->seo)
                        <div class="tabs__item">{!! $product->seo !!}</div>
                    @endif

                </div>


            </div>
        </section>




        <x-front.section.map></x-front.section.map>




    </main>


    <x-front.footer :headerInfo="$headerInfo" :curtainSubcats="$curtainSubcats" :blindSubcats="$blindSubcats"></x-front.footer>
    <x-front.popups></x-front.popups>

    @vite('resources/js/main.js')
    @vite('resources/js/swiper.js')
    <script src="https://kit.fontawesome.com/9d3fa3c0db.js" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {


            const productsContainer = document.getElementById('products-container');
            const slides = document.querySelectorAll('.prodForm');

            function hideFreeOptionPrices(root = document) {
                root.querySelectorAll('.option-price').forEach((element) => {
                    const amount = parseInt(element.textContent.replace(/\D/g, ''), 10) || 0;
                    if (amount <= 0) {
                        element.remove();
                    }
                });
            }

            hideFreeOptionPrices();



            function getPrice(arr, modelFromRequest, clothRequest, prodWidth, prodHeight, medelId) {
                arr.forEach(slide => {
                    const widthInput = slide.querySelector('.width-input');
                    const heightInput = slide.querySelector('.height-input');
                    const priceElement = slide.querySelector('.prodForm__price');
                    const modelSelect = slide.querySelector('.modelSelect');
                    const modelId = medelId;

                    const prodTitleTorequest = slide.querySelector('.prodForm__formTitle').innerText
                    
                    // Получаем тип монтажа и запорного устройства
                    const installationType = slide.querySelector('input[name="widhType' + (slide.querySelector('.prodForm__addToCart')?.getAttribute('data-id') || '') + '"]:checked')?.value || 'inside';
                    const lockType = slide.querySelector('input[name="lock-type"]:checked')?.value || 'sliders';
                    const lockPrice = parseInt(slide.querySelector('input[name="lock-type"]:checked')?.dataset.price || 0);
                    
                    const clothInput = slide.querySelector('.cloth');
                    const discountInput = slide.querySelector('.discount');

                    let counterMinusBtn = slide.querySelector('.minus');
                    let counterPlusBtn = slide.querySelector('.plus');
                    let counterInput = slide.querySelector('.quantity-input');
                    
                    // Удаляем старые обработчики перед добавлением новых
                    function removeEventListeners(element, events) {
                        const clone = element.cloneNode(true);
                        element.replaceWith(clone);
                        return clone;
                    }
                    
                    // Очищаем обработчики перед добавлением
                    counterMinusBtn = removeEventListeners(counterMinusBtn, ['click']);
                    counterPlusBtn = removeEventListeners(counterPlusBtn, ['click']);
                    counterInput = removeEventListeners(counterInput, ['input']);
                    
                    let currentBasePrice = parseFloat(priceElement.dataset.basePrice) || parseInt(priceElement.textContent.replace(/\D/g, ''), 10) || 0;
                    let currentPriceRequest = 0;

                    // Пересчет цены с учетом количества и скидки
                    function rebuildPrice(price, counterValue, discount = 0) {
                        if (price <= 0 || isNaN(price)) {
                            priceElement.textContent = 'Цена по запросу';
                            return;
                        }

                        const discountedPrice = price - (price * discount / 100);
                        let priceNow = counterValue * discountedPrice;
                        
                        // Добавляем стоимость опций из аккордеона
                        const form = slide.closest('.prodForm');

                        const selectedLockType = form.querySelector('input[name="lock-type"]:checked');
                        if (selectedLockType) {
                            priceNow += parseInt(selectedLockType.dataset.price || 0) * counterValue;
                        }
                        
                        // Тип монтажа
                        const installationType = form.querySelector('input[name="installation-type"]:checked');
                        if (installationType) {
                            priceNow += parseInt(installationType.dataset.price || 0) * counterValue;
                        }
                        
                        // Тип управления
                        const controlType = form.querySelector('input[name="control-type"]:checked');
                        if (controlType) {
                            priceNow += parseInt(controlType.dataset.price || 0) * counterValue;
                        }
                        
                        // Блокирующее устройство
                        const lockDevice = form.querySelector('input[name="lock-device"]:checked');
                        if (lockDevice) {
                            priceNow += parseInt(lockDevice.dataset.price || 0) * counterValue;
                        }
                        
                        // Дополнительные опции (чекбоксы)
                        const ralPaint = form.querySelector('input[name="ral-paint"]:checked');
                        if (ralPaint) {
                            priceNow += parseInt(ralPaint.dataset.price || 0) * counterValue;
                        }
                        
                        const photoPrint = form.querySelector('input[name="photo-print"]:checked');
                        if (photoPrint) {
                            priceNow += parseInt(photoPrint.dataset.price || 0) * counterValue;
                        }
                        
                        // Преобразуем цену в целое число
                        priceNow = Math.floor(priceNow);
                        priceElement.textContent = `Цена: ${priceNow}₽`;
                    }

                    // Функция для получения и обновления цены
                    function fetchPrice() {
                        const width = widthInput.value;
                        const height = heightInput.value;
                        const quantity = parseInt(counterInput.value) || 1;

                        let model = modelSelect ? modelSelect.value : modelFromRequest;
                        let cloth = clothRequest || clothInput.value;
                        const discount = parseFloat(discountInput?.value) || 0;

                        if (!width || !height) return;

                        const requestId = ++currentPriceRequest;
                        const params = new URLSearchParams({
                            width,
                            height,
                            model: model || '',
                            control: false,
                            cloth: cloth || '',
                            modelId: modelId || '',
                            prodTitle: `Сантехнические роллеты ${prodTitleTorequest}`,
                        });

                        fetch(`/sheet-names?${params.toString()}`)
                            .then(response => response.json())
                            .then(data => {
                                if (requestId !== currentPriceRequest) {
                                    return;
                                }

                                const basePrice = data.price || 0;
                                currentBasePrice = Number(basePrice) || 0;
                                priceElement.dataset.basePrice = currentBasePrice;
                                rebuildPrice(currentBasePrice, quantity, discount);
                            })
                            .catch(error => console.error('Ошибка при получении цены:', error));
                    }

                    // Инициализация количества
                    counterInput.value = counterInput.value || 1;

                    // Обработчики для изменения количества товаров
                    counterMinusBtn.addEventListener('click', () => {
                        let currentValue = parseInt(counterInput.value) || 1;
                        if (currentValue > 1) {
                            counterInput.value = currentValue - 1;
                            rebuildPrice(currentBasePrice, parseInt(counterInput.value) || 1, parseFloat(discountInput?.value) || 0);
                        }
                    });

                    counterPlusBtn.addEventListener('click', () => {
                        let currentValue = parseInt(counterInput.value) || 1;
                        counterInput.value = currentValue + 1;
                        rebuildPrice(currentBasePrice, parseInt(counterInput.value) || 1, parseFloat(discountInput?.value) || 0);
                    });

                    // Для ввода вручную
                    counterInput.addEventListener('input', () => {
                        let value = parseInt(counterInput.value);
                        if (isNaN(value) || value < 1) {
                            counterInput.value = 1;
                        }
                        rebuildPrice(currentBasePrice, parseInt(counterInput.value) || 1, parseFloat(discountInput?.value) || 0);
                    });

                    // Изначально показываем минимальную цену товара без запроса к таблице.
                    rebuildPrice(currentBasePrice, parseInt(counterInput.value) || 1, parseFloat(discountInput?.value) || 0);

                    // Обновление цены при изменении параметров
                    widthInput.addEventListener('input', fetchPrice);
                    heightInput.addEventListener('input', fetchPrice);
                    
                    // Добавляем обработчики для всех опций аккордеона
                    const form = slide.closest('.prodForm');
                    
                    // Радио-кнопки
                    form.querySelectorAll('input[type="radio"]').forEach(radio => {
                        radio.addEventListener('change', fetchPrice);
                    });
                    
                    // Чекбоксы
                    form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                        checkbox.addEventListener('change', fetchPrice);
                    });
                });
            }


            let allCards = document.querySelectorAll('.card')

            // Поскольку похожие и альтернативные товары удалены, не делаем запросы для карточек
            // allCards.forEach(element => {
            //     ... код для карточек ...
            // });

            getPrice(slides);




            function loadPopupsContent() {
                // Поскольку похожие товары удалены из шаблона, функция не нужна
                // let allQuickButtons = document.querySelectorAll('.quickProd')
                // if (allQuickButtons.length === 0) return;
                // ... остальной код ...
            }

            loadPopupsContent();

            // Инициализация аккордеона параметров товара
            document.querySelectorAll('.accordion-header').forEach(header => {
                header.addEventListener('click', function() {
                    const accordionItem = this.closest('.accordion-item');
                    const isActive = accordionItem.classList.contains('active');
                    
                    // Закрываем все аккордеоны
                    document.querySelectorAll('.accordion-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    
                    // Открываем текущий, если он был закрыт
                    if (!isActive) {
                        accordionItem.classList.add('active');
                    }
                });
            });

        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.prodForm__addToCart').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    const formWrapper = button.closest('.prodForm');
                    const productId = button.getAttribute('data-id');

                    if (!productId) {
                        console.error('productId не найден');
                        return;
                    }

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')
                        .getAttribute('content');

                    const widthToCalc = formWrapper.querySelector('.width-input')?.value || '';
                    const heightToCalc = formWrapper.querySelector('.height-input')?.value || '';
                    const prodsCouunter = formWrapper.querySelector('.quantity-input')?.value || 1;
                    const prodPriceText = formWrapper.querySelector('.prodForm__price')
                        ?.innerText || '';
                    const prodPrice = parseInt(prodPriceText.replace(/\D/g, ''), 10) || 0;
                    const cardCounter = document.querySelector('.header__cartCounter')

                    fetch('/cart/add', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                productId,
                                width: widthToCalc,
                                height: heightToCalc,
                                quantity: prodsCouunter,
                                price: prodPrice,
                                ...(window.Shop?.collectCartOptions(formWrapper, button) || {}),
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert(data.message);
                                cardCounter.innerHTML = data.cart_count
                            } else {
                                alert('Ошибка добавления товара в корзину');
                            }
                        })
                        .catch(error => {
                            console.error('Ошибка:', error);
                            alert('Произошла ошибка. Попробуйте еще раз.');
                        });
                });
            });
        });
    </script>


</body>

</html>
