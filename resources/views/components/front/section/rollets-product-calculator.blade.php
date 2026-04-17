<section class="prodMain wrapper catCalculator" style="padding-top: 40px;">
    <h2 class="prodMain__title title"> <span>Р Р°СЃСЃС‡РёС‚Р°С‚СЊ СЃС‚РѕРёРјРѕСЃС‚СЊ СЂРѕР»СЊСЃС‚Р°РІРµРЅ</span><svg width="114" height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
        </svg></h2>

    <div class="prodForm">
        <div class="prodForm__galleryWrapOuter">
            <div class="prodForm__galleryWrap">
                <div class="prodForm__imgWrap">
                    @if (!empty($product->image_path))
                        <img src="{{ asset(rawurlencode($product->image_path)) }}" alt="{{ $product->h1 }}" />
                    @endif
                    @if (!empty($product->fabric_photo))
                        <img src="{{ asset(rawurlencode($product->fabric_photo)) }}" alt="{{ $product->h1 }} РјР°С‚РµСЂРёР°Р»" />
                    @endif
                </div>

                <div class="prodForm__bar">
                    @foreach ($sameModelProducts as $sameProduct)
                        @if ($sameProduct->image_path)
                            <img src="{{ asset(rawurlencode($sameProduct->image_path)) }}" alt="{{ $sameProduct->h1 }}" />
                        @elseif($sameProduct->fabric_photo)
                            <img src="{{ asset(rawurlencode($sameProduct->fabric_photo)) }}" alt="{{ $sameProduct->h1 }} РјР°С‚РµСЂРёР°Р»" />
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="prodForm__calcFormWrap">
            <div class="prodForm__formSubtitle">{{ $category->titleh1 ?? 'Р РѕР»СЊСЃС‚Р°РІРЅРё' }}</div>
            <div class="prodForm__formTitle">{{ $product->h1 ?? 'Р РѕР»СЊСЃС‚Р°РІРЅРё' }}</div>
            <div class="prodForm__description">
                <p>{{ $product->first_screenn_description ?? '' }}</p><span class="more">РџРѕРґСЂРѕР±РЅРµРµ</span>
            </div>

            <input type="hidden" name="modelSelect" class="modelSelect" value="{{ $product->model_title ?? ($product->h1 ?? 'Р РѕР»СЊСЃС‚Р°РІРЅРё') }}">
            <input type="hidden" name="cloth" class="cloth" value="{{ $product->cloth ?? '' }}">
            <input type="hidden" name="model" class="model" value="{{ $product->model_title ?? ($product->h1 ?? 'Р РѕР»СЊСЃС‚Р°РІРЅРё') }}">
            <input type="hidden" class="discount" value="{{ $product->discount ?? 0 }}">

            <div class="prodForm__sizeWrap">
                <label class="prodForm__label">
                    <p>РЁРёСЂРёРЅР°, РјРј</p>
                    <input class="prodForm__input width-input" type="number" name="width" value="{{ $product->min_width ?: 500 }}" required />
                </label>
                <label class="prodForm__label">
                    <p>Р’С‹СЃРѕС‚Р°, РјРј</p>
                    <input class="prodForm__input height-input" type="number" name="height" value="{{ $product->min_height ?: 500 }}" required />
                </label>
            </div>

            <div class="product-params-accordion" style="margin-top: 20px;">
                <div class="cartForm__optionsList">
                    <div class="cartForm__listTitle">РҐР°СЂР°РєС‚РµСЂРёСЃС‚РёРєРё С‚РѕРІР°СЂР°</div>

                    <div class="accordion-item">
                        <div class="accordion-header">
                            <h4>Р’РёРґ РјРѕРЅС‚Р°Р¶Р°</h4>
                            <span class="accordion-arrow">в–ј</span>
                        </div>
                        <div class="accordion-content">
                            <label class="option-label">
                                <input type="radio" name="installation-type" value="overhead" data-price="{{ $product->overhead_price ?? 0 }}" {{ $product->installation_type == 'overhead' ? 'checked' : '' }}>
                                <span class="option-name">РќР°РєР»Р°РґРЅРѕР№ РјРѕРЅС‚Р°Р¶</span>
                                <span class="option-price">+{{ $product->overhead_price ?? 0 }}в‚Ѕ</span>
                            </label>
                            <label class="option-label">
                                <input type="radio" name="installation-type" value="built-in" data-price="{{ $product->builtin_price ?? 0 }}" {{ $product->installation_type == 'built-in' ? 'checked' : '' }}>
                                <span class="option-name">Р’СЃС‚СЂРѕРµРЅРЅС‹Р№ РјРѕРЅС‚Р°Р¶</span>
                                <span class="option-price">+{{ $product->builtin_price ?? 0 }}в‚Ѕ</span>
                            </label>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <div class="accordion-header">
                            <h4>РўРёРї СѓРїСЂР°РІР»РµРЅРёСЏ</h4>
                            <span class="accordion-arrow">в–ј</span>
                        </div>
                        <div class="accordion-content">
                            <label class="option-label">
                                <input type="radio" name="control-type" value="strap" data-price="{{ $product->strap_price ?? 0 }}" {{ $product->control_type == 'strap' ? 'checked' : '' }}>
                                <span class="option-name">Р›РµРЅС‚РѕС‡РЅС‹Р№ / С€РЅСѓСЂРѕРІРѕР№</span>
                                <span class="option-price">+{{ $product->strap_price ?? 0 }}в‚Ѕ</span>
                            </label>
                            <label class="option-label">
                                <input type="radio" name="control-type" value="cardan" data-price="{{ $product->cardan_price ?? 0 }}" {{ $product->control_type == 'cardan' ? 'checked' : '' }}>
                                <span class="option-name">Р’РѕСЂРѕС‚РєРѕРІС‹Р№ (РєР°СЂРґР°РЅ)</span>
                                <span class="option-price">+{{ $product->cardan_price ?? 0 }}в‚Ѕ</span>
                            </label>
                            <label class="option-label">
                                <input type="radio" name="control-type" value="pim" data-price="{{ $product->pim_price ?? 0 }}" {{ $product->control_type == 'pim' ? 'checked' : '' }}>
                                <span class="option-name">РџСЂСѓР¶РёРЅРЅРѕ-РёРЅРµСЂС†РёРѕРЅРЅС‹Р№ РјРµС…Р°РЅРёР·Рј</span>
                                <span class="option-price">+{{ $product->pim_price ?? 0 }}в‚Ѕ</span>
                            </label>
                            <label class="option-label">
                                <input type="radio" name="control-type" value="electric" data-price="{{ $product->electric_price ?? 0 }}" {{ $product->control_type == 'electric' ? 'checked' : '' }}>
                                <span class="option-name">Р­Р»РµРєС‚СЂРѕРїСЂРёРІРѕРґ</span>
                                <span class="option-price">+{{ $product->electric_price ?? 0 }}в‚Ѕ</span>
                            </label>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <div class="accordion-header">
                            <h4>Р‘Р»РѕРєРёСЂСѓСЋС‰РёРµ СѓСЃС‚СЂРѕР№СЃС‚РІР°</h4>
                            <span class="accordion-arrow">в–ј</span>
                        </div>
                        <div class="accordion-content">
                            <label class="option-label">
                                <input type="radio" name="lock-device" value="rigel" data-price="{{ $product->rigel_price ?? 0 }}" {{ $product->lock_device == 'rigel' ? 'checked' : '' }}>
                                <span class="option-name">Р РёРіРµР»СЊРЅС‹Р№ Р·Р°РјРѕРє</span>
                                <span class="option-price">+{{ $product->rigel_price ?? 0 }}в‚Ѕ</span>
                            </label>
                            <label class="option-label">
                                <input type="radio" name="lock-device" value="shchyolka" data-price="{{ $product->shchyolka_price ?? 0 }}" {{ $product->lock_device == 'shchyolka' ? 'checked' : '' }}>
                                <span class="option-name">Р©РµРєРѕР»РґР°</span>
                                <span class="option-price">+{{ $product->shchyolka_price ?? 0 }}в‚Ѕ</span>
                            </label>
                            <label class="option-label">
                                <input type="radio" name="lock-device" value="upper" data-price="{{ $product->upper_price ?? 0 }}" {{ $product->lock_device == 'upper' ? 'checked' : '' }}>
                                <span class="option-name">Р’РµСЂС…РЅРёР№ СЂРёРіРµР»СЊ</span>
                                <span class="option-price">+{{ $product->upper_price ?? 0 }}в‚Ѕ</span>
                            </label>
                            <label class="option-label">
                                <input type="radio" name="lock-device" value="none" data-price="0" {{ $product->lock_device == 'none' ? 'checked' : '' }}>
                                <span class="option-name">Р‘РµР· Р±Р»РѕРєРёСЂРѕРІРєРё</span>
                                <span class="option-price">+0в‚Ѕ</span>
                            </label>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <div class="accordion-header">
                            <h4>Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ РѕРїС†РёРё</h4>
                            <span class="accordion-arrow">в–ј</span>
                        </div>
                        <div class="accordion-content">
                            <label class="option-label">
                                <input type="checkbox" name="ral-paint" value="ral-paint" data-price="{{ $product->ral_price ?? 0 }}" {{ $product->ral_paint ? 'checked' : '' }}>
                                <span class="option-name">РџРѕРєСЂР°СЃРєР° РїРѕ RAL</span>
                                <span class="option-price">+{{ $product->ral_price ?? 0 }}в‚Ѕ</span>
                            </label>
                            <label class="option-label">
                                <input type="checkbox" name="photo-print" value="photo-print" data-price="{{ $product->photo_price ?? 0 }}" {{ $product->photo_print ? 'checked' : '' }}>
                                <span class="option-name">Р¤РѕС‚РѕРїРµС‡Р°С‚СЊ</span>
                                <span class="option-price">+{{ $product->photo_price ?? 0 }}в‚Ѕ</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <meta name="csrf-token" content="{{ csrf_token() }}">

            <div class="prodForm__howMany"> <button class="minus">-</button><input type="text" class="quantity-input" placeholder="1" value="1" /><button class="plus">+</button></div>
            <div class="prodForm__priceAndAddToCart">
                <div class="prodForm__price">Р¦РµРЅР°: 0в‚Ѕ</div>
                <button class="prodForm__addToCart" data-id="{{ $product->id }}"> Р”РѕР±Р°РІРёС‚СЊ РІ РєРѕСЂР·РёРЅСѓ </button>
            </div>
        </div>
    </div>
</section>

