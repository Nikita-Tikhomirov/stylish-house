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

        <!-- Р СџР ВµРЎР‚Р Р†РЎвЂ№Р в„– РЎРЊР С”РЎР‚Р В°Р Р… -->
        <section class="s-catMain wrapper">
            <div class="s-catMain__img"><img src="{{ Storage::url($subcategory->img) }}" alt="" /></div>
            <div class="breadcrumbs">
                <ul class="breadcrumbs__list">
                    <li class=""><a class="breadcrumbs__link" href="{{ route('front.home') }}">Р вЂњР В»Р В°Р Р†Р Р…Р В°РЎРЏ</a></li>

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

        <!-- Р вЂ™РЎвЂ№Р Р†Р С•Р Т‘ РЎвЂљР С•Р Р†Р В°РЎР‚Р С•Р Р† Р С”Р В°Р С” Р Р†Р ВµР В·Р Т‘Р Вµ -->

            <section class="popularsWithFilter wrapper">
                <h2 class="popularsWithFilter__title title"> <span>Р СџР С•Р С—РЎС“Р В»РЎРЏРЎР‚Р Р…РЎвЂ№Р Вµ РЎвЂљР С•Р Р†Р В°РЎР‚РЎвЂ№</span><svg width="114"
                        height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                            stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                    </svg></h2>
                <div class="popularsWithFilter__wrap">
                    <aside class="sidebarFilter">


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

        <!-- Р вЂ™Р С‘Р Т‘РЎвЂ№ Р СР С•Р Р…РЎвЂљР В°Р В¶Р В° -->
        @if (!empty($installationTypes) && $installationTypes->isNotEmpty())
            <x-front.section.subcategory-installation-types :installationTypes="$installationTypes" />
        @else
            <x-front.section.rollets-installation />
        @endif

        <!-- Р С™Р В°Р В»РЎРЉР С”РЎС“Р В»РЎРЏРЎвЂљР С•РЎР‚ -->
        @if (!empty($firstProduct))
            @php
                $product = $firstProduct;
            @endphp
            <section class="prodMain wrapper catCalculator" style="padding-top: 40px;">
                <h2 class="prodMain__title title"> <span>Р В Р В°РЎРѓРЎРѓРЎвЂЎР С‘РЎвЂљР В°РЎвЂљРЎРЉ РЎРѓРЎвЂљР С•Р С‘Р СР С•РЎРѓРЎвЂљРЎРЉ РЎР‚Р С•Р В»РЎРЉРЎРѓРЎвЂљР В°Р Р†Р ВµР Р…</span><svg width="114" height="35"
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
                        <div class="prodForm__price">Цена: 1200₽</div>
                        <button class="prodForm__addToCart" data-id="{{ $product->id }}"> Добавить в
                            корзину </button>
                    </div>
                </div>
                </div>
            </section>
        @endif

        @if (!empty($workExamples) && $workExamples->isNotEmpty())
            <x-front.section.subgallery :gallerys="$workExamples" :category='$category' title=""></x-front.section.gallery>
        @endif

        <!-- Р С›Р С—Р В»Р В°РЎвЂљР В° Р С‘ Р Т‘Р С•РЎРѓРЎвЂљР В°Р Р†Р С”Р В° -->
        <x-front.section.delivery :title="$homePageFields->section_delivery_title" :topText="$homePageFields->section_delivery_top_text" :bottomText="$homePageFields->section_delivery_bottom_text"
            :iconCards="$iconCards"></x-front.section.delivery>

        <!-- Р вЂ™Р С›Р СџР В Р С›Р РЋР В« Р В Р С›Р СћР вЂ™Р вЂўР СћР В« -->
        @if ($subcategory->faq_html)
            <section class="s-faq wrapper">
                <div class="s-faq__container">
                    <div class="s-faq__title-wrap">
                        <h2 class="s-faq__title title"> <span>Р вЂ™Р С•Р С—РЎР‚Р С•РЎРѓРЎвЂ№ Р С‘ Р С•РЎвЂљР Р†Р ВµРЎвЂљРЎвЂ№</span><svg width="114" height="35"
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
            <x-front.section.faqcat title="Р вЂ™Р С•Р С—РЎР‚Р С•РЎРѓРЎвЂ№ Р С‘ Р С•РЎвЂљР Р†Р ВµРЎвЂљРЎвЂ№" :faqs="$faqs"></x-front.section.faqcat>
        @endif

        <!-- Р РЋР вЂўР С› Р СћР вЂўР С™Р РЋР Сћ -->
        <x-front.section.seo :seoSection="$subcategory->seo"></x-front.section.seo>

        <!-- Р вЂ™РЎРѓР Вµ Р С”Р В°РЎвЂљР ВµР С–Р С•РЎР‚Р С‘Р С‘ -->
        <section class="s-tags wrapper">
            <h2 class="s-tags__title title"> <span>Р вЂ™РЎРѓР Вµ Р С”Р В°РЎвЂљР ВµР С–Р С•РЎР‚Р С‘Р С‘</span><svg width="114" height="35"
                    viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                        stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                </svg></h2>
            <div class="s-tags__tags">
                <div class="accardionJs">
                    <div class="accardion__title">Р вЂ™РЎРѓР Вµ Р С”Р В°РЎвЂљР ВµР С–Р С•РЎР‚Р С‘Р С‘</div>
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

                }; // Р вЂўРЎРѓР В»Р С‘ РЎС“Р В¶Р Вµ Р ВµРЎРѓРЎвЂљРЎРЉ Р СР С•Р Т‘Р В°Р В»Р С”Р В°, Р Р†РЎвЂ№РЎвЂ¦Р С•Р Т‘Р С‘Р С


            }



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

                        console.log('Р вЂ”Р В°Р С—РЎР‚Р С•РЎРѓ Р С” /popup РЎРѓ prodId:', prodId);

                        // Р СџР С•Р В»РЎС“РЎвЂЎР В°Р ВµР С Р Т‘Р В°Р Р…Р Р…РЎвЂ№Р Вµ Р С• РЎвЂљР С•Р Р†Р В°РЎР‚Р Вµ РЎРѓ РЎРѓР ВµРЎР‚Р Р†Р ВµРЎР‚Р В°
                        fetch(`/popup/${prodId}`)
                            .then(response => response.json())
                            .then(product => {
                                // Р вЂ”Р В°Р С—Р С•Р В»Р Р…РЎРЏР ВµР С Р С—Р С•Р С—Р В°Р С— Р Т‘Р В°Р Р…Р Р…РЎвЂ№Р СР С‘ РЎвЂљР С•Р Р†Р В°РЎР‚Р В°
                                document.querySelector('#popupProd .prodForm__formSubtitle')
                                    .innerText = product.title;
                                document.querySelector('#popupProd .prodForm__formTitle')
                                    .innerText = `Р вЂ”Р В°Р С”Р В°Р В·Р В°РЎвЂљРЎРЉ ${product.title}`;
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
                                // Р С™Р С•РЎР‚РЎР‚Р ВµР С”РЎвЂљР С‘РЎР‚РЎС“Р ВµР С Р С—РЎС“РЎвЂљРЎРЉ


                                // console.log(product.gallery);
                                // Р С›РЎвЂЎР С‘РЎвЂ°Р В°Р ВµР С РЎРѓРЎвЂљР В°РЎР‚РЎС“РЎР‹ Р С–Р В°Р В»Р ВµРЎР‚Р ВµРЎР‹
                                let gallery = document.querySelector(
                                    '#popupProd .prodForm__bar');
                                gallery.innerHTML = '';

                                // Р вЂќР С•Р В±Р В°Р Р†Р В»РЎРЏР ВµР С Р С‘Р В·Р С•Р В±РЎР‚Р В°Р В¶Р ВµР Р…Р С‘РЎРЏ РЎРѓ РЎРѓРЎРѓРЎвЂ№Р В»Р С”Р В°Р СР С‘
                                product.gallery.forEach(related => {
                                    let link = document.createElement('a');
                                    link.href = related.link; // Р РЋРЎРѓРЎвЂ№Р В»Р С”Р В° Р Р…Р В° РЎвЂљР С•Р Р†Р В°РЎР‚
                                    let img = document.createElement('img');
                                    if (related.image) {

                                        img.src =
                                            `${related.image}`; // Р СџРЎС“РЎвЂљРЎРЉ Р С” Р С‘Р В·Р С•Р В±РЎР‚Р В°Р В¶Р ВµР Р…Р С‘РЎР‹
                                    } else {

                                        img.src =
                                            `${related.fabric_photo}`; // Р СџРЎС“РЎвЂљРЎРЉ Р С” Р С‘Р В·Р С•Р В±РЎР‚Р В°Р В¶Р ВµР Р…Р С‘РЎР‹
                                    }


                                    link.appendChild(
                                        img); // Р вЂ™РЎРѓРЎвЂљР В°Р Р†Р В»РЎРЏР ВµР С Р С‘Р В·Р С•Р В±РЎР‚Р В°Р В¶Р ВµР Р…Р С‘Р Вµ Р Р† РЎРѓРЎРѓРЎвЂ№Р В»Р С”РЎС“
                                    gallery.appendChild(
                                        link); // Р вЂќР С•Р В±Р В°Р Р†Р В»РЎРЏР ВµР С РЎРѓРЎРѓРЎвЂ№Р В»Р С”РЎС“ Р Р† Р С–Р В°Р В»Р ВµРЎР‚Р ВµРЎР‹

                                });

                                console.log(product.model);

                                // Р вЂќР С•Р В±Р В°Р Р†Р С‘РЎвЂљРЎРЉ id Р Т‘Р В»РЎРЏ Р С”Р Р…Р С•Р С—Р С”Р С‘ Р Т‘Р С•Р В±Р В°Р Р†Р С‘РЎвЂљРЎРЉ Р Р† Р С”Р С•РЎР‚Р В·Р С‘Р Р…РЎС“
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
                                console.error('Р С›РЎв‚¬Р С‘Р В±Р С”Р В° Р С—РЎР‚Р С‘ Р В·Р В°Р С–РЎР‚РЎС“Р В·Р С”Р Вµ Р Т‘Р В°Р Р…Р Р…РЎвЂ№РЎвЂ¦ РЎвЂљР С•Р Р†Р В°РЎР‚Р В°:', error);
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
                    const modelId = medelId;
                    const prodTitleTorequest = slide.querySelector('.prodForm__formTitle').innerText
                    console.log(prodTitleTorequest);

                    const controlInput = slide.querySelector('.control') || {
                        checked: false
                    };
                    const clothInput = slide.querySelector('.cloth');
                    const discountInput = slide.querySelector('.discount');

                    let counterMinusBtn = slide.querySelector('.minus');
                    let counterPlusBtn = slide.querySelector('.plus');
                    let counterInput = slide.querySelector('.quantity-input');
                    // Р Р€Р Т‘Р В°Р В»РЎРЏР ВµР С РЎРѓРЎвЂљР В°РЎР‚РЎвЂ№Р Вµ Р С•Р В±РЎР‚Р В°Р В±Р С•РЎвЂљРЎвЂЎР С‘Р С”Р С‘ Р С—Р ВµРЎР‚Р ВµР Т‘ Р Т‘Р С•Р В±Р В°Р Р†Р В»Р ВµР Р…Р С‘Р ВµР С Р Р…Р С•Р Р†РЎвЂ№РЎвЂ¦
                    function removeEventListeners(element, events) {
                        const clone = element.cloneNode(true);
                        element.replaceWith(clone);
                        return clone;
                    }
                    // Р С›РЎвЂЎР С‘РЎвЂ°Р В°Р ВµР С Р С•Р В±РЎР‚Р В°Р В±Р С•РЎвЂљРЎвЂЎР С‘Р С”Р С‘ Р С—Р ВµРЎР‚Р ВµР Т‘ Р Т‘Р С•Р В±Р В°Р Р†Р В»Р ВµР Р…Р С‘Р ВµР С
                    counterMinusBtn = removeEventListeners(counterMinusBtn, ['click']);
                    counterPlusBtn = removeEventListeners(counterPlusBtn, ['click']);
                    counterInput = removeEventListeners(counterInput, ['input']);
                    let priceNow = 0;

                    // Р СџР ВµРЎР‚Р ВµРЎРѓРЎвЂЎР ВµРЎвЂљ РЎвЂ Р ВµР Р…РЎвЂ№ РЎРѓ РЎС“РЎвЂЎР ВµРЎвЂљР С•Р С Р С”Р С•Р В»Р С‘РЎвЂЎР ВµРЎРѓРЎвЂљР Р†Р В° Р С‘ РЎРѓР С”Р С‘Р Т‘Р С”Р С‘
                    function rebuildPrice(price, counterValue, discount = 0) {
                        if (price <= 0 || isNaN(price)) {
                            priceElement.textContent = 'Р В¦Р ВµР Р…Р В° Р С—Р С• Р В·Р В°Р С—РЎР‚Р С•РЎРѓРЎС“';
                            return;
                        }
                        const discountedPrice = price - (price * discount / 100);
                        let priceNow = counterValue * discountedPrice;

                        const form = slide.closest('.prodForm');

                        const installationType = form.querySelector('input[name="installation-type"]:checked');
                        if (installationType) {
                            priceNow += (parseInt(installationType.dataset.price || 0, 10) * counterValue);
                        }

                        const controlType = form.querySelector('input[name="control-type"]:checked');
                        if (controlType) {
                            priceNow += (parseInt(controlType.dataset.price || 0, 10) * counterValue);
                        }

                        const lockDevice = form.querySelector('input[name="lock-device"]:checked');
                        if (lockDevice) {
                            priceNow += (parseInt(lockDevice.dataset.price || 0, 10) * counterValue);
                        }

                        const ralPaint = form.querySelector('input[name="ral-paint"]:checked');
                        if (ralPaint) {
                            priceNow += (parseInt(ralPaint.dataset.price || 0, 10) * counterValue);
                        }

                        const photoPrint = form.querySelector('input[name="photo-print"]:checked');
                        if (photoPrint) {
                            priceNow += (parseInt(photoPrint.dataset.price || 0, 10) * counterValue);
                        }

                        // Р СџРЎР‚Р ВµР С•Р В±РЎР‚Р В°Р В·РЎС“Р ВµР С РЎвЂ Р ВµР Р…РЎС“ Р Р† РЎвЂ Р ВµР В»Р С•Р Вµ РЎвЂЎР С‘РЎРѓР В»Р С•
                        priceNow = Math.floor(priceNow);
                        priceElement.textContent = `Р В¦Р ВµР Р…Р В°: ${priceNow}РІвЂљР…`;
                    }

                    // Р В¤РЎС“Р Р…Р С”РЎвЂ Р С‘РЎРЏ Р Т‘Р В»РЎРЏ Р С—Р С•Р В»РЎС“РЎвЂЎР ВµР Р…Р С‘РЎРЏ Р С‘ Р С•Р В±Р Р…Р С•Р Р†Р В»Р ВµР Р…Р С‘РЎРЏ РЎвЂ Р ВµР Р…РЎвЂ№
                    function fetchPrice() {
                        const width = widthInput.value;
                        const height = heightInput.value;
                        // console.log(width);

                        const quantity = parseInt(counterInput.value) || 1;

                        let model = modelFromRequest || modelSelect.value;
                        let cloth = clothRequest || clothInput.value;
                        const control = controlInput.checked;
                        const discount = parseFloat(discountInput?.value) || 0;

                        if (!width || !height) {
                            rebuildPrice(0, quantity, discount); // Р вЂ”Р Т‘Р ВµРЎРѓРЎРЉ Р Р†Р СР ВµРЎРѓРЎвЂљР С• 0 Р В±РЎС“Р Т‘Р ВµРЎвЂљ "Р В¦Р ВµР Р…Р В° Р С—Р С• Р В·Р В°Р С—РЎР‚Р С•РЎРѓРЎС“"
                            return;
                        }

                        // Р С›Р С—РЎвЂ Р С‘Р С•Р Р…Р В°Р В»РЎРЉР Р…Р С•: Р С‘Р Р…Р Т‘Р С‘Р С”Р В°РЎвЂљР С•РЎР‚ Р В·Р В°Р С–РЎР‚РЎС“Р В·Р С”Р С‘, РЎвЂЎРЎвЂљР С•Р В±РЎвЂ№ Р Р…Р Вµ Р С—Р С•Р С”Р В°Р В·РЎвЂ№Р Р†Р В°Р В»Р С• РЎРѓРЎвЂљР В°РЎР‚Р С•Р Вµ Р В·Р Р…Р В°РЎвЂЎР ВµР Р…Р С‘Р Вµ
                        priceElement.textContent = 'Р В Р В°РЎРѓРЎвЂЎРЎвЂРЎвЂљ...';

                        fetch(
                                `/sheet-names?width=${width}&height=${height}&model=${model}&control=${control}&cloth=${cloth}&modelId=${modelId}&prodTitle=${prodTitleTorequest}`
                            )
                            .then(response => response.json())
                            .then(data => {
                                const basePrice = data.price || 0;
                                rebuildPrice(basePrice, quantity, discount);
                            })
                            .catch(error => {
                                console.error('Р С›РЎв‚¬Р С‘Р В±Р С”Р В° Р С—РЎР‚Р С‘ Р С—Р С•Р В»РЎС“РЎвЂЎР ВµР Р…Р С‘Р С‘ РЎвЂ Р ВµР Р…РЎвЂ№:', error);
                                rebuildPrice(0, quantity,
                                discount); // Р вЂ”Р Т‘Р ВµРЎРѓРЎРЉ РЎвЂљР С•Р В¶Р Вµ "Р В¦Р ВµР Р…Р В° Р С—Р С• Р В·Р В°Р С—РЎР‚Р С•РЎРѓРЎС“" Р Р† РЎРѓР В»РЎС“РЎвЂЎР В°Р Вµ Р С•РЎв‚¬Р С‘Р В±Р С”Р С‘
                            });
                    }

                    // Р ВР Р…Р С‘РЎвЂ Р С‘Р В°Р В»Р С‘Р В·Р В°РЎвЂ Р С‘РЎРЏ Р С”Р С•Р В»Р С‘РЎвЂЎР ВµРЎРѓРЎвЂљР Р†Р В°
                    counterInput.value = counterInput.value || 1;

                    // Р ВР Р…Р С‘РЎвЂ Р С‘Р В°Р В»Р С‘Р В·Р В°РЎвЂ Р С‘РЎРЏ UI fallback (Р Р…Р В° РЎРѓР В»РЎС“РЎвЂЎР В°Р в„–, Р ВµРЎРѓР В»Р С‘ fetch Р Р…Р Вµ РЎРѓРЎР‚Р В°Р В±Р С•РЎвЂљР В°Р ВµРЎвЂљ РЎРѓРЎР‚Р В°Р В·РЎС“)
                    priceElement.textContent = 'Р В¦Р ВµР Р…Р В° Р С—Р С• Р В·Р В°Р С—РЎР‚Р С•РЎРѓРЎС“';

                    // Р С›Р В±РЎР‚Р В°Р В±Р С•РЎвЂљРЎвЂЎР С‘Р С”Р С‘ Р Т‘Р В»РЎРЏ Р С‘Р В·Р СР ВµР Р…Р ВµР Р…Р С‘РЎРЏ Р С”Р С•Р В»Р С‘РЎвЂЎР ВµРЎРѓРЎвЂљР Р†Р В° РЎвЂљР С•Р Р†Р В°РЎР‚Р С•Р Р†
                    counterMinusBtn.addEventListener('click', () => {
                        let currentValue = parseInt(counterInput.value) || 1;
                        if (currentValue > 1) {
                            counterInput.value = currentValue - 1;
                            fetchPrice();
                        }
                    });

                    counterPlusBtn.addEventListener('click', () => {
                        let currentValue = parseInt(counterInput.value) || 1;
                        counterInput.value = currentValue + 1;
                        fetchPrice();
                    });

                    // Р вЂќР В»РЎРЏ Р Р†Р Р†Р С•Р Т‘Р В° Р Р†РЎР‚РЎС“РЎвЂЎР Р…РЎС“РЎР‹
                    counterInput.addEventListener('input', () => {
                        let value = parseInt(counterInput.value);
                        if (isNaN(value) || value < 1) {
                            counterInput.value = 1;
                        }
                        fetchPrice();
                    });

                    // Р ВР В·Р Р…Р В°РЎвЂЎР В°Р В»РЎРЉР Р…РЎвЂ№Р в„– РЎР‚Р В°РЎРѓРЎвЂЎР ВµРЎвЂљ Р С—РЎР‚Р С‘ Р В·Р В°Р С–РЎР‚РЎС“Р В·Р С”Р Вµ
                    fetchPrice();

                    // Р С›Р В±Р Р…Р С•Р Р†Р В»Р ВµР Р…Р С‘Р Вµ РЎвЂ Р ВµР Р…РЎвЂ№ Р С—РЎР‚Р С‘ Р С‘Р В·Р СР ВµР Р…Р ВµР Р…Р С‘Р С‘ РЎв‚¬Р С‘РЎР‚Р С‘Р Р…РЎвЂ№, Р Р†РЎвЂ№РЎРѓР С•РЎвЂљРЎвЂ№ Р С‘Р В»Р С‘ Р Т‘РЎР‚РЎС“Р С–Р С‘РЎвЂ¦ Р С—Р В°РЎР‚Р В°Р СР ВµРЎвЂљРЎР‚Р С•Р Р†
                    widthInput.addEventListener('input', fetchPrice);
                    heightInput.addEventListener('input', fetchPrice);
                    if (controlInput && controlInput instanceof Element) {
                        controlInput.addEventListener('input', fetchPrice);
                    }

                    const optionInputs = slide.querySelectorAll(
                        'input[name="installation-type"], input[name="control-type"], input[name="lock-device"], input[name="ral-paint"], input[name="photo-print"]'
                    );
                    optionInputs.forEach((input) => {
                        input.addEventListener('change', fetchPrice);
                    });
                });
            }

            getPrice(slides);




            loadPopupsContent()

            function rebuilCardsPrice(params) {
                let allCards = document.querySelectorAll('.card')

                allCards.forEach(element => {

                    // Р СљР С‘Р Р…Р СР В°Р В»РЎРЉР Р…РЎС“РЎР‹ Р С‘ Р СР В°Р С”РЎРѓР С‘Р СР В°Р В»РЎРЉР Р…РЎС“РЎР‹ Р В±РЎР‚Р В°РЎвЂљРЎРЉ Р С‘Р В· Р СР С•Р Т‘Р ВµР В»Р С‘

                    let prodTitle = element.querySelector('.bigProdCard__title').innerText.trim();

                    let width, height;

                    let counterForDouble = 1

                    if (prodTitle.includes("Р РЋРЎвЂљР В°Р Р…Р Т‘Р В°РЎР‚РЎвЂљ")) {
                        width = 500;
                        height = 500;
                    } else if (prodTitle.includes("Р РЋР С—РЎР‚Р С‘Р Р…Р С–")) {
                        width = 700;
                        height = 500;
                    } else if (prodTitle.includes("Р вЂњРЎР‚Р В°Р Р…Р Т‘")) {
                        width = 700;
                        height = 500;
                    } else if (prodTitle.includes("Р С™Р Р†Р В°РЎвЂљРЎР‚Р С• Р С”Р В»Р В°РЎРѓРЎРѓР С‘Р С”")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р С™Р Р†Р В°РЎвЂљРЎР‚Р С• Р В»РЎР‹Р С”РЎРѓ")) {
                        width = 700;
                        height = 500;
                    } else if (prodTitle.includes("Р С™Р В»Р В°РЎРѓРЎРѓР С‘Р С” Р С—РЎР‚Р ВµР СР С‘РЎС“Р С")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р вЂќР В°Р В±Р В» Р С”Р В»Р В°РЎРѓРЎРѓР С‘Р С”")) {
                        width = 400;
                        height = 500;
                        counterForDouble = 2
                    } else if (prodTitle.includes("Р вЂєРЎР‹Р С”РЎРѓ Р С—РЎР‚Р ВµР СР С‘РЎС“Р С")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р вЂќР В°Р В±Р В» Р В»РЎР‹Р С”РЎРѓ")) {
                        width = 400;
                        height = 500;
                        counterForDouble = 2
                    } else if (prodTitle.includes("Р СљР С‘Р Р…Р С‘")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р СљР С‘Р Р…Р С‘ Р Р…РЎРЉРЎР‹")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р Р€Р Р…Р С‘-1")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р Р€Р Р…Р С‘-2")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р Р€Р Р…Р С‘-1 Р В»Р В°Р СР С‘Р Р…Р В°РЎвЂ Р С‘РЎРЏ")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р Р€Р Р…Р С‘-2 Р В»Р В°Р СР С‘Р Р…Р В°РЎвЂ Р С‘РЎРЏ")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р С™Р С•Р СР В±Р С• Р СР С‘Р Р…Р С‘ Р Р…РЎРЉРЎР‹")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р С™Р С•Р СР В±Р С• РЎС“Р Р…Р С‘-1")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р С™Р С•Р СР В±Р С• РЎС“Р Р…Р С‘-2")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р С™Р С•Р СР В±Р С• РЎС“Р Р…Р С‘-2 Р В»Р В°Р СР С‘Р Р…Р В°РЎвЂ Р С‘РЎРЏ")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р С™Р С•Р СР В±Р С• Р Р†-52 РЎРѓРЎвЂљР В°Р Р…Р Т‘Р В°РЎР‚РЎвЂљ")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р С™Р С•Р СР В±Р С• Р С™Р В»Р В°РЎРѓРЎРѓР С‘Р С”")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р С™Р С•Р СР В±Р С• Р Р†-52 Р В»РЎР‹Р С”РЎРѓ")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р С™Р С•Р СР В±Р С• Р Т‘Р В°Р В±Р В» Р С”Р В»Р В°РЎРѓРЎРѓР С‘Р С”")) {
                        width = 400;
                        height = 500;
                        counterForDouble = 2
                    } else if (prodTitle.includes("Р С™Р С•Р СР В±Р С• Р Т‘Р В°Р В±Р В» Р В»РЎР‹Р С”РЎРѓ")) {
                        width = 400;
                        height = 500;
                        counterForDouble = 2
                    } else if (prodTitle.includes("Р С™Р С•Р СР В±Р С• Р С”Р Р†Р В°РЎвЂљРЎР‚Р С• Р С”Р В»Р В°РЎРѓРЎРѓР С‘Р С”")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р С™Р С•Р СР В±Р С• Р С”Р Р†Р В°РЎвЂљРЎР‚Р С• Р В»РЎР‹Р С”РЎРѓ")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р С’Р В»РЎР‹Р СР С‘Р Р…Р С‘Р ВµР Р†РЎвЂ№Р Вµ 50 Р СР С")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р С™Р С•Р СР С—Р В°Р С”РЎвЂљ Р СџРЎР‚Р ВµР СР С‘РЎС“Р С")) {
                        width = 300;
                        height = 600;
                    } else if (prodTitle.includes("Р ТђL Р С’Р В±РЎРѓР С•Р В»РЎР‹РЎвЂљ")) {
                        width = 300;
                        height = 600;
                    } else {
                        width = 700;
                        height = 700; // Р вЂ”Р Р…Р В°РЎвЂЎР ВµР Р…Р С‘Р Вµ Р С—Р С• РЎС“Р СР С•Р В»РЎвЂЎР В°Р Р…Р С‘РЎР‹
                    }


                    let model = element.getAttribute('data-model');
                    let control = false;
                    let cloth = element.getAttribute('data-cloth');
                    let priceElement = element.querySelector('.discount');
                    let normalPriceElement = element.querySelector('.normalPrice');
                    let modelId = element.dataset.modelid;

                    // console.log(prodTitle);



                    fetch(
                            `/sheet-names?width=${width}&height=${height}&model=${model}&control=${control}&cloth=${cloth}&modelId=${modelId}&prodTitle=${prodTitle}`
                        )
                        .then(response => response.json())
                        .then(data => {
                            const basePrice = data.price * counterForDouble || "Р В¦Р ВµР Р…Р В° Р С—Р С• Р В·Р В°Р С—РЎР‚Р С•РЎРѓРЎС“ ";
                            const discount = element.getAttribute('data-discount')
                            if (discount > 0) {
                                const discountedPrice = basePrice * (1 - discount / 100);
                                // Р СџРЎР‚Р ВµР С•Р В±РЎР‚Р В°Р В·РЎС“Р ВµР С РЎвЂ Р ВµР Р…РЎС“ Р Р† РЎвЂ Р ВµР В»Р С•Р Вµ РЎвЂЎР С‘РЎРѓР В»Р С• Р В±Р ВµР В· Р С”Р С•Р С—Р ВµР ВµР С”
                                const priceNow = Math.floor(discountedPrice);
                                priceElement.innerText = `${priceNow}РІвЂљР…`;
                                normalPriceElement.innerText = `${basePrice}РІвЂљР…`;

                                normalPriceElement.style.textDecoration = "line-through";
                            } else {
                                priceElement.innerText = `${basePrice}РІвЂљР…`;
                                normalPriceElement.innerText = ""; // Р С›РЎвЂЎР С‘РЎвЂ°Р В°Р ВµР С РЎРѓРЎвЂљР В°РЎР‚РЎС“РЎР‹ РЎвЂ Р ВµР Р…РЎС“
                            }
                        })
                        .catch(error => console.error('Р С›РЎв‚¬Р С‘Р В±Р С”Р В° Р С—РЎР‚Р С‘ Р С—Р С•Р В»РЎС“РЎвЂЎР ВµР Р…Р С‘Р С‘ РЎвЂ Р ВµР Р…РЎвЂ№:', error));
                });

            }
            rebuilCardsPrice()

            // Р СџР В°Р С–Р С‘Р Р…Р В°РЎвЂ Р С‘РЎРЏ


            function fetchProducts(url) {
                fetch(url, {
                        headers: {
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    })
                    .then(response => response.json()) // Р СџР С•Р В»РЎС“РЎвЂЎР В°Р ВµР С Р Т‘Р В°Р Р…Р Р…РЎвЂ№Р Вµ Р Р† РЎвЂћР С•РЎР‚Р СР В°РЎвЂљР Вµ JSON
                    .then(data => {
                        // Р С›Р В±Р Р…Р С•Р Р†Р В»РЎРЏР ВµР С Р С”Р С•Р Р…РЎвЂљР ВµР Р…РЎвЂљ Р С—РЎР‚Р С•Р Т‘РЎС“Р С”РЎвЂљР С•Р Р†
                        document.getElementById("productsWrap").innerHTML = data.filterProduts;
                        // Р С›Р В±Р Р…Р С•Р Р†Р В»РЎРЏР ВµР С Р С—Р В°Р С–Р С‘Р Р…Р В°РЎвЂ Р С‘РЎР‹
                        document.getElementById("pagination").innerHTML = data.pagination;
                    })
                    .catch(error => console.error('Р С›РЎв‚¬Р С‘Р В±Р С”Р В°:', error)); // Р С›Р В±РЎР‚Р В°Р В±Р С•РЎвЂљР С”Р В° Р С•РЎв‚¬Р С‘Р В±Р С•Р С”
            }

            document.body.addEventListener("click", function(e) {
                let pageLink = e.target.closest("#pagination a");
                if (pageLink) {
                    e.preventDefault(); // Р С›РЎвЂљР СР ВµР Р…РЎРЏР ВµР С РЎРѓРЎвЂљР В°Р Р…Р Т‘Р В°РЎР‚РЎвЂљР Р…РЎвЂ№Р в„– Р С—Р ВµРЎР‚Р ВµРЎвЂ¦Р С•Р Т‘
                    let pageUrl = new URL(pageLink.href); // Р СџР С•Р В»РЎС“РЎвЂЎР В°Р ВµР С URL Р С‘Р В· РЎРѓРЎРѓРЎвЂ№Р В»Р С”Р С‘
                    let pageNumber = pageUrl.searchParams.get("page"); // Р вЂР ВµРЎР‚Р ВµР С Р Р…Р С•Р СР ВµРЎР‚ РЎРѓРЎвЂљРЎР‚Р В°Р Р…Р С‘РЎвЂ РЎвЂ№
                    fetchFilteredProducts(pageNumber);
                    loadPopupsContent()
                }
            });

            document.querySelectorAll('.sidebarFilter__label input[type="checkbox"]').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    fetchFilteredProducts(1); // Р СџРЎР‚Р С‘ Р С‘Р В·Р СР ВµР Р…Р ВµР Р…Р С‘Р С‘ РЎвЂћР С‘Р В»РЎРЉРЎвЂљРЎР‚Р В° Р В·Р В°Р С–РЎР‚РЎС“Р В¶Р В°Р ВµР С Р С—Р ВµРЎР‚Р Р†РЎС“РЎР‹ РЎРѓРЎвЂљРЎР‚Р В°Р Р…Р С‘РЎвЂ РЎС“
                    rebuilCardsPrice()
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
                            page: page, // Р СџР ВµРЎР‚Р ВµР Т‘Р В°Р ВµР С РЎРѓРЎвЂљРЎР‚Р В°Р Р…Р С‘РЎвЂ РЎС“ Р Р† Р В·Р В°Р С—РЎР‚Р С•РЎРѓ
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
                                <div class="bigProdCard__toolTip">Р вЂ™ Р С”Р С•РЎР‚Р В·Р С‘Р Р…РЎС“</div>
                            </div>
                            <div class="bigProdCard__quckView control quickProd" data-modal="#popupProd" data-prod="${product.id}"><i class="fas fa-eye"></i>
                                <div class="bigProdCard__toolTip">Р вЂРЎвЂ№РЎРѓРЎвЂљРЎР‚РЎвЂ№Р в„– Р С—РЎР‚Р С•РЎРѓР СР С•РЎвЂљРЎР‚</div>
                            </div>
                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                <div class="bigProdCard__toolTip">Р вЂќР С•Р В±Р В°Р Р†Р С‘РЎвЂљРЎРЉ Р Р† Р С‘Р В·Р В±РЎР‚Р В°Р Р…Р Р…Р С•Р Вµ</div>
                            </div>
                        </div>
                    </div>
                    <div class="bigProdCard__info">
                        <a class="bigProdCard__category" href="${product.category ? '/' + product.category.slug : '#'}">${product.category ? product.category.titleh1 : 'Р вЂР ВµР В· Р С”Р В°РЎвЂљР ВµР С–Р С•РЎР‚Р С‘Р С‘'}</a>
                        <a class="bigProdCard__title" href="${product.slug ? '/' + product.category.slug + '/' + product.subcategory.slug + '/' + product.slug : '#'}">${product.h1}</a>
                        <div class="bigProdCard__priceWrap">
                            <span class="normalPrice" style="text-decoration: line-through;">${product.price}РІвЂљР…</span>
                            <span class="discount">${product.old_price}РІвЂљР…</span>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

                        // Р С›Р В±Р Р…Р С•Р Р†Р В»РЎРЏР ВµР С Р С—Р В°Р С–Р С‘Р Р…Р В°РЎвЂ Р С‘РЎР‹
                        document.querySelector('.pagination').innerHTML = data.pagination;
                        rebuilCardsPrice()
                        loadPopupsContent()
                    });
            }



            function initPricefilter() {
                const slider = document.querySelector('.custom-range-slider');
                const track = slider.querySelector('.track');
                const range = slider.querySelector('.range');
                const leftThumb = slider.querySelector('.left-thumb');
                const rightThumb = slider.querySelector('.right-thumb');
                const minPriceDisplay = document.getElementById('min-price-display');
                const maxPriceDisplay = document.getElementById('max-price-display');
                const minPriceInput = document.getElementById('min-price');
                const maxPriceInput = document.getElementById('max-price');
                const productCards = document.querySelectorAll('.card'); // Р С™Р В°РЎР‚РЎвЂљР С•РЎвЂЎР С”Р С‘ РЎвЂљР С•Р Р†Р В°РЎР‚Р С•Р Р†

                let min = 0,
                    max = 15000;
                let currentMin = min,
                    currentMax = max;

                // Р В¤РЎС“Р Р…Р С”РЎвЂ Р С‘РЎРЏ Р С•Р В±Р Р…Р С•Р Р†Р В»Р ВµР Р…Р С‘РЎРЏ Р С—Р С•Р В»Р С•Р В¶Р ВµР Р…Р С‘РЎРЏ Р С—Р С•Р В»Р В·РЎС“Р Р…Р С”Р С•Р Р†
                function updateThumbPosition(thumb, value) {
                    const percent = ((value - min) / (max - min)) * 100;
                    thumb.style.left = `${percent}%`;
                }

                // Р В¤РЎС“Р Р…Р С”РЎвЂ Р С‘РЎРЏ Р С•Р В±Р Р…Р С•Р Р†Р В»Р ВµР Р…Р С‘РЎРЏ Р Т‘Р С‘Р В°Р С—Р В°Р В·Р С•Р Р…Р В°
                function updateRange() {
                    const minPercent = ((currentMin - min) / (max - min)) * 100;
                    const maxPercent = ((currentMax - min) / (max - min)) * 100;
                    range.style.left = `${minPercent}%`;
                    range.style.width = `${maxPercent - minPercent}%`;
                }

                // Р В¤РЎС“Р Р…Р С”РЎвЂ Р С‘РЎРЏ РЎвЂћР С‘Р В»РЎРЉРЎвЂљРЎР‚Р В°РЎвЂ Р С‘Р С‘ РЎвЂљР С•Р Р†Р В°РЎР‚Р С•Р Р†
                function filterProducts() {
                    productCards.forEach(card => {
                        const discountSpan = card.querySelector('.discount');
                        const price = parseFloat(discountSpan?.textContent.replace('РІвЂљР…', '').trim()) || 0;

                        if (price >= currentMin && price <= currentMax) {
                            card.style.display = ''; // Р СџР С•Р С”Р В°Р В·РЎвЂ№Р Р†Р В°Р ВµР С Р С”Р В°РЎР‚РЎвЂљР С•РЎвЂЎР С”РЎС“
                        } else {
                            card.style.display = 'none'; // Р РЋР С”РЎР‚РЎвЂ№Р Р†Р В°Р ВµР С Р С”Р В°РЎР‚РЎвЂљР С•РЎвЂЎР С”РЎС“
                        }
                    });
                }

                // Р В¤РЎС“Р Р…Р С”РЎвЂ Р С‘РЎРЏ Р С—Р ВµРЎР‚Р ВµР СР ВµРЎвЂ°Р ВµР Р…Р С‘РЎРЏ Р С—Р С•Р В»Р В·РЎС“Р Р…Р С”Р В°
                function moveThumb(thumb, event) {
                    const rect = slider.getBoundingClientRect();
                    const offsetX = event.touches ? event.touches[0].clientX - rect.left : event.clientX - rect
                        .left;
                    const percent = Math.min(Math.max((offsetX / rect.width) * 100, 0), 100);
                    const value = Math.round(min + ((max - min) * percent) / 100);

                    if (thumb === leftThumb && value < currentMax) {
                        currentMin = value;
                        minPriceDisplay.textContent = value;
                        minPriceInput.value = value;
                    } else if (thumb === rightThumb && value > currentMin) {
                        currentMax = value;
                        maxPriceDisplay.textContent = value;
                        maxPriceInput.value = value;
                    }

                    updateThumbPosition(thumb, value);
                    updateRange();
                    filterProducts(); // Р В¤Р С‘Р В»РЎРЉРЎвЂљРЎР‚РЎС“Р ВµР С РЎвЂљР С•Р Р†Р В°РЎР‚РЎвЂ№ РЎРѓРЎР‚Р В°Р В·РЎС“ Р С—Р С•РЎРѓР В»Р Вµ Р С—Р ВµРЎР‚Р ВµР СР ВµРЎвЂ°Р ВµР Р…Р С‘РЎРЏ
                }

                // Р С›Р В±РЎР‚Р В°Р В±Р С•РЎвЂљРЎвЂЎР С‘Р С”Р С‘ РЎРѓР С•Р В±РЎвЂ№РЎвЂљР С‘Р в„– Р Т‘Р В»РЎРЏ Р С—Р ВµРЎР‚Р ВµР СР ВµРЎвЂ°Р ВµР Р…Р С‘РЎРЏ Р С—Р С•Р В»Р В·РЎС“Р Р…Р С”Р С•Р Р†
                [leftThumb, rightThumb].forEach((thumb) => {
                    thumb.addEventListener('mousedown', (e) => {
                        const moveHandler = (event) => moveThumb(thumb, event);
                        document.addEventListener('mousemove', moveHandler);
                        document.addEventListener('mouseup', () => {
                            document.removeEventListener('mousemove', moveHandler);
                        }, {
                            once: true
                        });
                    });

                    thumb.addEventListener('touchstart', (e) => {
                        const moveHandler = (event) => moveThumb(thumb, event);
                        document.addEventListener('touchmove', moveHandler);
                        document.addEventListener('touchend', () => {
                            document.removeEventListener('touchmove', moveHandler);
                        }, {
                            once: true
                        });
                    });
                });

                // Р ВР Р…Р С‘РЎвЂ Р С‘Р В°Р В»Р С‘Р В·Р В°РЎвЂ Р С‘РЎРЏ
                updateThumbPosition(leftThumb, currentMin);
                updateThumbPosition(rightThumb, currentMax);
                updateRange();
                filterProducts();
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
