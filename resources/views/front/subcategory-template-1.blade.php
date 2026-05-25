{{-- @include('front.head') --}}
<x-front.head title="{{ $subcategory->title }}" description="{{ $subcategory->description }}"></x-front.head>
@vite('resources/css/prod.css')
<style>
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
</style>

<body class="p-index">

    <x-front.header :categoriesInCatalogMenu="$categoriesInCatalogMenu" :categoriesInHeaderMenu="$categoriesInHeaderMenu" :cart="$cart" :headerInfo="$headerInfo"></x-front.header>

    <main class="layout">

        <!-- Первый экран -->
        <section class="s-catMain wrapper">
            <div class="s-catMain__img"><img src="{{ Storage::url($subcategory->img) }}" alt="" /></div>
            <div class="breadcrumbs">
                <ul class="breadcrumbs__list">
                    <li class=""><a class="breadcrumbs__link" href="{{ route('front.home') }}">Главная</a></li>

                    <li class="breadcrumbs__item"><svg class="breadcrumbs__arrow" width="5" height="9"
                            viewBox="0 0 5 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#000"
                                d="M3.4575 4.5L2.97878 5.0625L5.28601e-05 8.2125L0.744734 9L5.00005 4.5L0.744733 3.72007e-07L5.2211e-05 0.7875L2.97878 3.9375L3.4575 4.5Z">
                            </path>
                        </svg>
                        <a href="/{{ $subcategory->category->slug }}"
                            class="breadcrumbs__link">{{ $subcategory->category->titleh1 }}</a>

                    </li>
                    <li class="breadcrumbs__item"><svg class="breadcrumbs__arrow" width="5" height="9"
                            viewBox="0 0 5 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#000"
                                d="M3.4575 4.5L2.97878 5.0625L5.28601e-05 8.2125L0.744734 9L5.00005 4.5L0.744733 3.72007e-07L5.2211e-05 0.7875L2.97878 3.9375L3.4575 4.5Z">
                            </path>
                        </svg><span class="breadcrumbs__active">{{ $subcategory->titleh1 }}</span></li>

                </ul>
            </div>
            <h1 class="s-catMain__title title"> <span>{{ $subcategory->titleh1 }}</span><svg width="114"
                    height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                        stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"> </path>
                </svg></h1>
            <div class="s-catMain__text">{{ $subcategory->first_screen_text }}</div>
        </section>

        <!-- Вывод товаров как везде -->

            <section class="popularsWithFilter wrapper">
                <h2 class="popularsWithFilter__title title"> <span>Популярные товары</span><svg width="114"
                        height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                            stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                    </svg></h2>
                @php
                    $hidePriceFilter = $subcategory->slug === 'santehnicheskie-rolleti';
                @endphp
                <div class="popularsWithFilter__wrap">
                    @unless ($hidePriceFilter)
                    <aside class="sidebarFilter">
                        @include('front.partials.price-filter')


                        <style>
                            .popularsWithFilter__wrap{
                                grid-template-columns: 1fr
                            }
                            .popularsWithFilter__cards{
                                grid-template-columns: repeat(4, 1fr);
                            }
                            .filterMaterials {
                                display: flex;
                                flex-direction: column;
                            }

                            .filterMaterials .sidebarFilter__paramsWrap {
                                display: flex;
                                flex-wrap: wrap;
                                gap: 5px;
                            }

                            .filterMaterials .materialLabel {
                                position: relative;
                                display: inline-block;
                                width: 40px;
                                height: 40px;
                                cursor: pointer;
                                border: 2px solid transparent;
                                border-radius: 5px;
                                transition: border-color 0.3s;
                            }

                            .filterMaterials .materialLabel input:checked+.materialOverlay+.materialImage {
                                border: 2px solid #007bff;
                            }

                            .filterMaterials .materialOverlay {
                                position: absolute;
                                top: 0;
                                left: 0;
                                width: fit-content;
                                height: 100%;
                                padding: 5px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                background: rgba(0, 0, 0, 0.7);
                                color: #fff;
                                opacity: 0;
                                font-size: 12px;
                                text-align: center;
                                border-radius: 5px;
                                pointer-events: none;
                                transition: opacity 0.3s;
                                white-space: nowrap;
                                z-index: 2;
                            }

                            .filterMaterials .materialLabel:hover .materialOverlay {
                                opacity: 1;
                            }

                            .filterMaterials .materialImage {
                                width: 100%;
                                height: 100%;
                                object-fit: cover;
                                border-radius: 5px;
                            }
                        </style>
                    </aside>
                    @endunless
                    @if ($hidePriceFilter)
                        <style>
                            .popularsWithFilter__wrap{
                                grid-template-columns: 1fr
                            }
                            .popularsWithFilter__cards{
                                grid-template-columns: repeat(4, 1fr);
                            }
                        </style>
                    @endif
                    <div class="popularsWithFilter__cardsWrap">
                        <div class="popularsWithFilter__cards" id="productsWrap">
                            @include('front.partials.products')
                        </div>
                        <div class="pagination" id="pagination">
                            {{ $filterProduts->onEachSide(1)->links() }}
                        </div>
                    </div>
                </div>
            </section>

        <!-- Блоки подкатегорий / виды монтажа -->
        @if (($showSubcatSections ?? false) && !empty($subcategoriesWithProducts) && $subcategoriesWithProducts->isNotEmpty())
            <x-subcat-sections :category="$category" :subcategoriesWithProducts="$subcategoriesWithProducts" :headerInfo="$headerInfo" />
        @elseif (!empty($installationTypes) && $installationTypes->isNotEmpty())
            <x-front.section.subcategory-installation-types :installationTypes="$installationTypes" />
        @else
            <x-front.section.rollets-installation />
        @endif

        @if ($showRolletSystems ?? false)
            @php
                $templateRollerShutterSystems = $rollerShutterSystems
                    ?? \App\Models\RollerShutterSystem::where('category_id', $category->id)
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->get();
            @endphp
            @if ($templateRollerShutterSystems->isNotEmpty())
                <x-front.section.rollets-systems :systems="$templateRollerShutterSystems" />
            @endif
        @endif

        @if ($showRolletProfilePrices ?? false)
            <x-front.section.rollets-profile-prices />
        @endif

        <!-- Калькулятор -->
        @if (($showTemplateCalculator ?? true) && !empty($firstProduct))
            @php
                $product = $firstProduct;
            @endphp
            <section class="prodMain wrapper catCalculator" style="padding-top: 40px;">
                <h2 class="prodMain__title title"> <span>Рассчитать стоимость рольставен</span><svg width="114" height="35"
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
                </div>

                <div class="prodForm__calcFormWrap">
                    <div class="prodForm__formSubtitle">{{ $category->titleh1 }}</div>
                    <div class="prodForm__formTitle">{{ $product->h1 }}</div>
                    <div class="prodForm__description">
                        <p>{{ $product->first_screenn_description }}...</p><span class="more">Подробнее</span>
                    </div>

                    <input type="hidden" name="modelSelect" class="modelSelect" value="{{ $product->model_title }}">

                    <input type="hidden" name="cloth" class="cloth" value="{{ $product->cloth }}">
                    <input type="hidden" name="model" class="model" value="{{ $product->model_title }}">
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
                                        <span>Задвижки +0р</span>
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
                                            <span class="option-price">+{{ $product->overhead_price ?? 0 }}₽</span>
                                        </label>
                                        <div class="option-description">
                                            При выборе накладного монтажа необходимо к размерам проема добавьте 110мм по ширине и 250мм по высоте.
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="radio" name="installation-type" value="built-in" data-price="{{ $product->builtin_price ?? 0 }}" {{ $product->installation_type == 'built-in' ? 'checked' : '' }}>
                                            <span class="option-name">Встроенный монтаж</span>
                                            <span class="option-price">+{{ $product->builtin_price ?? 0 }}₽</span>
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
                                            <span class="option-price">+{{ $product->strap_price ?? 0 }}₽</span>
                                        </label>
                                        <div class="option-description">
                                            Грузоподъемность до 15 кг. Ручное управление.
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="radio" name="control-type" value="cardan" data-price="{{ $product->cardan_price ?? 0 }}" {{ $product->control_type == 'cardan' ? 'checked' : '' }}>
                                            <span class="option-name">Воротковый привод (кардан)</span>
                                            <span class="option-price">+{{ $product->cardan_price ?? 0 }}₽</span>
                                        </label>
                                        <div class="option-description">
                                            Грузоподъемность до 35 кг. Ручное управление.
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="radio" name="control-type" value="pim" data-price="{{ $product->pim_price ?? 0 }}" {{ $product->control_type == 'pim' ? 'checked' : '' }}>
                                            <span class="option-name">Пружинно-инерционный механизм (ПИМ)</span>
                                            <span class="option-price">+{{ $product->pim_price ?? 0 }}₽</span>
                                        </label>
                                        <div class="option-description">
                                            Грузоподъемность от 6 до 80 кг. Ручное управление.
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="radio" name="control-type" value="electric" data-price="{{ $product->electric_price ?? 6793 }}" {{ $product->control_type == 'electric' ? 'checked' : '' }}>
                                            <span class="option-name">Автоматическое управление (электропривод)</span>
                                            <span class="option-price">+{{ $product->electric_price ?? 6793 }}₽</span>
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
                                            <input type="radio" name="lock-device" value="rigel" data-price="{{ $product->rigel_price ?? 1575 }}" {{ $product->lock_device == 'rigel' ? 'checked' : '' }}>
                                            <span class="option-name">Ригельный замок (с ключом)</span>
                                            <span class="option-price">+{{ $product->rigel_price ?? 1575 }}₽</span>
                                        </label>
                                        <div class="option-description">
                                            Используется при ручном управлении. Рольставни запираются на замок. Не рекомендуется использовать при электроприводе.
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="radio" name="lock-device" value="shchyolka" data-price="{{ $product->shchyolka_price ?? 171 }}" {{ $product->lock_device == 'shchyolka' ? 'checked' : '' }}>
                                            <span class="option-name">Ручной ригель (щеколда)</span>
                                            <span class="option-price">+{{ $product->shchyolka_price ?? 171 }}₽</span>
                                        </label>
                                        <div class="option-description">
                                            Используется при ручном управлении. Любой желающий сможет открыть роллету. Не рекомендуется использовать при электроприводе.
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="radio" name="lock-device" value="upper" data-price="{{ $product->upper_price ?? 2358 }}" {{ $product->lock_device == 'upper' ? 'checked' : '' }}>
                                            <span class="option-name">Верхний ригель (верхние замки)</span>
                                            <span class="option-price">+{{ $product->upper_price ?? 2358 }}₽</span>
                                        </label>
                                        <div class="option-description">
                                            Используется при управлении воротковый привод или автоматическом управлении для ограничения ручного подъема полотна.
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="radio" name="lock-device" value="none" data-price="0" {{ $product->lock_device == 'none' ? 'checked' : '' }}>
                                            <span class="option-name">Без блокировки</span>
                                            <span class="option-price">+0₽</span>
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
                                            <input type="checkbox" name="ral-paint" value="ral-paint" data-price="{{ $product->ral_price ?? 8010 }}" {{ $product->ral_paint ? 'checked' : '' }}>
                                            <span class="option-name">Покраска по RAL</span>
                                            <span class="option-price">+{{ $product->ral_price ?? 8010 }}₽</span>
                                        </label>
                                        <div class="option-description">
                                            Возможна покраска профиля AER44m/S или AER55m/S. Профиль с пенным заполнением покраске не подлежит!
                                        </div>
                                        
                                        <label class="option-label">
                                            <input type="checkbox" name="photo-print" value="photo-print" data-price="{{ $product->photo_price ?? 3920 }}" {{ $product->photo_print ? 'checked' : '' }}>
                                            <span class="option-name">Нанесение фотопечати</span>
                                            <span class="option-price">+{{ $product->photo_price ?? 3920 }}₽</span>
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
            </section>
        @endif

        @if (!empty($workExamples) && $workExamples->isNotEmpty())
            <x-front.section.subgallery :gallerys="$workExamples" :category='$category' title=""></x-front.section.subgallery>
        @endif

        <!-- Оплата и доставка -->
        <x-front.section.delivery :title="$homePageFields->section_delivery_title" :topText="$homePageFields->section_delivery_top_text" :bottomText="$homePageFields->section_delivery_bottom_text"
            :iconCards="$iconCards"></x-front.section.delivery>

        <!-- ВОПРОСЫ И ОТВЕТЫ -->
        @if ($subcategory->faq_html)
            <section class="s-faq wrapper">
                <div class="s-faq__container">
                    <div class="s-faq__title-wrap">
                        <h2 class="s-faq__title title"> <span>Вопросы и ответы</span><svg width="114" height="35"
                                viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                                    stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                            </svg></h2>
                    </div>
                    <div class="customAccardeonWrap">
                        {!! $subcategory->faq_html !!}
                    </div>
                </div>
            </section>

            <style>
                .customAccardeonWrap {
                   
                }
                 .customAccardeonWrap .faq-block .faq-item {
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

                .customAccardeonWrap .faq-block .faq-item .faq-question {
                    position: relative;
                    z-index: 3;
                    padding-right: 10px;
                    list-style: none;
                    cursor: pointer;
                }

                .customAccardeonWrap .faq-block .faq-item .faq-answer {
                    padding-top: 10px;
                }

                .customAccardeonWrap .faq-block .faq-item .faq-arrow {
                    display: none;
                }

                .customAccardeonWrap .faq-block .faq-item:before {

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

                .customAccardeonWrap .faq-block .faq-item:after {
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

                .customAccardeonWrap .faq-block .faq-item .faq-question::marker {
                    display: none;
                }
            </style>
        @else
            <x-front.section.faqcat title="Вопросы и ответы" :faqs="$faqs"></x-front.section.faqcat>
        @endif

        <!-- СЕО ТЕКСТ -->
        @if ($showSeoSection ?? true)
            <x-front.section.seo :seoSection="$subcategory->seo"></x-front.section.seo>
        @endif

        <!-- Все категории -->
        <section class="s-tags wrapper">
            <h2 class="s-tags__title title"> <span>Все категории</span><svg width="114" height="35"
                    viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                        stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                </svg></h2>
            <div class="s-tags__tags">
                <div class="accardionJs">
                    <div class="accardion__title">Все категории</div>
                    <div class="accardion__content">
                        @if ($seoCats->isNotEmpty())
                            @foreach ($seoCats as $cat)
                                <a class="s-tags__tag"
                                    href="{{ route('subcategory.show', ['category_slug' => $cat->category->slug, 'subcategory_slug' => $cat->slug]) }}">
                                    {{ $cat->titleh1 ?? $cat->title }}
                                </a>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </section>

    </main>

    <x-front.footer :headerInfo="$headerInfo" :curtainSubcats="$curtainSubcats" :blindSubcats="$blindSubcats"></x-front.footer>

    <x-front.popups></x-front.popups>

    @vite('resources/js/main.js')
    @vite('resources/js/swiper.js')
    <script src="https://kit.fontawesome.com/9d3fa3c0db.js" crossorigin="anonymous"></script>

        <script>
        document.addEventListener('DOMContentLoaded', function() {

            const tabs = document.querySelectorAll('.s-populars__tabsNav li');
            const productsContainer = document.getElementById('products-container');
            const slides = document.querySelectorAll('.catCalculator .prodForm');
            const useSantehPriceMatrix = @json($subcategory->slug === 'santehnicheskie-rolleti');
            // console.log(calcForm);

            document.querySelectorAll('.product-params-accordion .accordion-header').forEach((header) => {
                header.addEventListener('click', () => {
                    const item = header.closest('.accordion-item');
                    if (item) {
                        item.classList.toggle('active');
                    }
                });
            });
            function wrapPopupProd() {
                if (document.querySelector('.modal')) {} else {
                    let popup = document.getElementById('popupProd');
                    if (!popup) return;

                    popup.style.display = "block";
                    let modal = document.createElement('div');
                    modal.className = 'modal fadeIn';
                    modal.style.display = 'block';

                    let container = document.createElement('div');
                    container.className = 'modal__container';

                    let closeButton = document.createElement('button');
                    closeButton.className = 'modal__close';

                    closeButton.addEventListener('click', () => {
                        modal.classList.add('fadeOut');

                        setTimeout(() => {
                            popup.style.display = '';
                            document.body.appendChild(popup);
                            document.body.removeChild(modal);
                        }, 450);
                    });

                    container.appendChild(closeButton);
                    container.appendChild(popup);
                    modal.appendChild(container);
                    document.body.appendChild(modal);

                }; // Если уже есть модалка, выходим


            }


            function renderSantehPopupForm(product, prodWrap, prodId) {
                if (!useSantehPriceMatrix) {
                    return;
                }

                const calcWrap = prodWrap.querySelector('.prodForm__calcFormWrap');
                const sizeWrap = prodWrap.querySelector('.prodForm__sizeWrap');
                if (!calcWrap || !sizeWrap) {
                    return;
                }

                calcWrap.querySelectorAll('.calcWidhType, .side, .sidebarFilter, .product-params-accordion, .popup-santeh-fields')
                    .forEach((element) => element.remove());

                const price = (value, fallback = 0) => Number(value ?? fallback) || 0;
                const checked = (actual, expected, fallback = false) => actual === expected || (!actual && fallback) ? 'checked' : '';
                const boolChecked = (value) => value ? 'checked' : '';
                const fields = document.createElement('div');
                fields.className = 'popup-santeh-fields';
                fields.innerHTML = `
                    <div class="calcWidhType">
                        <div class="cartForm__optionsList">
                            <div class="cartForm__listTitle">Тип монтажа</div>
                            <ul>
                                <li><label><input class="widthType" type="radio" name="popup-widhType${prodId}" value="Ширина по ткани" checked><span>Короб внутри (скрытый)</span></label></li>
                                <li><label><input class="widthType" type="radio" name="popup-widhType${prodId}" value="Ширина по габариту"><span>Короб снаружи</span></label></li>
                            </ul>
                        </div>
                    </div>
                    <div class="calcWidhType">
                        <div class="cartForm__optionsList">
                            <div class="cartForm__listTitle">Тип запорного устройства</div>
                            <ul>
                                <li><label><input class="widthType" type="radio" name="popup-lock-type" value="sliders" data-price="0" checked><span>Задвижки +0р</span></label></li>
                                <li><label><input class="widthType" type="radio" name="popup-lock-type" value="lock" data-price="1600"><span>Замок +1600р</span></label></li>
                            </ul>
                        </div>
                    </div>
                    <div class="product-params-accordion" style="margin-top: 30px;">
                        <div class="cartForm__optionsList">
                            <div class="cartForm__listTitle">Характеристики товара</div>
                            <div class="accordion-item">
                                <div class="accordion-header"><h4>Вид монтажа</h4><span class="accordion-arrow">▼</span></div>
                                <div class="accordion-content">
                                    <div class="installation-options">
                                        <label class="option-label"><input type="radio" name="popup-installation-type" value="overhead" data-price="${price(product.overhead_price)}" ${checked(product.installation_type, 'overhead', true)}><span class="option-name">Накладной монтаж</span><span class="option-price">+${price(product.overhead_price)}₽</span></label>
                                        <div class="option-description">При выборе накладного монтажа необходимо к размерам проема добавьте 110мм по ширине и 250мм по высоте.</div>
                                        <label class="option-label"><input type="radio" name="popup-installation-type" value="built-in" data-price="${price(product.builtin_price)}" ${checked(product.installation_type, 'built-in')}><span class="option-name">Встроенный монтаж</span><span class="option-price">+${price(product.builtin_price)}₽</span></label>
                                        <div class="option-description">При выборе встроенного монтажа рекомендуется от размеров проема отнять по ширине и высоте 5 мм.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <div class="accordion-header"><h4>Тип управления рольставни</h4><span class="accordion-arrow">▼</span></div>
                                <div class="accordion-content">
                                    <div class="control-options">
                                        <label class="option-label"><input type="radio" name="popup-control-type" value="strap" data-price="${price(product.strap_price)}" ${checked(product.control_type, 'strap', true)}><span class="option-name">Ленточный или шнуровой инерционный привод</span><span class="option-price">+${price(product.strap_price)}₽</span></label>
                                        <div class="option-description">Грузоподъемность до 15 кг. Ручное управление.</div>
                                        <label class="option-label"><input type="radio" name="popup-control-type" value="cardan" data-price="${price(product.cardan_price)}" ${checked(product.control_type, 'cardan')}><span class="option-name">Воротковый привод (кардан)</span><span class="option-price">+${price(product.cardan_price)}₽</span></label>
                                        <div class="option-description">Грузоподъемность до 35 кг. Ручное управление.</div>
                                        <label class="option-label"><input type="radio" name="popup-control-type" value="pim" data-price="${price(product.pim_price)}" ${checked(product.control_type, 'pim')}><span class="option-name">Пружинно-инерционный механизм (ПИМ)</span><span class="option-price">+${price(product.pim_price)}₽</span></label>
                                        <div class="option-description">Грузоподъемность от 6 до 80 кг. Ручное управление.</div>
                                        <label class="option-label"><input type="radio" name="popup-control-type" value="electric" data-price="${price(product.electric_price, 6793)}" ${checked(product.control_type, 'electric')}><span class="option-name">Автоматическое управление (электропривод)</span><span class="option-price">+${price(product.electric_price, 6793)}₽</span></label>
                                        <div class="option-description">Тип управление: выключатель настенный или мини пульт.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <div class="accordion-header"><h4>Блокирующие устройства</h4><span class="accordion-arrow">▼</span></div>
                                <div class="accordion-content">
                                    <div class="lock-options">
                                        <label class="option-label"><input type="radio" name="popup-lock-device" value="rigel" data-price="${price(product.rigel_price, 1575)}" ${checked(product.lock_device, 'rigel')}><span class="option-name">Ригельный замок (с ключом)</span><span class="option-price">+${price(product.rigel_price, 1575)}₽</span></label>
                                        <div class="option-description">Используется при ручном управлении. Рольставни запираются на замок. Не рекомендуется использовать при электроприводе.</div>
                                        <label class="option-label"><input type="radio" name="popup-lock-device" value="shchyolka" data-price="${price(product.shchyolka_price, 171)}" ${checked(product.lock_device, 'shchyolka')}><span class="option-name">Ручной ригель (щеколда)</span><span class="option-price">+${price(product.shchyolka_price, 171)}₽</span></label>
                                        <div class="option-description">Используется при ручном управлении. Любой желающий сможет открыть роллету. Не рекомендуется использовать при электроприводе.</div>
                                        <label class="option-label"><input type="radio" name="popup-lock-device" value="upper" data-price="${price(product.upper_price, 2358)}" ${checked(product.lock_device, 'upper')}><span class="option-name">Верхний ригель (верхние замки)</span><span class="option-price">+${price(product.upper_price, 2358)}₽</span></label>
                                        <div class="option-description">Используется при управлении воротковый привод или автоматическом управлении для ограничения ручного подъема полотна.</div>
                                        <label class="option-label"><input type="radio" name="popup-lock-device" value="none" data-price="0" ${checked(product.lock_device, 'none', true)}><span class="option-name">Без блокировки</span><span class="option-price">+0₽</span></label>
                                        <div class="option-description">Применяется в основном при изделии для сантехнических проемов.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <div class="accordion-header"><h4>Дополнительные опции</h4><span class="accordion-arrow">▼</span></div>
                                <div class="accordion-content">
                                    <div class="additional-options">
                                        <label class="option-label"><input type="checkbox" name="popup-ral-paint" value="ral-paint" data-price="${price(product.ral_price, 8010)}" ${boolChecked(product.ral_paint)}><span class="option-name">Покраска по RAL</span><span class="option-price">+${price(product.ral_price, 8010)}₽</span></label>
                                        <div class="option-description">Возможна покраска профиля AER44m/S или AER55m/S. Профиль с пенным заполнением покраске не подлежит!</div>
                                        <label class="option-label"><input type="checkbox" name="popup-photo-print" value="photo-print" data-price="${price(product.photo_price, 3920)}" ${boolChecked(product.photo_print)}><span class="option-name">Нанесение фотопечати</span><span class="option-price">+${price(product.photo_price, 3920)}₽</span></label>
                                        <div class="option-description">Возможна нанесение любого рисунка путем фотопечати на всем изделии.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                sizeWrap.insertAdjacentElement('afterend', fields);
                fields.querySelectorAll('.product-params-accordion .accordion-header').forEach((header) => {
                    header.addEventListener('click', () => {
                        const item = header.closest('.accordion-item');
                        if (item) {
                            item.classList.toggle('active');
                        }
                    });
                });
            }


            function loadPopupsContent() {

                let allQuickButtons = document.querySelectorAll('.quickProd')



                allQuickButtons.forEach(element => {
                    element.addEventListener('click', () => {

                        const prodId = element.dataset.prod;

                        if (!prodId) return;
                        setTimeout(() => {
                            wrapPopupProd();
                        }, 50);

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

                                // Добавить id для кнопки добавить в корзину
                                document.querySelector('#popupProd .prodForm__addToCart')
                                    .setAttribute('data-id', prodId)

                                let controlLabel = document.querySelector(
                                    '#popupProd .sidebarFilter__label')
                                let controlInput = document.querySelector('#popupProd .control')
                                if (controlLabel && controlInput) {
                                    controlLabel.setAttribute('for', 'control' + prodId)
                                    controlInput.setAttribute('id', 'control' + prodId)
                                    controlInput.checked = false;
                                }

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

                                renderSantehPopupForm(product, prodWrap, prodId);



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


            function getPrice(arr, modelFromRequest, clothRequest, prodWidth, prodHeight, medelId) {
                arr.forEach(slide => {
                    const widthInput = slide.querySelector('.width-input');
                    const heightInput = slide.querySelector('.height-input');
                    const priceElement = slide.querySelector('.prodForm__price');
                    const modelSelect = slide.querySelector('.modelSelect');
                    const modelInput = slide.querySelector('.model');
                    const modelId = medelId;
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
                    let currentPriceRequest = 0;

                    // Пересчет цены с учетом количества и скидки
                    function rebuildPrice(price, counterValue, discount = 0) {
                        if (price <= 0 || isNaN(price)) {
                            priceElement.textContent = 'Цена по запросу';
                            return;
                        }
                        const discountedPrice = price - (price * discount / 100);
                        let priceNow = counterValue * discountedPrice;

                        const form = slide.matches('.prodForm')
                            ? slide
                            : (slide.querySelector('.prodForm') || slide.closest('.prodForm') || slide);

                        const installationType = form.querySelector('input[name="installation-type"]:checked, input[name="popup-installation-type"]:checked');
                        if (installationType) {
                            priceNow += (parseInt(installationType.dataset.price || 0, 10) * counterValue);
                        }

                        const controlType = form.querySelector('input[name="control-type"]:checked, input[name="popup-control-type"]:checked');
                        if (controlType) {
                            priceNow += (parseInt(controlType.dataset.price || 0, 10) * counterValue);
                        }

                        const lockDevice = form.querySelector('input[name="lock-device"]:checked, input[name="popup-lock-device"]:checked');
                        if (lockDevice) {
                            priceNow += (parseInt(lockDevice.dataset.price || 0, 10) * counterValue);
                        }

                        const ralPaint = form.querySelector('input[name="ral-paint"]:checked, input[name="popup-ral-paint"]:checked');
                        if (ralPaint) {
                            priceNow += (parseInt(ralPaint.dataset.price || 0, 10) * counterValue);
                        }

                        const photoPrint = form.querySelector('input[name="photo-print"]:checked, input[name="popup-photo-print"]:checked');
                        if (photoPrint) {
                            priceNow += (parseInt(photoPrint.dataset.price || 0, 10) * counterValue);
                        }

                        // Преобразуем цену в целое число
                        priceNow = Math.floor(priceNow);
                        priceElement.textContent = `Цена: ${priceNow}₽`;
                    }

                    // Функция для получения и обновления цены
                    function fetchPrice() {
                        const width = widthInput.value;
                        const height = heightInput.value;
                        // console.log(width);

                        const quantity = parseInt(counterInput.value) || 1;

                        let model = modelFromRequest || modelSelect?.value || modelInput?.value || '';
                        let cloth = clothRequest || clothInput?.value || '';
                        const control = controlInput.checked;
                        const discount = parseFloat(discountInput?.value) || 0;

                        if (!width || !height) {
                            rebuildPrice(0, quantity, discount); // Здесь вместо 0 будет "Цена по запросу"
                            return;
                        }

                        // Опционально: индикатор загрузки, чтобы не показывало старое значение
                        priceElement.textContent = 'Расчёт...';

                        const requestId = ++currentPriceRequest;
                        const params = new URLSearchParams({
                            width,
                            height,
                            model: model || '',
                            control,
                            cloth: cloth || '',
                            modelId: modelId || '',
                            prodTitle: useSantehPriceMatrix ? `Сантехнические роллеты ${prodTitleTorequest}` : prodTitleTorequest,
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
                            .catch(error => {
                                console.error('Ошибка при получении цены:', error);
                                rebuildPrice(0, quantity,
                                discount); // Здесь тоже "Цена по запросу" в случае ошибки
                            });
                    }

                    // Инициализация количества
                    counterInput.value = counterInput.value || 1;

                    // Инициализация UI fallback (на случай, если fetch не сработает сразу)
                    priceElement.textContent = 'Цена по запросу';

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
                    if (useSantehPriceMatrix) {
                        fetchPrice();
                    } else {
                        rebuildPrice(currentBasePrice, parseInt(counterInput.value) || 1, parseFloat(discountInput?.value) || 0);
                    }

                    // Обновление цены при изменении ширины, высоты или других параметров
                    widthInput.addEventListener('input', fetchPrice);
                    heightInput.addEventListener('input', fetchPrice);
                    if (controlInput && controlInput instanceof Element) {
                        controlInput.addEventListener('input', fetchPrice);
                    }

                    const optionInputs = slide.querySelectorAll(
                        'input[name="installation-type"], input[name="control-type"], input[name="lock-device"], input[name="ral-paint"], input[name="photo-print"], input[name="popup-installation-type"], input[name="popup-control-type"], input[name="popup-lock-device"], input[name="popup-ral-paint"], input[name="popup-photo-print"]'
                    );
                    optionInputs.forEach((input) => {
                        input.addEventListener('change', fetchPrice);
                    });
                });
            }

            getPrice(slides);




            loadPopupsContent()


            function renderStaticCardPrice(product) {
                const minPrice = Number(product.min_price) || 0;
                const discount = Number(product.discount) || 0;

                if (minPrice <= 0) {
                    return '<span class="discount">Цена по запросу</span>';
                }

                if (discount > 0) {
                    const discountedPrice = Math.floor(minPrice * (1 - discount / 100));

                    return `
                            <span class="normalPrice" style="text-decoration: line-through;">${minPrice}₽</span>
                            <span class="discount">${discountedPrice}₽</span>
                    `;
                }

                return `<span class="discount">${minPrice}₽</span>`;
            }

            function buildCardMinDimensions(product) {
                const minWidth = parseInt(product.min_width, 10) || 0;
                const minHeight = parseInt(product.min_height, 10) || 0;

                if (!minWidth && !minHeight) {
                    return '';
                }

                const widthText = minWidth ? `${minWidth} мм` : '';
                const heightText = minHeight ? `${minHeight} мм` : '';
                const separator = widthText && heightText ? ' x ' : '';

                return `<div class="bigProdCard__meta">От ${widthText}${separator}${heightText}</div>`;
            }


// Пагинация


            function fetchProducts(url) {
                fetch(url, {
                        headers: {
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    })
                    .then(response => response.json()) // Получаем данные в формате JSON
                    .then(data => {
                        // Обновляем контент продуктов
                        document.getElementById("productsWrap").innerHTML = data.filterProduts;
                        // Обновляем пагинацию
                        document.getElementById("pagination").innerHTML = data.pagination;
                    })
                    .catch(error => console.error('Ошибка:', error)); // Обработка ошибок
            }

            document.body.addEventListener("click", function(e) {
                let pageLink = e.target.closest("#pagination a");
                if (pageLink) {
                    e.preventDefault(); // Отменяем стандартный переход
                    let pageUrl = new URL(pageLink.href); // Получаем URL из ссылки
                    let pageNumber = pageUrl.searchParams.get("page"); // Берем номер страницы
                    fetchFilteredProducts(pageNumber);
                    loadPopupsContent()
                }
            });

            document.querySelectorAll('.sidebarFilter__label input[type="checkbox"]').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    fetchFilteredProducts(1); // При изменении фильтра загружаем первую страницу
                    loadPopupsContent()
                });
            });

            function fetchFilteredProducts(page) {
                let selectedModels = Array.from(document.querySelectorAll(
                        '.modelLabel input[type="checkbox"]:checked'))
                    .map(el => el.id.replace('modelid', ''));
                let selectedColors = Array.from(document.querySelectorAll('input[name="color[]"]:checked'))
                    .map(el => el.value);
                let selectedMaterials = Array.from(document.querySelectorAll('input[name="material[]"]:checked'))
                    .map(el => el.value);

                const priceFilterPayload = getPriceFilterPayload();

                fetch('/filter-subcat-products/{{ $subcategory->id }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            models: selectedModels,
                            colors: selectedColors,
                            materials: selectedMaterials,
                            page: page, // Передаем страницу в запрос
                        price_filter_active: priceFilterPayload.active,
                        min_price: priceFilterPayload.min,
                        max_price: priceFilterPayload.max,
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        let productsContainer = document.querySelector('.popularsWithFilter__cards');
                        productsContainer.innerHTML = data.products.map(product => `
            <div class="bigProdCard card" id="prod${product.id}" data-modelId="${product.modelid}" data-model="${product.model}" data-cloth="${product.cloth}" data-discount="">
                <div class="bigProdCard__wrap">
                    <div class="bigProdCard__img-wrap">
                        <div class="bigProdCard__imgCustomWrap">
                            ${product.image_path ? `<img src="/${product.image_path}" alt="${product.h1}" />` : ''}
                            ${product.fabric_photo ? `<img src="${product.fabric_photo}" alt="${product.h1}" />` : ''}
                        </div>
                        <div class="bigProdCard__controls">
                            <div class="bigProdCard__cart control"><i class="fas fa-cart-arrow-down"></i>
                                <div class="bigProdCard__toolTip">В корзину</div>
                            </div>
                            <div class="bigProdCard__quckView control quickProd" data-modal="#popupProd" data-prod="${product.id}"><i class="fas fa-eye"></i>
                                <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                            </div>
                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                <div class="bigProdCard__toolTip">Добавить в избранное</div>
                            </div>
                        </div>
                    </div>
                    <div class="bigProdCard__info">
                        <a class="bigProdCard__category" href="${product.category ? '/' + product.category.slug : '#'}">${product.category ? product.category.titleh1 : 'Без категории'}</a>
                        <a class="bigProdCard__title" href="${product.slug ? '/' + product.category.slug + '/' + product.subcategory.slug + '/' + product.slug : '#'}">${product.h1}</a>
                        ${buildCardMinDimensions(product)}
                        <div class="bigProdCard__priceWrap">
                            ${renderStaticCardPrice(product)}
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

                        // Обновляем пагинацию
                        document.querySelector('.pagination').innerHTML = data.pagination;
                        loadPopupsContent()
                    });
            }



            function getPriceFilterPayload() {
                const activeInput = document.getElementById('price-filter-active');
                const minInput = document.getElementById('min-price');
                const maxInput = document.getElementById('max-price');

                return {
                    active: activeInput ? activeInput.value === '1' : false,
                    min: minInput ? Number(minInput.value) || 0 : null,
                    max: maxInput ? Number(maxInput.value) || null : null,
                };
            }

            function initPricefilter() {
                const filter = document.querySelector('[data-price-filter]');
                if (!filter) {
                    return;
                }

                const slider = filter.querySelector('.custom-range-slider');
                if (!slider) {
                    return;
                }

                const range = slider.querySelector('.range');
                const leftThumb = slider.querySelector('.left-thumb');
                const rightThumb = slider.querySelector('.right-thumb');
                const minPriceDisplay = document.getElementById('min-price-display');
                const maxPriceDisplay = document.getElementById('max-price-display');
                const minPriceInput = document.getElementById('min-price');
                const maxPriceInput = document.getElementById('max-price');
                const activeInput = document.getElementById('price-filter-active');

                if (!range || !leftThumb || !rightThumb || !minPriceInput || !maxPriceInput) {
                    return;
                }

                const min = Number(filter.dataset.defaultMin) || 0;
                const max = Number(filter.dataset.defaultMax) || 0;
                if (max <= min) {
                    return;
                }

                let currentMin = Number(minPriceInput.value) || min;
                let currentMax = Number(maxPriceInput.value) || max;
                let requestTimer = null;

                function formatPrice(value) {
                    return Number(value).toLocaleString('ru-RU');
                }

                function updateThumbPosition(thumb, value) {
                    const percent = ((value - min) / (max - min)) * 100;
                    thumb.style.left = `${Math.min(Math.max(percent, 0), 100)}%`;
                }

                function updateRange() {
                    const minPercent = ((currentMin - min) / (max - min)) * 100;
                    const maxPercent = ((currentMax - min) / (max - min)) * 100;
                    range.style.left = `${Math.min(Math.max(minPercent, 0), 100)}%`;
                    range.style.width = `${Math.max(maxPercent - minPercent, 0)}%`;
                }

                function requestFilteredProducts() {
                    if (activeInput) {
                        activeInput.value = '1';
                    }

                    clearTimeout(requestTimer);
                    requestTimer = setTimeout(() => fetchFilteredProducts(1), 250);
                }

                function moveThumb(thumb, event) {
                    const rect = slider.getBoundingClientRect();
                    const pointer = event.touches ? event.touches[0] : event;
                    const offsetX = pointer.clientX - rect.left;
                    const percent = Math.min(Math.max((offsetX / rect.width) * 100, 0), 100);
                    const value = Math.round(min + ((max - min) * percent) / 100);

                    if (thumb === leftThumb && value < currentMax) {
                        currentMin = value;
                        minPriceInput.value = value;
                        if (minPriceDisplay) {
                            minPriceDisplay.textContent = formatPrice(value);
                        }
                    } else if (thumb === rightThumb && value > currentMin) {
                        currentMax = value;
                        maxPriceInput.value = value;
                        if (maxPriceDisplay) {
                            maxPriceDisplay.textContent = formatPrice(value);
                        }
                    }

                    updateThumbPosition(leftThumb, currentMin);
                    updateThumbPosition(rightThumb, currentMax);
                    updateRange();
                    requestFilteredProducts();
                }

                [leftThumb, rightThumb].forEach((thumb) => {
                    thumb.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        const moveHandler = (event) => moveThumb(thumb, event);
                        document.addEventListener('mousemove', moveHandler);
                        document.addEventListener('mouseup', () => {
                            document.removeEventListener('mousemove', moveHandler);
                        }, { once: true });
                    });

                    thumb.addEventListener('touchstart', (e) => {
                        const moveHandler = (event) => moveThumb(thumb, event);
                        document.addEventListener('touchmove', moveHandler);
                        document.addEventListener('touchend', () => {
                            document.removeEventListener('touchmove', moveHandler);
                        }, { once: true });
                    });
                });

                updateThumbPosition(leftThumb, currentMin);
                updateThumbPosition(rightThumb, currentMax);
                updateRange();
            }

            initPricefilter()

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
                    const installationType = formWrapper.querySelector('input[name^="widhType"]:checked')?.value || 'inside';
                    const lockType = formWrapper.querySelector('input[name="lock-type"]:checked')?.value || 'sliders';
                    const lockPrice = parseInt(formWrapper.querySelector('input[name="lock-type"]:checked')?.dataset.price || 0);
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
                                installationType,
                                lockType,
                                lockPrice,
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/fslightbox/3.4.2/index.min.js"></script>


</body>
</html>
