<div class="s-actions wrapper blueControls">
    <div class="s-actions__title-wrap">
        <h2 class="s-actions__title title"> <span>Акции </span><svg width="114" height="35" viewBox="0 0 114 35"
                fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                    stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
            </svg></h2><a class="s-actions__more btn" href=""> <span>Подробнее</span><i
                class="fas fa-arrow-right"></i></a>
    </div>
    <div class="s-actions__cards">
        <div class="s-actions__swiper swiper">
            <div class="swiper-wrapper">
                @foreach ($homeActions as $actionCard)
                    <div class="s-actions__slide swiper-slide card" id="prod{{ $actionCard->id }}"
                        data-modelid="{{ $actionCard->model_id ?? '' }}" data-model="{{ $actionCard->model_title }}"
                        data-cloth="{{ $actionCard->cloth }}" data-discount="{{ $actionCard->discount }}"
                        data-min-width="{{ $actionCard->min_width ?? '' }}" data-min-height="{{ $actionCard->min_height ?? '' }}">
                        <div class="bigProdCard">
                            <div class="bigProdCard__wrap">
                                <div class="bigProdCard__img-wrap">

                                    <div class="bigProdCard__imgCustomWrap">
                                        @php
                                            $mainImagePath = $actionCard->image_thumb_path ?: $actionCard->image_path;
                                            $fabricImagePath = $actionCard->fabric_thumb_path ?: $actionCard->fabric_photo;
                                        @endphp
                                        @if ($mainImagePath)
                                            <img src="{{ asset($mainImagePath) }}" alt="" />
                                        @endif

                                        @if ($fabricImagePath)
                                            <img src="{{ asset($fabricImagePath) }}" alt="" />
                                            
                                        @endif
                                    </div>

                  

                                    <div class="bigProdCard__controls">
                                        {{-- <div class="bigProdCard__cart control"><i class="fas fa-cart-arrow-down"></i>
                                            <div class="bigProdCard__toolTip">В корзину</div>
                                        </div> --}}
                                        <div class="bigProdCard__cart control quickProd"
                                            data-prod="{{ $actionCard->id }}" data-modal="#popupProd"><i
                                                class="fas fa-eye"></i>
                                            <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                        </div>
                                        <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                            <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bigProdCard__info">
                                    <a class="bigProdCard__category"
                                        href="{{ route('subcategory.show', ['category_slug' => $actionCard->category->slug, 'subcategory_slug' => $actionCard->subcategory->slug]) }}">{{ $actionCard->category->titleh1 }}</a><a
                                        class="bigProdCard__title"
                                        href="{{ route('product.show', ['category_slug' => $actionCard->category->slug, 'subcategory_slug' => $actionCard->subcategory->slug, 'product_slug' => $actionCard->slug]) }}/">{{ $actionCard->h1 }}</a>

                                    @if ($actionCard->min_width || $actionCard->min_height)
                                        <div class="bigProdCard__meta">
                                            От
                                            @if ($actionCard->min_width)
                                                {{ $actionCard->min_width }} мм
                                            @endif
                                            @if ($actionCard->min_width && $actionCard->min_height)
                                                x
                                            @endif
                                            @if ($actionCard->min_height)
                                                {{ $actionCard->min_height }} мм
                                            @endif
                                        </div>
                                    @endif

                                    <div class="bigProdCard__priceWrap">
                                        @php
                                            $minPrice = (float) ($actionCard->min_price ?? 0);
                                            $discount = (float) ($actionCard->discount ?? 0);
                                            $discountedPrice = floor($minPrice * (1 - $discount / 100));
                                        @endphp

                                        @if ($minPrice > 0 && $discount > 0)
                                            <span class="normalPrice">{{ number_format($minPrice, 0, '', ' ') }}₽</span>
                                            <span class="discount">{{ number_format($discountedPrice, 0, '', ' ') }}₽</span>
                                        @elseif ($minPrice > 0)
                                            <span class="discount">{{ number_format($minPrice, 0, '', ' ') }}₽</span>
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
        <div class="swiper-pagination"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
    </div>
</div>
