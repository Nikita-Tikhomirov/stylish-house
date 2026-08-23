@foreach ($filterProduts as $prod)
    <div class="bigProdCard card" data-color="{{ $prod->color }}" id="prod{{ $prod->id }}" data-modelid="{{ $prod->model_id ?? '' }}"
        data-model="{{ $prod->model_title }}" data-cloth="{{ $prod->cloth }}" data-discount="{{ $prod->discount }}"
        data-width="{{ $prod->min_width }}" data-min-width="{{ $prod->min_width }}" data-min-height="{{ $prod->min_height }}">
        <div class="bigProdCard__wrap">
            <div class="bigProdCard__img-wrap">
                <div class="bigProdCard__imgCustomWrap">
                    @php
                        $mainImagePath = $prod->image_thumb_path ?: $prod->image_path;
                        $fabricImagePath = $prod->fabric_thumb_path ?: $prod->fabric_photo;
                    @endphp

                    @if ($mainImagePath)
                        <img src="{{ asset($mainImagePath) }}" alt="" loading="lazy" decoding="async" />
                    @endif

                    @if ($fabricImagePath)
                        <img src="{{ asset($fabricImagePath) }}" alt="" loading="lazy" decoding="async" />
                        
                    @endif
                </div>



                <div class="bigProdCard__controls">
                    <div class="bigProdCard__cart control"><i class="fas fa-cart-arrow-down"></i>
                        <span class="bigProdCard__toolTip">В корзину</span>
                    </div>
                    <div class="bigProdCard__quckView control quickProd" data-modal="#popupProd"
                        data-prod="{{ $prod->id }}"><i class="fas fa-eye"></i>
                        <span class="bigProdCard__toolTip">Быстрый просмотр</span>
                    </div>
                    <button class="bigProdCard__favorites control" type="button"
                        data-favorite-product="{{ $prod->id }}" aria-label="Добавить в избранное">
                        <i class="far fa-heart"></i>
                        <span class="bigProdCard__toolTip">Добавить в избранное</span>
                    </button>
                </div>
            </div>
            <div class="bigProdCard__info">
                <a class="bigProdCard__category"
                    href="{{ \App\Support\CanonicalUrl::route('subcategory.show', ['category_slug' => $prod->category->slug, 'subcategory_slug' => $prod->subcategory->slug]) }}">{{ $prod->category->titleh1 }}</a><a
                    class="bigProdCard__title"
                    href="{{ \App\Support\CanonicalUrl::route('product.show', ['category_slug' => $prod->category->slug, 'subcategory_slug' => $prod->subcategory->slug, 'product_slug' => $prod->slug]) }}">{{ $prod->h1 }}</a>
                @if ($prod->min_width || $prod->min_height)
                    <div class="bigProdCard__meta">
                        От
                        @if ($prod->min_width)
                            {{ $prod->min_width }} мм
                        @endif
                        @if ($prod->min_width && $prod->min_height)
                            x
                        @endif
                        @if ($prod->min_height)
                            {{ $prod->min_height }} мм
                        @endif
                    </div>
                @endif
                <div class="bigProdCard__priceWrap">
                    @php
                        $minPrice = (float) ($prod->min_price ?? 0);
                        $discount = (float) ($prod->discount ?? 0);
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
@endforeach
