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
                                $productMainImage = $product->image_thumb_path ?: $product->image_path;
                                $productFabricImage = $product->fabric_thumb_path ?: $product->fabric_photo;
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
                                    $sameMainImage = $sameProduct->image_thumb_path ?: $sameProduct->image_path;
                                    $sameFabricImage = $sameProduct->fabric_thumb_path ?: $sameProduct->fabric_photo;
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
                    {{-- <div class="sidebarFilter__statusWrap filterColors colorsForCalc">
                        <div class="sidebarFilter__labelText complectTitle">Цвет комплектации</div>

                        <div class="sidebarFilter__paramsWrap">


                            <div class="radio-buttons">
                                <label class="radio-button active">
                                    <input class="controlColor" type="radio" name="choice{{ $product->id }}"
                                        value="#fff" checked />
                                    <img src="../../img/fur.jpg" alt="Option 1">
                                </label>
                                <label class="radio-button">
                                    <input class="controlColor" type="radio" name="choice{{ $product->id }}"
                                        value="#000" />
                                    <img src="../../img/fur.jpg" alt="Option 2">
                                </label>
                                <label class="radio-button">
                                    <input class="controlColor" type="radio" name="choice{{ $product->id }}"
                                        value="#eee" />
                                    <img src="../../img/fur.jpg" alt="Option 3">
                                </label>
                            </div>




                        </div>

                    </div> --}}
                </div>

                <div class="prodForm__calcFormWrap">
                    <div class="prodForm__formSubtitle">{{ $category->titleh1 }}</div>
                    <div class="prodForm__formTitle">{{ $product->h1 }}</div>
                    <div class="prodForm__description">
                        <p>{{ $product->first_screenn_description }}...</p><span class="more">Подробнее</span>
                    </div>
                    {{-- <select class="select-js modelSelect" name="model">
                            @foreach ($models as $model)
                                <option value="{{ $model->id }}">{{ $model->title }}</option>
                            @endforeach
                        </select> --}}

                    {{--
                        <select class="select-js modelSelect" name="model" id="model-select-{{ $product->id }}">
                            <option value="" disabled selected>Выберите модель</option>
                            @foreach ($models as $model)
                                <!-- Предполагаем, что $models содержит все модели -->
                                <option value="{{ $model->title }}" @if ($product->model_id == $model->id) selected @endif>
                                    {{ $model->title }} <!-- Выводим название модели -->
                                </option>
                            @endforeach
                        </select> --}}
                    <input type="hidden" name="modelSelect" class="modelSelect" value="{{ $product->model_title }}">

                    <input type="hidden" name="cloth" class="cloth" value="{{ $product->cloth }}">

                    {{-- <input type="hidden" name="model" class="model" value="{{ $model->title }}"> --}}

                    @if ($model)
                        <input type="hidden" name="model" class="model" value="{{ $model->title }}">
                    @endif

                    <input type="hidden" class="discount" value="{{ $product->discount }}">
                    {{-- <div class="prodForm__colorPickerWrap">
                            <div class="prodForm__colorPickerTitle">Выбирите цвет:</div>
                            <div class="prodForm__colorPicker">
                                <div class="prodForm__color red active"></div>
                                <div class="prodForm__color green"></div>
                                <div class="prodForm__color blue"></div>
                            </div>
                        </div> --}}

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
                            <div class="cartForm__listTitle">Тип замера </div>
                            <ul>
                                <li>
                                    <label>
                                        <input class="widthType" type="radio" name="widhType{{ $product->id }}"
                                            value="Ширина по ткани" checked>
                                        <span>Ширина по ткани</span>
                                    </label>
                                </li>
                                <li>
                                    <label>
                                        <input class="widthType" type="radio" name="widhType{{ $product->id }}"
                                            value="Ширина по габариту">
                                        <span>Ширина по габариту</span>

                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <meta name="csrf-token" content="{{ csrf_token() }}">
                    <select class="select-js side" name="select">
                        {{-- <option value="Не выбрано" >Выберите управление</option> --}}
                        <option value="Левое" selected="selected">Левое управление</option>
                        <option value="Правое">Правое управление</option>
                    </select>

                    <div class="sidebarFilter" style="margin-bottom: 20px">
                        <div class="sidebarFilter__paramsWrap">

                            <label class="sidebarFilter__label" for="control{{ $product->id }}">
                                <input class="control" type="checkbox" id="control{{ $product->id }}"
                                    name="control">
                                <span class="checkmark"><i class="fas fa-check" aria-hidden="true"></i></span>
                                <span class="sidebarFilter__labelText">Электропривод + пульт
                                    управления</span>
                            </label>


                        </div>
                    </div>

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
        {{-- <x-front.section.delivery :title="$homePageFields->section_delivery_title" :topText="$homePageFields->section_delivery_top_text" :bottomText="$homePageFields->section_delivery_bottom_text"
            :iconCards="$iconCards"></x-front.section.delivery> --}}

        <div class="s-populars wrapper blueControls prodAdd">
            <div class="s-populars__title-wrap">
                <h2 class="s-populars__title title"> <span>Похожие товары</span><svg width="114" height="35"
                        viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                            stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                    </svg></h2>
            </div>
            <div class="s-populars__cards">
                <div class="s-populars__swiper swiper">
                    <div class="swiper-wrapper">
                        @foreach ($seamlesProds as $relatedProduct)
                            <div class="s-populars__slide swiper-slide card" id="prod{{ $relatedProduct->id }}"
                                data-modelid="{{ $relatedProduct->model_id ?? '' }}"
                                data-model="{{ $relatedProduct->model_title }}"
                                data-cloth="{{ $relatedProduct->cloth }}"
                                data-discount="{{ $relatedProduct->discount }}">
                                <div class="bigProdCard">
                                    <div class="bigProdCard__wrap">
                                        <div class="bigProdCard__img-wrap">
                                            <div class="bigProdCard__imgCustomWrap">
                                                @php
                                                    $relatedMainImage = $relatedProduct->image_thumb_path ?: $relatedProduct->image_path;
                                                    $relatedFabricImage = $relatedProduct->fabric_thumb_path ?: $relatedProduct->fabric_photo;
                                                @endphp
                                                @if ($relatedMainImage)
                                                    <img src="{{ asset($relatedMainImage) }}"
                                                        alt="" />
                                                @endif

                                                @if ($relatedFabricImage)
                                                    <img src="{{ asset($relatedFabricImage) }}"
                                                        alt="" />
                                                @endif

                                            </div>
                                            <div class="bigProdCard__controls">
                                                <div class="bigProdCard__cart control"><i
                                                        class="fas fa-cart-arrow-down"></i>
                                                    <div class="bigProdCard__toolTip">В корзину</div>
                                                </div>
                                                <div class="bigProdCard__quckView control quickProd"
                                                    data-modal="#popupProd" data-prod="{{ $relatedProduct->id }}"><i
                                                        class="fas fa-eye"></i>
                                                    <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                                </div>
                                                <div class="bigProdCard__favorites control"><i
                                                        class="far fa-heart"></i>
                                                    <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bigProdCard__info">
                                            <a class="bigProdCard__category"
                                                href="">{{ $relatedProduct->category->titleh1 }}</a>
                                            <a class="bigProdCard__title"
                                                href="{{ route('product.show', [$relatedProduct->category->slug, $relatedProduct->subcategory->slug, $relatedProduct->slug]) }}/">
                                                {{ $relatedProduct->h1 }}
                                            </a>
                                            @if ($relatedProduct->min_width || $relatedProduct->min_height)
                                                <div class="bigProdCard__meta">
                                                    От
                                                    @if ($relatedProduct->min_width)
                                                        {{ $relatedProduct->min_width }} мм
                                                    @endif
                                                    @if ($relatedProduct->min_width && $relatedProduct->min_height)
                                                        x
                                                    @endif
                                                    @if ($relatedProduct->min_height)
                                                        {{ $relatedProduct->min_height }} мм
                                                    @endif
                                                </div>
                                            @endif
                                            <div class="bigProdCard__priceWrap">
                                                @php
                                                    $relatedMinPrice = (float) ($relatedProduct->min_price ?? 0);
                                                    $relatedDiscount = (float) ($relatedProduct->discount ?? 0);
                                                    $relatedDiscountedPrice = floor($relatedMinPrice * (1 - $relatedDiscount / 100));
                                                @endphp

                                                @if ($relatedMinPrice > 0 && $relatedDiscount > 0)
                                                    <span class="normalPrice">{{ number_format($relatedMinPrice, 0, '', ' ') }}₽</span>
                                                    <span class="discount">{{ number_format($relatedDiscountedPrice, 0, '', ' ') }}₽</span>
                                                @elseif ($relatedMinPrice > 0)
                                                    <span class="discount">{{ number_format($relatedMinPrice, 0, '', ' ') }}₽</span>
                                                @else
                                                    <span class="discount">Цена по запросу</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach


                    </div>
                </div>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"> </div>
        </div>

        <div class="s-populars wrapper blueControls prodAlt">
            <div class="s-populars__title-wrap">
                <h2 class="s-populars__title title"> <span>Альтернативные товары </span><svg width="114"
                        height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                            stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                    </svg></h2>
            </div>
            <div class="s-populars__cards">
                <div class="s-populars__swiper swiper">
                    <div class="swiper-wrapper">

                        @foreach ($altProducts as $altProduct)
                            <div class="s-populars__slide swiper-slide card" id="prod{{ $altProduct->id }}"
                                data-modelid="{{ $altProduct->model_id ?? '' }}"
                                data-model="{{ $altProduct->model_title }}" data-cloth="{{ $altProduct->cloth }}"
                                data-discount="{{ $altProduct->discount }}">
                                <div class="bigProdCard">
                                    <div class="bigProdCard__wrap">
                                        <div class="bigProdCard__img-wrap">


                                            <div class="bigProdCard__imgCustomWrap">
                                                @php
                                                    $altMainImage = $altProduct->image_thumb_path ?: $altProduct->image_path;
                                                    $altFabricImage = $altProduct->fabric_thumb_path ?: $altProduct->fabric_photo;
                                                @endphp
                                                @if ($altMainImage)
                                                    <img src="{{ asset($altMainImage) }}" alt="" />
                                                @endif

                                                @if ($altFabricImage)
                                                    <img src="{{ asset($altFabricImage) }}" alt="" />
                                                @endif
                                            </div>

                                            <div class="bigProdCard__controls">
                                                <div class="bigProdCard__cart control"><i
                                                        class="fas fa-cart-arrow-down"></i>
                                                    <div class="bigProdCard__toolTip">В корзину</div>
                                                </div>
                                                <div class="bigProdCard__quckView control quickProd"
                                                    data-prod="{{ $altProduct->id }}" data-modal="#popupProd"><i
                                                        class="fas fa-eye"></i>
                                                    <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                                </div>
                                                <div class="bigProdCard__favorites control"><i
                                                        class="far fa-heart"></i>
                                                    <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bigProdCard__info">
                                            <a class="bigProdCard__category"
                                                href="">{{ $altProduct->category->titleh1 }}</a>
                                            <a class="bigProdCard__title"
                                                href="{{ route('product.show', [$altProduct->category->slug, $altProduct->subcategory->slug, $altProduct->slug]) }}/">
                                                {{ $altProduct->h1 }}
                                            </a>
                                            @if ($altProduct->min_width || $altProduct->min_height)
                                                <div class="bigProdCard__meta">
                                                    От
                                                    @if ($altProduct->min_width)
                                                        {{ $altProduct->min_width }} мм
                                                    @endif
                                                    @if ($altProduct->min_width && $altProduct->min_height)
                                                        x
                                                    @endif
                                                    @if ($altProduct->min_height)
                                                        {{ $altProduct->min_height }} мм
                                                    @endif
                                                </div>
                                            @endif
                                            <div class="bigProdCard__priceWrap">
                                                @php
                                                    $altMinPrice = (float) ($altProduct->min_price ?? 0);
                                                    $altDiscount = (float) ($altProduct->discount ?? 0);
                                                    $altDiscountedPrice = floor($altMinPrice * (1 - $altDiscount / 100));
                                                @endphp

                                                @if ($altMinPrice > 0 && $altDiscount > 0)
                                                    <span class="normalPrice">{{ number_format($altMinPrice, 0, '', ' ') }}₽</span>
                                                    <span class="discount">{{ number_format($altDiscountedPrice, 0, '', ' ') }}₽</span>
                                                @elseif ($altMinPrice > 0)
                                                    <span class="discount">{{ number_format($altMinPrice, 0, '', ' ') }}₽</span>
                                                @else
                                                    <span class="discount">Цена по запросу</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach


                    </div>
                </div>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"> </div>
        </div>

        {{-- <x-front.section.seo :seoSection="$product->seo"></x-front.section.seo> --}}
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



            function getPrice(arr, modelFromRequest, clothRequest, prodWidth, prodHeight, medelId) {
                arr.forEach(slide => {
                    const widthInput = slide.querySelector('.width-input');
                    const heightInput = slide.querySelector('.height-input');
                    const priceElement = slide.querySelector('.prodForm__price');
                    const modelSelect = slide.querySelector('.modelSelect');
                    const modelId = medelId;
                    // console.log(modelId);

                    const prodTitleTorequest = slide.querySelector('.prodForm__formTitle').innerText
                    const controlInput = slide.querySelector('.control') || {
                        checked: false
                    };
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

                    // Пересчет цены с учетом количества и скидки
                    function rebuildPrice(price, counterValue, discount = 0) {
                        const discountedPrice = price - (price * discount / 100);
                        let priceNow = counterValue * discountedPrice;
                        // Преобразуем цену в целое число
                        priceNow = Math.floor(priceNow);
                        priceElement.textContent = `Цена: ${priceNow}₽`;
                    }

                    // Функция для получения и обновления цены
                    function fetchPrice() {
                        const width = widthInput.value;
                        const height = heightInput.value;
                        const quantity = parseInt(counterInput.value) || 1;

                        let model
                        if (modelSelect) {
                            model = modelSelect.value
                        } else {
                            model = modelFromRequest

                        }
                        console.log(model);

                        let cloth = clothRequest || clothInput.value;

                        const control = controlInput.checked;
                        const discount = parseFloat(discountInput?.value) || 0;

                        if (!width || !height) return;

                        fetch(
                                `/sheet-names?width=${width}&height=${height}&model=${model}&control=${control}&cloth=${cloth}&modelId=${modelId}&prodTitle=${prodTitleTorequest}`
                            )
                            .then(response => response.json())
                            .then(data => {
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

                    // Изначальный расчет при загрузке
                    rebuildPrice(currentBasePrice, parseInt(counterInput.value) || 1, parseFloat(discountInput?.value) || 0);

                    // Обновление цены при изменении ширины, высоты или других параметров
                    widthInput.addEventListener('input', fetchPrice);
                    heightInput.addEventListener('input', fetchPrice);
                    if (controlInput && controlInput instanceof Element) {
                        controlInput.addEventListener('input', fetchPrice);
                    }
                });
            }


            getPrice(slides);




            function loadPopupsContent() {

                let allQuickButtons = document.querySelectorAll('.quickProd')



                allQuickButtons.forEach(element => {
                    element.addEventListener('click', () => {
                        const prodId = element.dataset.prod;
                        console.log('prodId:', prodId);
                        if (!prodId) return;
                        setTimeout(() => {
                            wrapPopupProd();
                        }, 50);

                        console.log('Запрос к /popup с prodId:', prodId);

                        // Получаем данные о товаре с сервера
                        fetch(`/popup/${prodId}`)
                            .then(response => response.json())
                            .then(product => {
                                // Заполняем попап данными товара
                                document.querySelector('#popupProd .prodForm__formSubtitle')
                                    .innerText = product.title;
                                document.querySelector('#popupProd .prodForm__formTitle')
                                    .innerText = `Заказать ${product.title}`;
                                document.querySelector('#popupProd .prodForm__description p')
                                    .innerText = product.first_screenn_description + ' ';
                                let prodImg = document.querySelectorAll(
                                    '#popupProd .prodForm__imgWrap img')

                                let img1src = `${product.image_path}`
                                let img2src = `${product.fabric_photo}`


                                // prodImg[1].src = img2src; 

                                if (img1src != 'null') {
                                    prodImg[0].src = img1src || '';
                                    prodImg[1].src = img2src || '';


                                } else {

                                    prodImg[1].style.display = 'none'
                                    prodImg[0].src = img2src;

                                }
                                // Корректируем путь


                                // console.log(product.gallery);
                                // Очищаем старую галерею
                                let gallery = document.querySelector(
                                    '#popupProd .prodForm__bar');
                                gallery.innerHTML = '';

                                // Добавляем изображения с ссылками
                                product.gallery.forEach(related => {
                                    let link = document.createElement('a');
                                    link.href = related.link; // Ссылка на товар
                                    let img = document.createElement('img');
                                    if (related.image) {

                                        img.src =
                                            `${related.image}`; // Путь к изображению
                                    } else {

                                        img.src =
                                            `${related.fabric_photo}`; // Путь к изображению
                                    }


                                    link.appendChild(
                                        img); // Вставляем изображение в ссылку
                                    gallery.appendChild(
                                        link); // Добавляем ссылку в галерею

                                });

                                console.log(product.model);

                                // Добавить id для кнопки добавить в корзину
                                document.querySelector('#popupProd .prodForm__addToCart')
                                    .setAttribute('data-id', prodId)

                                let controlLabel = document.querySelector(
                                    '#popupProd .sidebarFilter__label')
                                let controlInput = document.querySelector('#popupProd .control')
                                controlLabel.setAttribute('for', 'control' + prodId)
                                controlInput.setAttribute('id', 'control' + prodId)
                                controlInput.checked = false;

                                const prodWrap = document.querySelector('#popupProd');

                                let modelInput = prodWrap.querySelector('.model');
                                modelInput.value = product.model;
                                let clothInput = prodWrap.querySelector('.cloth');
                                clothInput.value = product.cloth;

                                let discountInput = prodWrap.querySelector('.discount');
                                discountInput.value = product.discount;

                                const popupPriceElement = prodWrap.querySelector('.prodForm__price');
                                const popupBasePrice = Number(product.min_price) || 0;
                                const popupDiscount = Number(product.discount) || 0;
                                if (popupPriceElement) {
                                    popupPriceElement.dataset.basePrice = popupBasePrice;
                                    if (popupBasePrice > 0) {
                                        const popupDisplayPrice = Math.floor(popupBasePrice * (1 - popupDiscount / 100));
                                        popupPriceElement.textContent = `Цена: ${popupDisplayPrice}₽`;
                                    } else {
                                        popupPriceElement.textContent = 'Цена по запросу';
                                    }
                                }

                                let widthInput = prodWrap.querySelector('.width-input');
                                let heightInput = prodWrap.querySelector('.height-input');


                                if (widthInput && product.min_width) {
                                    widthInput.value = product.min_width;
                                }

                                if (heightInput && product.min_height) {
                                    heightInput.value = product.min_height;
                                }



                                setTimeout(() => {
                                    getPrice([prodWrap], product.model, product.cloth,
                                        product.min_width, product.min_height,
                                        product.model_id)
                                }, 100);
                            })
                            .catch(error => {
                                console.error('Ошибка при загрузке данных товара:', error);
                            });

                    })
                });
            }


            loadPopupsContent()

        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.prodForm__addToCart').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    const formWrapper = button.closest('.prodForm');
                    const productId = button.getAttribute('data-id'); // читаем data-id, а не id

                    if (!productId) {
                        console.error('productId не найден');
                        return;
                    }

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')
                        .getAttribute('content');

                    const widthToCalc = formWrapper.querySelector('.width-input')?.value || '';
                    const heightToCalc = formWrapper.querySelector('.height-input')?.value || '';
                    const controlCheck = formWrapper.querySelector('.control')?.checked || false;
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
                                control: controlCheck,
                                quantity: prodsCouunter,
                                price: prodPrice,
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
    {{-- <style>
    .tabs__link:nth-child(2){
        display: none;
    }
    .tabs__item:nth-child(2){
        display: none;
    }
</style> --}}

</body>

</html>
