<section class="s-subcatProducts wrapper blueControls">
    <h2 class="s-subcatProducts__title title"> <span>Товары по подкатегориям</span><svg width="114" height="35"
            viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4"
                stroke-miterlimit="3.8637" stroke-linecap="round"> </path>
        </svg></h2>

    @foreach ($subcategoriesWithProducts as $subcategory)
        @if($subcategory->show_in_more_cats && $subcategory->products_for_slider->count() > 0)
            <div class="s-subcatProducts__block">
                <div class="s-subcatProducts__header">
                    <h3 class="s-subcatProducts__subtitle">
                        <a href="{{ route('subcategory.show', ['category_slug' => $category->slug, 'subcategory_slug' => $subcategory->slug]) }}/">
                            {{ $subcategory->titleh1 }}
                        </a>
                    </h3>
                    <a href="{{ route('subcategory.show', ['category_slug' => $category->slug, 'subcategory_slug' => $subcategory->slug]) }}/" 
                       class="s-subcatProducts__link">
                        Смотреть все товары
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4.16667 10H15.8333M15.8333 10L10 4.16667M15.8333 10L10 15.8333" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
                
                <div class="s-subcatProducts__cards">
                    <div class="s-subcatProducts__swiper swiper">
                        <div class="swiper-wrapper">
                            @foreach ($subcategory->products_for_slider as $product)
                                <div class="s-subcatProducts__slide swiper-slide card" 
                                     id="prod{{ $product->id }}" 
                                     data-modelid="{{ $product->model_id ?? '' }}"  
                                     data-model="{{ $product->model_title }}" 
                                     data-cloth="{{ $product->cloth }}"  
                                     data-discount="{{ $product->discount }}">
                                    <div class="bigProdCard">
                                        <div class="bigProdCard__wrap">
                                            <div class="bigProdCard__img-wrap">
                                                <div class="bigProdCard__imgCustomWrap">
                                                    @isset($product->image_path)
                                                        <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->h1 }}" />
                                                    @endisset

                                                    @isset($product->fabric_photo)
                                                        <img src="{{ Storage::url($product->fabric_photo) }}" alt="{{ $product->h1 }}" />
                                                    @endisset
                                                </div>

                                                <div class="bigProdCard__controls">
                                                    <div class="bigProdCard__cart control">
                                                        <i class="fas fa-cart-arrow-down"></i>
                                                        <div class="bigProdCard__toolTip">В корзину</div>
                                                    </div>
                                                    <div class="bigProdCard__quckView control quickProd"  
                                                         data-prod="{{$product->id}}" data-modal="#popupProd">
                                                        <i class="fas fa-eye"></i>
                                                        <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                                    </div>
                                                    <div class="bigProdCard__favorites control">
                                                        <i class="far fa-heart"></i>
                                                        <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bigProdCard__info">
                                                <a class="bigProdCard__category"
                                                   href="{{ route('subcategory.show', ['category_slug' => $product->category->slug, 'subcategory_slug' => $product->subcategory->slug]) }}">
                                                    {{ $product->subcategory->titleh1 }}
                                                </a>
                                                <a class="bigProdCard__title"
                                                   href="{{ route('product.show', ['category_slug' => $product->category->slug, 'subcategory_slug' => $product->subcategory->slug, 'product_slug' => $product->slug]) }}/">
                                                    {{ $product->h1 }}
                                                </a>

                                                <div class="bigProdCard__priceWrap">
                                                    @if ($product->discount > 0)
                                                        <span class="normalPrice">{{ $product->old_price ?? $product->price }}₽</span>
                                                        <span class="discount">{{ $product->price }}₽</span>
                                                    @else
                                                        <span class="discount">{{ $product->price }}₽</span>
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
        @endif
    @endforeach
</section>

<style>
.s-subcatProducts {
    margin-bottom: 80px;
}

.s-subcatProducts__title {
    margin-bottom: 40px;
}

.s-subcatProducts__block {
    margin-bottom: 60px;
}

.s-subcatProducts__block:last-child {
    margin-bottom: 0;
}

.s-subcatProducts__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.s-subcatProducts__subtitle {
    font-size: 24px;
    font-weight: 600;
    margin: 0;
}

.s-subcatProducts__subtitle a {
    color: inherit;
    text-decoration: none;
    transition: color 0.3s ease;
}

.s-subcatProducts__subtitle a:hover {
    color: var(--accent-color, #007bff);
}

.s-subcatProducts__link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--accent-color, #007bff);
    text-decoration: none;
    font-weight: 500;
    transition: gap 0.3s ease;
}

.s-subcatProducts__link:hover {
    gap: 12px;
}

.s-subcatProducts__link svg {
    transition: transform 0.3s ease;
}

.s-subcatProducts__link:hover svg {
    transform: translateX(2px);
}

.s-subcatProducts__cards {
    position: relative;
}

.s-subcatProducts__swiper {
    overflow: hidden;
}

.s-subcatProducts__slide {
    width: auto;
}

@media (max-width: 768px) {
    .s-subcatProducts__header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .s-subcatProducts__subtitle {
        font-size: 20px;
    }
}
</style>
