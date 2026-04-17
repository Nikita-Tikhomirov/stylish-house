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

        <!-- РџРµСЂРІС‹Р№ СЌРєСЂР°РЅ -->
        <section class="s-catMain wrapper">
            <div class="s-catMain__img"><img src="{{ Storage::url($subcategory->img) }}" alt="" /></div>
            <div class="breadcrumbs">
                <ul class="breadcrumbs__list">
                    <li class=""><a class="breadcrumbs__link" href="{{ route('front.home') }}">Р“Р»Р°РІРЅР°СЏ</a></li>

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

        <!-- Р’С‹РІРѕРґ С‚РѕРІР°СЂРѕРІ РєР°Рє РІРµР·РґРµ -->

            <section class="popularsWithFilter wrapper">
                <h2 class="popularsWithFilter__title title"> <span>РџРѕРїСѓР»СЏСЂРЅС‹Рµ С‚РѕРІР°СЂС‹</span><svg width="114"
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

        <!-- Р’РёРґС‹ РјРѕРЅС‚Р°Р¶Р° -->
        @if (!empty($installationTypes) && $installationTypes->isNotEmpty())
            <x-front.section.subcategory-installation-types :installationTypes="$installationTypes" />
        @else
            <x-front.section.rollets-installation />
        @endif

        <!-- РљР°Р»СЊРєСѓР»СЏС‚РѕСЂ -->
        @if (!empty($firstProduct))
            @php
                $product = $firstProduct;
            @endphp
            <section class="prodMain wrapper catCalculator" style="padding-top: 40px;">
                <h2 class="prodMain__title title"> <span>Р Р°СЃСЃС‡РёС‚Р°С‚СЊ СЃС‚РѕРёРјРѕСЃС‚СЊ СЂРѕР»СЊСЃС‚Р°РІРµРЅ</span><svg width="114" height="35"
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
                                    <img src="{{ asset($productFabricImage) }}" alt="{{ $product->h1 }}" />
                                @endif
                            </div>

                            <div class="prodForm__bar">
                                @foreach ($sameModelProducts as $sameProduct)
                                    @php
                                        $sameMainImage = $sameProduct->image_thumb_path ?: $sameProduct->image_path;
                                        $sameFabricImage = $sameProduct->fabric_thumb_path ?: $sameProduct->fabric_photo;
                                    @endphp
                                    @if ($sameMainImage)
                                        <img src="{{ asset($sameMainImage) }}" alt="{{ $sameProduct->h1 }}" />
                                    @elseif ($sameFabricImage)
                                        <img src="{{ asset($sameFabricImage) }}" alt="{{ $sameProduct->h1 }}" />
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="prodForm__calcFormWrap">
                        <div class="prodForm__formSubtitle">{{ $category->titleh1 }}</div>
                        <div class="prodForm__formTitle">{{ $product->h1 }}</div>
                        <div class="prodForm__description">
                            <p>{{ $product->first_screenn_description }}...</p><span class="more">РџРѕРґСЂРѕР±РЅРµРµ</span>
                        </div>

                        <input type="hidden" name="modelSelect" class="modelSelect" value="{{ $product->model_title }}">
                        <input type="hidden" name="cloth" class="cloth" value="{{ $product->cloth }}">
                        <input type="hidden" name="model" class="model" value="{{ $product->model_title }}">
                        <input type="hidden" class="discount" value="{{ $product->discount }}">

                        <div class="prodForm__sizeWrap">
                            <label class="prodForm__label">
                                <p>РЁРёСЂРёРЅР°, РјРј</p>
                                @if ($product->min_width)
                                    <input class="prodForm__input width-input" type="number" name="width" value="{{ $product->min_width }}" required />
                                @else
                                    <input class="prodForm__input width-input" type="number" name="width" value="500" required />
                                @endif
                            </label>
                            <label class="prodForm__label">
                                <p>Р’С‹СЃРѕС‚Р°, РјРј</p>
                                @if ($product->min_height)
                                    <input class="prodForm__input height-input" type="number" name="height" value="{{ $product->min_height }}" required />
                                @else
                                    <input class="prodForm__input height-input" type="number" name="height" value="500" required />
                                @endif
                            </label>
                        </div>

                        <div class="calcWidhType">
                            <div class="cartForm__optionsList">
                                <div class="cartForm__listTitle">РўРёРї РјРѕРЅС‚Р°Р¶Р°</div>
                                <ul>
                                    <li>
                                        <label>
                                            <input class="widthType" type="radio" name="widhType{{ $product->id }}" value="РЁРёСЂРёРЅР° РїРѕ С‚РєР°РЅРё" checked>
                                            <span>РљРѕСЂРѕР± РІРЅСѓС‚СЂРё (СЃРєСЂС‹С‚С‹Р№)</span>
                                        </label>
                                    </li>
                                    <li>
                                        <label>
                                            <input class="widthType" type="radio" name="widhType{{ $product->id }}" value="РЁРёСЂРёРЅР° РїРѕ РіР°Р±Р°СЂРёС‚Сѓ">
                                            <span>РљРѕСЂРѕР± СЃРЅР°СЂСѓР¶Рё</span>
                                        </label>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="calcWidhType">
                            <div class="cartForm__optionsList">
                                <div class="cartForm__listTitle">РўРёРї Р·Р°РїРѕСЂРЅРѕРіРѕ СѓСЃС‚СЂРѕР№СЃС‚РІР°</div>
                                <ul>
                                    <li>
                                        <label>
                                            <input class="widthType" type="radio" name="lock-type" value="sliders" data-price="0" checked>
                                            <span>Р—Р°РґРІРёР¶РєРё +0СЂ</span>
                                        </label>
                                    </li>
                                    <li>
                                        <label>
                                            <input class="widthType" type="radio" name="lock-type" value="lock" data-price="1600">
                                            <span>Р—Р°РјРѕРє +1600СЂ</span>
                                        </label>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="product-params-accordion" style="margin-top: 30px;">
                            <div class="cartForm__optionsList">
                                <div class="cartForm__listTitle">РҐР°СЂР°РєС‚РµСЂРёСЃС‚РёРєРё С‚РѕРІР°СЂР°</div>

                                <div class="accordion-item">
                                    <div class="accordion-header">
                                        <h4>Р’РёРґ РјРѕРЅС‚Р°Р¶Р°</h4>
                                        <span class="accordion-arrow">в–ј</span>
                                    </div>
                                    <div class="accordion-content">
                                        <div class="installation-options">
                                            <label class="option-label">
                                                <input type="radio" name="installation-type" value="overhead" data-price="{{ $product->overhead_price ?? 0 }}" {{ $product->installation_type == 'overhead' ? 'checked' : '' }}>
                                                <span class="option-name">РќР°РєР»Р°РґРЅРѕР№ РјРѕРЅС‚Р°Р¶</span>
                                                <span class="option-price">+{{ $product->overhead_price ?? 0 }}в‚Ѕ</span>
                                            </label>
                                            <div class="option-description">
                                                РџСЂРё РІС‹Р±РѕСЂРµ РЅР°РєР»Р°РґРЅРѕРіРѕ РјРѕРЅС‚Р°Р¶Р° РЅРµРѕР±С…РѕРґРёРјРѕ Рє СЂР°Р·РјРµСЂР°Рј РїСЂРѕРµРјР° РґРѕР±Р°РІСЊС‚Рµ 110РјРј РїРѕ С€РёСЂРёРЅРµ Рё 250РјРј РїРѕ РІС‹СЃРѕС‚Рµ.
                                            </div>

                                            <label class="option-label">
                                                <input type="radio" name="installation-type" value="built-in" data-price="{{ $product->builtin_price ?? 0 }}" {{ $product->installation_type == 'built-in' ? 'checked' : '' }}>
                                                <span class="option-name">Р’СЃС‚СЂРѕРµРЅРЅС‹Р№ РјРѕРЅС‚Р°Р¶</span>
                                                <span class="option-price">+{{ $product->builtin_price ?? 0 }}в‚Ѕ</span>
                                            </label>
                                            <div class="option-description">
                                                РџСЂРё РІС‹Р±РѕСЂРµ РІСЃС‚СЂРѕРµРЅРЅРѕРіРѕ РјРѕРЅС‚Р°Р¶Р° СЂРµРєРѕРјРµРЅРґСѓРµС‚СЃСЏ РѕС‚ СЂР°Р·РјРµСЂРѕРІ РїСЂРѕРµРјР° РѕС‚РЅСЏС‚СЊ РїРѕ С€РёСЂРёРЅРµ Рё РІС‹СЃРѕС‚Рµ 5 РјРј.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <div class="accordion-header">
                                        <h4>РўРёРї СѓРїСЂР°РІР»РµРЅРёСЏ СЂРѕР»СЊСЃС‚Р°РІРЅРё</h4>
                                        <span class="accordion-arrow">в–ј</span>
                                    </div>
                                    <div class="accordion-content">
                                        <div class="control-options">
                                            <label class="option-label">
                                                <input type="radio" name="control-type" value="strap" data-price="{{ $product->strap_price ?? 0 }}" {{ $product->control_type == 'strap' ? 'checked' : '' }}>
                                                <span class="option-name">Р›РµРЅС‚РѕС‡РЅС‹Р№ РёР»Рё С€РЅСѓСЂРѕРІРѕР№ РёРЅРµСЂС†РёРѕРЅРЅС‹Р№ РїСЂРёРІРѕРґ</span>
                                                <span class="option-price">+{{ $product->strap_price ?? 0 }}в‚Ѕ</span>
                                            </label>
                                            <div class="option-description">
                                                Р“СЂСѓР·РѕРїРѕРґСЉРµРјРЅРѕСЃС‚СЊ РґРѕ 15 РєРі. Р СѓС‡РЅРѕРµ СѓРїСЂР°РІР»РµРЅРёРµ.
                                            </div>

                                            <label class="option-label">
                                                <input type="radio" name="control-type" value="cardan" data-price="{{ $product->cardan_price ?? 0 }}" {{ $product->control_type == 'cardan' ? 'checked' : '' }}>
                                                <span class="option-name">Р’РѕСЂРѕС‚РєРѕРІС‹Р№ РїСЂРёРІРѕРґ (РєР°СЂРґР°РЅ)</span>
                                                <span class="option-price">+{{ $product->cardan_price ?? 0 }}в‚Ѕ</span>
                                            </label>
                                            <div class="option-description">
                                                Р“СЂСѓР·РѕРїРѕРґСЉРµРјРЅРѕСЃС‚СЊ РґРѕ 35 РєРі. Р СѓС‡РЅРѕРµ СѓРїСЂР°РІР»РµРЅРёРµ.
                                            </div>

                                            <label class="option-label">
                                                <input type="radio" name="control-type" value="pim" data-price="{{ $product->pim_price ?? 0 }}" {{ $product->control_type == 'pim' ? 'checked' : '' }}>
                                                <span class="option-name">РџСЂСѓР¶РёРЅРЅРѕ-РёРЅРµСЂС†РёРѕРЅРЅС‹Р№ РјРµС…Р°РЅРёР·Рј (РџРРњ)</span>
                                                <span class="option-price">+{{ $product->pim_price ?? 0 }}в‚Ѕ</span>
                                            </label>
                                            <div class="option-description">
                                                Р“СЂСѓР·РѕРїРѕРґСЉРµРјРЅРѕСЃС‚СЊ РѕС‚ 6 РґРѕ 80 РєРі. Р СѓС‡РЅРѕРµ СѓРїСЂР°РІР»РµРЅРёРµ.
                                            </div>

                                            <label class="option-label">
                                                <input type="radio" name="control-type" value="electric" data-price="{{ $product->electric_price ?? 6793 }}" {{ $product->control_type == 'electric' ? 'checked' : '' }}>
                                                <span class="option-name">РђРІС‚РѕРјР°С‚РёС‡РµСЃРєРѕРµ СѓРїСЂР°РІР»РµРЅРёРµ (СЌР»РµРєС‚СЂРѕРїСЂРёРІРѕРґ)</span>
                                                <span class="option-price">+{{ $product->electric_price ?? 6793 }}в‚Ѕ</span>
                                            </label>
                                            <div class="option-description">
                                                РўРёРї СѓРїСЂР°РІР»РµРЅРёРµ: РІС‹РєР»СЋС‡Р°С‚РµР»СЊ РЅР°СЃС‚РµРЅРЅС‹Р№ РёР»Рё РјРёРЅРё РїСѓР»СЊС‚.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <div class="accordion-header">
                                        <h4>Р‘Р»РѕРєРёСЂСѓСЋС‰РёРµ СѓСЃС‚СЂРѕР№СЃС‚РІР°</h4>
                                        <span class="accordion-arrow">в–ј</span>
                                    </div>
                                    <div class="accordion-content">
                                        <div class="lock-options">
                                            <label class="option-label">
                                                <input type="radio" name="lock-device" value="rigel" data-price="{{ $product->rigel_price ?? 1575 }}" {{ $product->lock_device == 'rigel' ? 'checked' : '' }}>
                                                <span class="option-name">Р РёРіРµР»СЊРЅС‹Р№ Р·Р°РјРѕРє (СЃ РєР»СЋС‡РѕРј)</span>
                                                <span class="option-price">+{{ $product->rigel_price ?? 1575 }}в‚Ѕ</span>
                                            </label>
                                            <div class="option-description">
                                                РСЃРїРѕР»СЊР·СѓРµС‚СЃСЏ РїСЂРё СЂСѓС‡РЅРѕРј СѓРїСЂР°РІР»РµРЅРёРё. Р РѕР»СЊСЃС‚Р°РІРЅРё Р·Р°РїРёСЂР°СЋС‚СЃСЏ РЅР° Р·Р°РјРѕРє. РќРµ СЂРµРєРѕРјРµРЅРґСѓРµС‚СЃСЏ РёСЃРїРѕР»СЊР·РѕРІР°С‚СЊ РїСЂРё СЌР»РµРєС‚СЂРѕРїСЂРёРІРѕРґРµ.
                                            </div>

                                            <label class="option-label">
                                                <input type="radio" name="lock-device" value="shchyolka" data-price="{{ $product->shchyolka_price ?? 171 }}" {{ $product->lock_device == 'shchyolka' ? 'checked' : '' }}>
                                                <span class="option-name">Р СѓС‡РЅРѕР№ СЂРёРіРµР»СЊ (С‰РµРєРѕР»РґР°)</span>
                                                <span class="option-price">+{{ $product->shchyolka_price ?? 171 }}в‚Ѕ</span>
                                            </label>
                                            <div class="option-description">
                                                РСЃРїРѕР»СЊР·СѓРµС‚СЃСЏ РїСЂРё СЂСѓС‡РЅРѕРј СѓРїСЂР°РІР»РµРЅРёРё. Р›СЋР±РѕР№ Р¶РµР»Р°СЋС‰РёР№ СЃРјРѕР¶РµС‚ РѕС‚РєСЂС‹С‚СЊ СЂРѕР»Р»РµС‚Сѓ. РќРµ СЂРµРєРѕРјРµРЅРґСѓРµС‚СЃСЏ РёСЃРїРѕР»СЊР·РѕРІР°С‚СЊ РїСЂРё СЌР»РµРєС‚СЂРѕРїСЂРёРІРѕРґРµ.
                                            </div>

                                            <label class="option-label">
                                                <input type="radio" name="lock-device" value="upper" data-price="{{ $product->upper_price ?? 2358 }}" {{ $product->lock_device == 'upper' ? 'checked' : '' }}>
                                                <span class="option-name">Р’РµСЂС…РЅРёР№ СЂРёРіРµР»СЊ (РІРµСЂС…РЅРёРµ Р·Р°РјРєРё)</span>
                                                <span class="option-price">+{{ $product->upper_price ?? 2358 }}в‚Ѕ</span>
                                            </label>
                                            <div class="option-description">
                                                РСЃРїРѕР»СЊР·СѓРµС‚СЃСЏ РїСЂРё СѓРїСЂР°РІР»РµРЅРёРё РІРѕСЂРѕС‚РєРѕРІС‹Р№ РїСЂРёРІРѕРґ РёР»Рё Р°РІС‚РѕРјР°С‚РёС‡РµСЃРєРѕРј СѓРїСЂР°РІР»РµРЅРёРё РґР»СЏ РѕРіСЂР°РЅРёС‡РµРЅРёСЏ СЂСѓС‡РЅРѕРіРѕ РїРѕРґСЉРµРјР° РїРѕР»РѕС‚РЅР°.
                                            </div>

                                            <label class="option-label">
                                                <input type="radio" name="lock-device" value="none" data-price="0" {{ $product->lock_device == 'none' ? 'checked' : '' }}>
                                                <span class="option-name">Р‘РµР· Р±Р»РѕРєРёСЂРѕРІРєРё</span>
                                                <span class="option-price">+0в‚Ѕ</span>
                                            </label>
                                            <div class="option-description">
                                                РџСЂРёРјРµРЅСЏРµС‚СЃСЏ РІ РѕСЃРЅРѕРІРЅРѕРј РїСЂРё РёР·РґРµР»РёРё РґР»СЏ СЃР°РЅС‚РµС…РЅРёС‡РµСЃРєРёС… РїСЂРѕРµРјРѕРІ.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <div class="accordion-header">
                                        <h4>Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ РѕРїС†РёРё</h4>
                                        <span class="accordion-arrow">в–ј</span>
                                    </div>
                                    <div class="accordion-content">
                                        <div class="additional-options">
                                            <label class="option-label">
                                                <input type="checkbox" name="ral-paint" value="ral-paint" data-price="{{ $product->ral_price ?? 8010 }}" {{ $product->ral_paint ? 'checked' : '' }}>
                                                <span class="option-name">РџРѕРєСЂР°СЃРєР° РїРѕ RAL</span>
                                                <span class="option-price">+{{ $product->ral_price ?? 8010 }}в‚Ѕ</span>
                                            </label>
                                            <div class="option-description">
                                                Р’РѕР·РјРѕР¶РЅР° РїРѕРєСЂР°СЃРєР° РїСЂРѕС„РёР»СЏ AER44m/S РёР»Рё AER55m/S. РџСЂРѕС„РёР»СЊ СЃ РїРµРЅРЅС‹Рј Р·Р°РїРѕР»РЅРµРЅРёРµРј РїРѕРєСЂР°СЃРєРµ РЅРµ РїРѕРґР»РµР¶РёС‚!
                                            </div>

                                            <label class="option-label">
                                                <input type="checkbox" name="photo-print" value="photo-print" data-price="{{ $product->photo_price ?? 3920 }}" {{ $product->photo_print ? 'checked' : '' }}>
                                                <span class="option-name">РќР°РЅРµСЃРµРЅРёРµ С„РѕС‚РѕРїРµС‡Р°С‚Рё</span>
                                                <span class="option-price">+{{ $product->photo_price ?? 3920 }}в‚Ѕ</span>
                                            </label>
                                            <div class="option-description">
                                                Р’РѕР·РјРѕР¶РЅР° РЅР°РЅРµСЃРµРЅРёРµ Р»СЋР±РѕРіРѕ СЂРёСЃСѓРЅРєР° РїСѓС‚РµРј С„РѕС‚РѕРїРµС‡Р°С‚Рё РЅР° РІСЃРµРј РёР·РґРµР»РёРё.
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
                            <div class="prodForm__price">Р¦РµРЅР°: 1200в‚Ѕ</div>
                            <button class="prodForm__addToCart" data-id="{{ $product->id }}"> Р”РѕР±Р°РІРёС‚СЊ РІ
                                РєРѕСЂР·РёРЅСѓ </button>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if (!empty($workExamples) && $workExamples->isNotEmpty())
            <x-front.section.subgallery :gallerys="$workExamples" :category='$category' title=""></x-front.section.gallery>
        @endif

        <!-- РћРїР»Р°С‚Р° Рё РґРѕСЃС‚Р°РІРєР° -->
        <x-front.section.delivery :title="$homePageFields->section_delivery_title" :topText="$homePageFields->section_delivery_top_text" :bottomText="$homePageFields->section_delivery_bottom_text"
            :iconCards="$iconCards"></x-front.section.delivery>

        <!-- Р’РћРџР РћРЎР« Р РћРўР’Р•РўР« -->
        @if ($subcategory->faq_html)
            <section class="s-faq wrapper">
                <div class="s-faq__container">
                    <div class="s-faq__title-wrap">
                        <h2 class="s-faq__title title"> <span>Р’РѕРїСЂРѕСЃС‹ Рё РѕС‚РІРµС‚С‹</span><svg width="114" height="35"
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
            <x-front.section.faqcat title="Р’РѕРїСЂРѕСЃС‹ Рё РѕС‚РІРµС‚С‹" :faqs="$faqs"></x-front.section.faqcat>
        @endif

        <!-- РЎР•Рћ РўР•РљРЎРў -->
        <x-front.section.seo :seoSection="$subcategory->seo"></x-front.section.seo>

        <!-- Р’СЃРµ РєР°С‚РµРіРѕСЂРёРё -->
        <section class="s-tags wrapper">
            <h2 class="s-tags__title title"> <span>Р’СЃРµ РєР°С‚РµРіРѕСЂРёРё</span><svg width="114" height="35"
                    viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                        stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                </svg></h2>
            <div class="s-tags__tags">
                <div class="accardionJs">
                    <div class="accardion__title">Р’СЃРµ РєР°С‚РµРіРѕСЂРёРё</div>
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

                }; // Р•СЃР»Рё СѓР¶Рµ РµСЃС‚СЊ РјРѕРґР°Р»РєР°, РІС‹С…РѕРґРёРј


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

                        console.log('Р—Р°РїСЂРѕСЃ Рє /popup СЃ prodId:', prodId);

                        // РџРѕР»СѓС‡Р°РµРј РґР°РЅРЅС‹Рµ Рѕ С‚РѕРІР°СЂРµ СЃ СЃРµСЂРІРµСЂР°
                        fetch(`/popup/${prodId}`)
                            .then(response => response.json())
                            .then(product => {
                                // Р—Р°РїРѕР»РЅСЏРµРј РїРѕРїР°Рї РґР°РЅРЅС‹РјРё С‚РѕРІР°СЂР°
                                document.querySelector('#popupProd .prodForm__formSubtitle')
                                    .innerText = product.title;
                                document.querySelector('#popupProd .prodForm__formTitle')
                                    .innerText = `Р—Р°РєР°Р·Р°С‚СЊ ${product.title}`;
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
                                // РљРѕСЂСЂРµРєС‚РёСЂСѓРµРј РїСѓС‚СЊ


                                // console.log(product.gallery);
                                // РћС‡РёС‰Р°РµРј СЃС‚Р°СЂСѓСЋ РіР°Р»РµСЂРµСЋ
                                let gallery = document.querySelector(
                                    '#popupProd .prodForm__bar');
                                gallery.innerHTML = '';

                                // Р”РѕР±Р°РІР»СЏРµРј РёР·РѕР±СЂР°Р¶РµРЅРёСЏ СЃ СЃСЃС‹Р»РєР°РјРё
                                product.gallery.forEach(related => {
                                    let link = document.createElement('a');
                                    link.href = related.link; // РЎСЃС‹Р»РєР° РЅР° С‚РѕРІР°СЂ
                                    let img = document.createElement('img');
                                    if (related.image) {

                                        img.src =
                                            `${related.image}`; // РџСѓС‚СЊ Рє РёР·РѕР±СЂР°Р¶РµРЅРёСЋ
                                    } else {

                                        img.src =
                                            `${related.fabric_photo}`; // РџСѓС‚СЊ Рє РёР·РѕР±СЂР°Р¶РµРЅРёСЋ
                                    }


                                    link.appendChild(
                                        img); // Р’СЃС‚Р°РІР»СЏРµРј РёР·РѕР±СЂР°Р¶РµРЅРёРµ РІ СЃСЃС‹Р»РєСѓ
                                    gallery.appendChild(
                                        link); // Р”РѕР±Р°РІР»СЏРµРј СЃСЃС‹Р»РєСѓ РІ РіР°Р»РµСЂРµСЋ

                                });

                                console.log(product.model);

                                // Р”РѕР±Р°РІРёС‚СЊ id РґР»СЏ РєРЅРѕРїРєРё РґРѕР±Р°РІРёС‚СЊ РІ РєРѕСЂР·РёРЅСѓ
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
                                console.error('РћС€РёР±РєР° РїСЂРё Р·Р°РіСЂСѓР·РєРµ РґР°РЅРЅС‹С… С‚РѕРІР°СЂР°:', error);
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
                    // РЈРґР°Р»СЏРµРј СЃС‚Р°СЂС‹Рµ РѕР±СЂР°Р±РѕС‚С‡РёРєРё РїРµСЂРµРґ РґРѕР±Р°РІР»РµРЅРёРµРј РЅРѕРІС‹С…
                    function removeEventListeners(element, events) {
                        const clone = element.cloneNode(true);
                        element.replaceWith(clone);
                        return clone;
                    }
                    // РћС‡РёС‰Р°РµРј РѕР±СЂР°Р±РѕС‚С‡РёРєРё РїРµСЂРµРґ РґРѕР±Р°РІР»РµРЅРёРµРј
                    counterMinusBtn = removeEventListeners(counterMinusBtn, ['click']);
                    counterPlusBtn = removeEventListeners(counterPlusBtn, ['click']);
                    counterInput = removeEventListeners(counterInput, ['input']);
                    let priceNow = 0;

                    // РџРµСЂРµСЃС‡РµС‚ С†РµРЅС‹ СЃ СѓС‡РµС‚РѕРј РєРѕР»РёС‡РµСЃС‚РІР° Рё СЃРєРёРґРєРё
                    function rebuildPrice(price, counterValue, discount = 0) {
                        if (price <= 0 || isNaN(price)) {
                            priceElement.textContent = 'Р¦РµРЅР° РїРѕ Р·Р°РїСЂРѕСЃСѓ';
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

                        // РџСЂРµРѕР±СЂР°Р·СѓРµРј С†РµРЅСѓ РІ С†РµР»РѕРµ С‡РёСЃР»Рѕ
                        priceNow = Math.floor(priceNow);
                        priceElement.textContent = `Р¦РµРЅР°: ${priceNow}в‚Ѕ`;
                    }

                    // Р¤СѓРЅРєС†РёСЏ РґР»СЏ РїРѕР»СѓС‡РµРЅРёСЏ Рё РѕР±РЅРѕРІР»РµРЅРёСЏ С†РµРЅС‹
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
                            rebuildPrice(0, quantity, discount); // Р—РґРµСЃСЊ РІРјРµСЃС‚Рѕ 0 Р±СѓРґРµС‚ "Р¦РµРЅР° РїРѕ Р·Р°РїСЂРѕСЃСѓ"
                            return;
                        }

                        // РћРїС†РёРѕРЅР°Р»СЊРЅРѕ: РёРЅРґРёРєР°С‚РѕСЂ Р·Р°РіСЂСѓР·РєРё, С‡С‚РѕР±С‹ РЅРµ РїРѕРєР°Р·С‹РІР°Р»Рѕ СЃС‚Р°СЂРѕРµ Р·РЅР°С‡РµРЅРёРµ
                        priceElement.textContent = 'Р Р°СЃС‡С‘С‚...';

                        fetch(
                                `/sheet-names?width=${width}&height=${height}&model=${model}&control=${control}&cloth=${cloth}&modelId=${modelId}&prodTitle=${prodTitleTorequest}`
                            )
                            .then(response => response.json())
                            .then(data => {
                                const basePrice = data.price || 0;
                                rebuildPrice(basePrice, quantity, discount);
                            })
                            .catch(error => {
                                console.error('РћС€РёР±РєР° РїСЂРё РїРѕР»СѓС‡РµРЅРёРё С†РµРЅС‹:', error);
                                rebuildPrice(0, quantity,
                                discount); // Р—РґРµСЃСЊ С‚РѕР¶Рµ "Р¦РµРЅР° РїРѕ Р·Р°РїСЂРѕСЃСѓ" РІ СЃР»СѓС‡Р°Рµ РѕС€РёР±РєРё
                            });
                    }

                    // РРЅРёС†РёР°Р»РёР·Р°С†РёСЏ РєРѕР»РёС‡РµСЃС‚РІР°
                    counterInput.value = counterInput.value || 1;

                    // РРЅРёС†РёР°Р»РёР·Р°С†РёСЏ UI fallback (РЅР° СЃР»СѓС‡Р°Р№, РµСЃР»Рё fetch РЅРµ СЃСЂР°Р±РѕС‚Р°РµС‚ СЃСЂР°Р·Сѓ)
                    priceElement.textContent = 'Р¦РµРЅР° РїРѕ Р·Р°РїСЂРѕСЃСѓ';

                    // РћР±СЂР°Р±РѕС‚С‡РёРєРё РґР»СЏ РёР·РјРµРЅРµРЅРёСЏ РєРѕР»РёС‡РµСЃС‚РІР° С‚РѕРІР°СЂРѕРІ
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

                    // Р”Р»СЏ РІРІРѕРґР° РІСЂСѓС‡РЅСѓСЋ
                    counterInput.addEventListener('input', () => {
                        let value = parseInt(counterInput.value);
                        if (isNaN(value) || value < 1) {
                            counterInput.value = 1;
                        }
                        fetchPrice();
                    });

                    // РР·РЅР°С‡Р°Р»СЊРЅС‹Р№ СЂР°СЃС‡РµС‚ РїСЂРё Р·Р°РіСЂСѓР·РєРµ
                    fetchPrice();

                    // РћР±РЅРѕРІР»РµРЅРёРµ С†РµРЅС‹ РїСЂРё РёР·РјРµРЅРµРЅРёРё С€РёСЂРёРЅС‹, РІС‹СЃРѕС‚С‹ РёР»Рё РґСЂСѓРіРёС… РїР°СЂР°РјРµС‚СЂРѕРІ
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

                    // РњРёРЅРјР°Р»СЊРЅСѓСЋ Рё РјР°РєСЃРёРјР°Р»СЊРЅСѓСЋ Р±СЂР°С‚СЊ РёР· РјРѕРґРµР»Рё

                    let prodTitle = element.querySelector('.bigProdCard__title').innerText.trim();

                    let width, height;

                    let counterForDouble = 1

                    if (prodTitle.includes("РЎС‚Р°РЅРґР°СЂС‚")) {
                        width = 500;
                        height = 500;
                    } else if (prodTitle.includes("РЎРїСЂРёРЅРі")) {
                        width = 700;
                        height = 500;
                    } else if (prodTitle.includes("Р“СЂР°РЅРґ")) {
                        width = 700;
                        height = 500;
                    } else if (prodTitle.includes("РљРІР°С‚СЂРѕ РєР»Р°СЃСЃРёРє")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РљРІР°С‚СЂРѕ Р»СЋРєСЃ")) {
                        width = 700;
                        height = 500;
                    } else if (prodTitle.includes("РљР»Р°СЃСЃРёРє РїСЂРµРјРёСѓРј")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р”Р°Р±Р» РєР»Р°СЃСЃРёРє")) {
                        width = 400;
                        height = 500;
                        counterForDouble = 2
                    } else if (prodTitle.includes("Р›СЋРєСЃ РїСЂРµРјРёСѓРј")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Р”Р°Р±Р» Р»СЋРєСЃ")) {
                        width = 400;
                        height = 500;
                        counterForDouble = 2
                    } else if (prodTitle.includes("РњРёРЅРё")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РњРёРЅРё РЅСЊСЋ")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РЈРЅРё-1")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РЈРЅРё-2")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РЈРЅРё-1 Р»Р°РјРёРЅР°С†РёСЏ")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РЈРЅРё-2 Р»Р°РјРёРЅР°С†РёСЏ")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РљРѕРјР±Рѕ РјРёРЅРё РЅСЊСЋ")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РљРѕРјР±Рѕ СѓРЅРё-1")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РљРѕРјР±Рѕ СѓРЅРё-2")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РљРѕРјР±Рѕ СѓРЅРё-2 Р»Р°РјРёРЅР°С†РёСЏ")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РљРѕРјР±Рѕ РІ-52 СЃС‚Р°РЅРґР°СЂС‚")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РљРѕРјР±Рѕ РљР»Р°СЃСЃРёРє")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РљРѕРјР±Рѕ РІ-52 Р»СЋРєСЃ")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РљРѕРјР±Рѕ РґР°Р±Р» РєР»Р°СЃСЃРёРє")) {
                        width = 400;
                        height = 500;
                        counterForDouble = 2
                    } else if (prodTitle.includes("РљРѕРјР±Рѕ РґР°Р±Р» Р»СЋРєСЃ")) {
                        width = 400;
                        height = 500;
                        counterForDouble = 2
                    } else if (prodTitle.includes("РљРѕРјР±Рѕ РєРІР°С‚СЂРѕ РєР»Р°СЃСЃРёРє")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РљРѕРјР±Рѕ РєРІР°С‚СЂРѕ Р»СЋРєСЃ")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РђР»СЋРјРёРЅРёРµРІС‹Рµ 50 РјРј")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("РљРѕРјРїР°РєС‚ РџСЂРµРјРёСѓРј")) {
                        width = 300;
                        height = 600;
                    } else if (prodTitle.includes("РҐL РђР±СЃРѕР»СЋС‚")) {
                        width = 300;
                        height = 600;
                    } else {
                        width = 700;
                        height = 700; // Р—РЅР°С‡РµРЅРёРµ РїРѕ СѓРјРѕР»С‡Р°РЅРёСЋ
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
                            const basePrice = data.price * counterForDouble || "Р¦РµРЅР° РїРѕ Р·Р°РїСЂРѕСЃСѓ ";
                            const discount = element.getAttribute('data-discount')
                            if (discount > 0) {
                                const discountedPrice = basePrice * (1 - discount / 100);
                                // РџСЂРµРѕР±СЂР°Р·СѓРµРј С†РµРЅСѓ РІ С†РµР»РѕРµ С‡РёСЃР»Рѕ Р±РµР· РєРѕРїРµРµРє
                                const priceNow = Math.floor(discountedPrice);
                                priceElement.innerText = `${priceNow}в‚Ѕ`;
                                normalPriceElement.innerText = `${basePrice}в‚Ѕ`;

                                normalPriceElement.style.textDecoration = "line-through";
                            } else {
                                priceElement.innerText = `${basePrice}в‚Ѕ`;
                                normalPriceElement.innerText = ""; // РћС‡РёС‰Р°РµРј СЃС‚Р°СЂСѓСЋ С†РµРЅСѓ
                            }
                        })
                        .catch(error => console.error('РћС€РёР±РєР° РїСЂРё РїРѕР»СѓС‡РµРЅРёРё С†РµРЅС‹:', error));
                });

            }
            rebuilCardsPrice()

            // РџР°РіРёРЅР°С†РёСЏ


            function fetchProducts(url) {
                fetch(url, {
                        headers: {
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    })
                    .then(response => response.json()) // РџРѕР»СѓС‡Р°РµРј РґР°РЅРЅС‹Рµ РІ С„РѕСЂРјР°С‚Рµ JSON
                    .then(data => {
                        // РћР±РЅРѕРІР»СЏРµРј РєРѕРЅС‚РµРЅС‚ РїСЂРѕРґСѓРєС‚РѕРІ
                        document.getElementById("productsWrap").innerHTML = data.filterProduts;
                        // РћР±РЅРѕРІР»СЏРµРј РїР°РіРёРЅР°С†РёСЋ
                        document.getElementById("pagination").innerHTML = data.pagination;
                    })
                    .catch(error => console.error('РћС€РёР±РєР°:', error)); // РћР±СЂР°Р±РѕС‚РєР° РѕС€РёР±РѕРє
            }

            document.body.addEventListener("click", function(e) {
                let pageLink = e.target.closest("#pagination a");
                if (pageLink) {
                    e.preventDefault(); // РћС‚РјРµРЅСЏРµРј СЃС‚Р°РЅРґР°СЂС‚РЅС‹Р№ РїРµСЂРµС…РѕРґ
                    let pageUrl = new URL(pageLink.href); // РџРѕР»СѓС‡Р°РµРј URL РёР· СЃСЃС‹Р»РєРё
                    let pageNumber = pageUrl.searchParams.get("page"); // Р‘РµСЂРµРј РЅРѕРјРµСЂ СЃС‚СЂР°РЅРёС†С‹
                    fetchFilteredProducts(pageNumber);
                    loadPopupsContent()
                }
            });

            document.querySelectorAll('.sidebarFilter__label input[type="checkbox"]').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    fetchFilteredProducts(1); // РџСЂРё РёР·РјРµРЅРµРЅРёРё С„РёР»СЊС‚СЂР° Р·Р°РіСЂСѓР¶Р°РµРј РїРµСЂРІСѓСЋ СЃС‚СЂР°РЅРёС†Сѓ
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
                            page: page, // РџРµСЂРµРґР°РµРј СЃС‚СЂР°РЅРёС†Сѓ РІ Р·Р°РїСЂРѕСЃ
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
                                <div class="bigProdCard__toolTip">Р’ РєРѕСЂР·РёРЅСѓ</div>
                            </div>
                            <div class="bigProdCard__quckView control quickProd" data-modal="#popupProd" data-prod="${product.id}"><i class="fas fa-eye"></i>
                                <div class="bigProdCard__toolTip">Р‘С‹СЃС‚СЂС‹Р№ РїСЂРѕСЃРјРѕС‚СЂ</div>
                            </div>
                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                <div class="bigProdCard__toolTip">Р”РѕР±Р°РІРёС‚СЊ РІ РёР·Р±СЂР°РЅРЅРѕРµ</div>
                            </div>
                        </div>
                    </div>
                    <div class="bigProdCard__info">
                        <a class="bigProdCard__category" href="${product.category ? '/' + product.category.slug : '#'}">${product.category ? product.category.titleh1 : 'Р‘РµР· РєР°С‚РµРіРѕСЂРёРё'}</a>
                        <a class="bigProdCard__title" href="${product.slug ? '/' + product.category.slug + '/' + product.subcategory.slug + '/' + product.slug : '#'}">${product.h1}</a>
                        <div class="bigProdCard__priceWrap">
                            <span class="normalPrice" style="text-decoration: line-through;">${product.price}в‚Ѕ</span>
                            <span class="discount">${product.old_price}в‚Ѕ</span>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

                        // РћР±РЅРѕРІР»СЏРµРј РїР°РіРёРЅР°С†РёСЋ
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
                const productCards = document.querySelectorAll('.card'); // РљР°СЂС‚РѕС‡РєРё С‚РѕРІР°СЂРѕРІ

                let min = 0,
                    max = 15000;
                let currentMin = min,
                    currentMax = max;

                // Р¤СѓРЅРєС†РёСЏ РѕР±РЅРѕРІР»РµРЅРёСЏ РїРѕР»РѕР¶РµРЅРёСЏ РїРѕР»Р·СѓРЅРєРѕРІ
                function updateThumbPosition(thumb, value) {
                    const percent = ((value - min) / (max - min)) * 100;
                    thumb.style.left = `${percent}%`;
                }

                // Р¤СѓРЅРєС†РёСЏ РѕР±РЅРѕРІР»РµРЅРёСЏ РґРёР°РїР°Р·РѕРЅР°
                function updateRange() {
                    const minPercent = ((currentMin - min) / (max - min)) * 100;
                    const maxPercent = ((currentMax - min) / (max - min)) * 100;
                    range.style.left = `${minPercent}%`;
                    range.style.width = `${maxPercent - minPercent}%`;
                }

                // Р¤СѓРЅРєС†РёСЏ С„РёР»СЊС‚СЂР°С†РёРё С‚РѕРІР°СЂРѕРІ
                function filterProducts() {
                    productCards.forEach(card => {
                        const discountSpan = card.querySelector('.discount');
                        const price = parseFloat(discountSpan?.textContent.replace('в‚Ѕ', '').trim()) || 0;

                        if (price >= currentMin && price <= currentMax) {
                            card.style.display = ''; // РџРѕРєР°Р·С‹РІР°РµРј РєР°СЂС‚РѕС‡РєСѓ
                        } else {
                            card.style.display = 'none'; // РЎРєСЂС‹РІР°РµРј РєР°СЂС‚РѕС‡РєСѓ
                        }
                    });
                }

                // Р¤СѓРЅРєС†РёСЏ РїРµСЂРµРјРµС‰РµРЅРёСЏ РїРѕР»Р·СѓРЅРєР°
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
                    filterProducts(); // Р¤РёР»СЊС‚СЂСѓРµРј С‚РѕРІР°СЂС‹ СЃСЂР°Р·Сѓ РїРѕСЃР»Рµ РїРµСЂРµРјРµС‰РµРЅРёСЏ
                }

                // РћР±СЂР°Р±РѕС‚С‡РёРєРё СЃРѕР±С‹С‚РёР№ РґР»СЏ РїРµСЂРµРјРµС‰РµРЅРёСЏ РїРѕР»Р·СѓРЅРєРѕРІ
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

                // РРЅРёС†РёР°Р»РёР·Р°С†РёСЏ
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

                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const widthToCalc = formWrapper.querySelector('.width-input')?.value || '';
                    const heightToCalc = formWrapper.querySelector('.height-input')?.value || '';
                    const installationType = formWrapper.querySelector('input[name^="widhType"]:checked')?.value || 'inside';
                    const lockType = formWrapper.querySelector('input[name="lock-type"]:checked')?.value || 'sliders';
                    const lockPrice = parseInt(formWrapper.querySelector('input[name="lock-type"]:checked')?.dataset.price || 0, 10);
                    const prodsCouunter = formWrapper.querySelector('.quantity-input')?.value || 1;
                    const prodPriceText = formWrapper.querySelector('.prodForm__price')?.innerText || '';
                    const prodPrice = parseInt(prodPriceText.replace(/\D/g, ''), 10) || 0;
                    const cardCounter = document.querySelector('.header__cartCounter');

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
                                if (cardCounter) {
                                    cardCounter.innerHTML = data.cart_count;
                                }
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
