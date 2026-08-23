<div class="s-populars wrapper blueControls homePopulars">
    <div class="s-populars__title-wrap">
        <h2 class="s-populars__title title"> <span>Популярные товары </span><svg width="114" height="35"
                viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                    stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
            </svg></h2>
        <ul class="s-populars__tabsNav">
            @foreach ($categories as $category)
                <li data-catid="{{ $category->id }}" class="{{ $loop->first ? 'active' : '' }}">
                    <span>{{ $category->titleh1 }}</span>
                    <svg width="52" height="13" viewBox="0 0 52 13" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 8.97127C11.6061 -5.48521 33 3.99996 51 11.4635" stroke="currentColor"
                            stroke-width="2" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                    </svg>
                </li>
            @endforeach
        </ul>
    </div>
    <div class="s-populars__cards">
        <div class="s-populars__swiper swiper">
            <div class="swiper-wrapper" id="products-container">
                @foreach ($homePopulars as $product)
                    <div class="s-populars__slide swiper-slide card" id="prod{{ $product->id }}" data-modelid="{{ $product->model_id ?? '' }}"  data-model="{{ $product->model_title }}" data-cloth="{{ $product->cloth }}"  data-discount="{{ $product->discount }}" data-min-width="{{ $product->min_width ?? '' }}" data-min-height="{{ $product->min_height ?? '' }}">
                        <div class="bigProdCard">
                            <div class="bigProdCard__wrap">
                                <div class="bigProdCard__img-wrap">

                                    <div class="bigProdCard__imgCustomWrap">
                                        @php
                                            $mainImagePath = $product->image_thumb_path ?: $product->image_path;
                                            $fabricImagePath = $product->fabric_thumb_path ?: $product->fabric_photo;
                                        @endphp
                                        @if ($mainImagePath)
                                            <img src="{{ asset($mainImagePath) }}" alt="" loading="lazy" decoding="async" />
                                        @endif

                                        @if ($fabricImagePath)
                                            <img src="{{ asset($fabricImagePath) }}" alt="" loading="lazy" decoding="async" />
                                            
                                        @endif
                                    </div>

                                    <div class="bigProdCard__controls">
                                        <div class="bigProdCard__cart control quickProd" data-prod="{{ $product->id }}" data-modal="#popupProd"><i class="fas fa-cart-arrow-down"></i>
                                            <span class="bigProdCard__toolTip">В корзину</span>
                                        </div>
                                        <div class="bigProdCard__quckView control quickProd"  data-prod="{{$product->id}}" data-modal="#popupProd"><i
                                                class="fas fa-eye"></i>
                                            <span class="bigProdCard__toolTip">Быстрый просмотр</span>
                                        </div>
                                        <button class="bigProdCard__favorites control" type="button"
                                            data-favorite-product="{{ $product->id }}" aria-label="Добавить в избранное"><i class="far fa-heart"></i>
                                            <span class="bigProdCard__toolTip">Добавить в избранное</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="bigProdCard__info">
                                    <a class="bigProdCard__category"
                                        href="{{ \App\Support\CanonicalUrl::route('subcategory.show', ['category_slug' => $product->category->slug, 'subcategory_slug' => $product->subcategory->slug]) }}">{{ $product->category->titleh1 }}</a><a
                                        class="bigProdCard__title"
                                        href="{{ \App\Support\CanonicalUrl::route('product.show', ['category_slug' => $product->category->slug, 'subcategory_slug' => $product->subcategory->slug, 'product_slug' => $product->slug]) }}">{{ $product->h1 }}</a>

                                        @if ($product->min_width || $product->min_height)
                                            <div class="bigProdCard__meta">
                                                От
                                                @if ($product->min_width)
                                                    {{ $product->min_width }} мм
                                                @endif
                                                @if ($product->min_width && $product->min_height)
                                                    x
                                                @endif
                                                @if ($product->min_height)
                                                    {{ $product->min_height }} мм
                                                @endif
                                            </div>
                                        @endif

                                        <div class="bigProdCard__priceWrap">
                                            @php
                                                $minPrice = (float) ($product->min_price ?? 0);
                                                $discount = (float) ($product->discount ?? 0);
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
    </div>
    <div class="swiper-pagination"></div>
    <div class="swiper-button-prev"></div>
    <div class="swiper-button-next"> </div>
</div>
