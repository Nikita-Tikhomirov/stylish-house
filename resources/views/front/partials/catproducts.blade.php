@foreach ($filterProduts as $prod)
    <div class="bigProdCard card" data-color="{{ $prod->color }}" id="prod{{ $prod->id }}" data-modelid="{{ $prod->model_id ?? '' }}"
        data-model="{{ $prod->model_title }}" data-cloth="{{ $prod->cloth }}" data-discount="{{ $prod->discount }}">
        <div class="bigProdCard__wrap">
            <div class="bigProdCard__img-wrap">
                <div class="bigProdCard__imgCustomWrap">
                    @php
                        $mainImagePath = $prod->image_thumb_path ?: $prod->image_path;
                        $fabricImagePath = $prod->fabric_thumb_path ?: $prod->fabric_photo;
                    @endphp

                    @if ($mainImagePath)
                        <img src="{{ asset($mainImagePath) }}" alt="" />
                    @endif

                    @if ($fabricImagePath)
                        <img src="{{ asset($fabricImagePath) }}" alt="" />

                    @endif
                </div>
                <div class="bigProdCard__controls">
                    <div class="bigProdCard__cart control"><i class="fas fa-cart-arrow-down"></i>
                        <div class="bigProdCard__toolTip">В корзину</div>
                    </div>
                    <div class="bigProdCard__quckView control quickProd" data-modal="#popupProd"
                        data-prod="{{ $prod->id }}"><i class="fas fa-eye"></i>
                        <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                    </div>
                    <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                        <div class="bigProdCard__toolTip">Добавить в избранное</div>
                    </div>
                </div>
            </div>
            <div class="bigProdCard__info">
                <a class="bigProdCard__category"
                    href="{{ route('subcategory.show', ['category_slug' => $prod->category->slug, 'subcategory_slug' => $prod->subcategory->slug]) }}">{{ $prod->category->titleh1 }}</a><a
                    class="bigProdCard__title"
                    href="{{ route('product.show', ['category_slug' => $prod->category->slug, 'subcategory_slug' => $prod->subcategory->slug, 'product_slug' => $prod->slug]) }}/">{{ $prod->h1 }}</a>
                <div class="bigProdCard__priceWrap">
                    @if ($prod->discount)
                        <span class="normalPrice">1000₽</span>
                    @endif

                    <span class="discount">500₽</span>
                </div>
            </div>
        </div>
    </div>
@endforeach
