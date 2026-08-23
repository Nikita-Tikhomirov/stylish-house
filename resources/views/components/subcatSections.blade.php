<section class="s-subcatSections wrapper blueControls">
    @foreach ($subcategoriesWithProducts as $subcatData)
        @if($subcatData['subcategory']->show_in_more_cats && $subcatData['products']->count() > 0)
            <div class="s-subcatSections__section">
                <h2 class="s-subcatSections__title">
                    {{ $subcatData['subcategory']->titleh1 }}
                </h2>
                
                <div class="s-subcatSections__slider">
                    <div class="s-subcatSections__swiper swiper">
                        <div class="swiper-wrapper">
                            @foreach ($subcatData['products'] as $product)
                                <div class="s-subcatSections__slide swiper-slide card" 
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
                                                        <span class="bigProdCard__toolTip">В корзину</span>
                                                    </div>
                                                    <div class="bigProdCard__quckView control quickProd"  
                                                         data-prod="{{$product->id }}" data-modal="#popupProd">
                                                        <i class="fas fa-eye"></i>
                                                        <span class="bigProdCard__toolTip">Быстрый просмотр</span>
                                                    </div>
                                                    <button class="bigProdCard__favorites control" type="button"
                                                        data-favorite-product="{{ $product->id }}" aria-label="Добавить в избранное">
                                                        <i class="far fa-heart"></i>
                                                        <span class="bigProdCard__toolTip">Добавить в избранное</span>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="bigProdCard__info">
                                                <a class="bigProdCard__category"
                                                   href="{{ \App\Support\CanonicalUrl::route('subcategory.show', ['category_slug' => $product->category->slug, 'subcategory_slug' => $product->subcategory->slug]) }}">
                                                    {{ $product->subcategory->titleh1 }}
                                                </a>
                                                <a class="bigProdCard__title"
                                                   href="{{ \App\Support\CanonicalUrl::route('product.show', ['category_slug' => $product->category->slug, 'subcategory_slug' => $product->subcategory->slug, 'product_slug' => $product->slug]) }}">
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
                
                <div class="s-subcatSections__buttons">
                    <a href="#" class="s-subcatSections__button s-subcatSections__button--primary">
                        <i class="fas fa-calculator"></i>
                        Рассчитать стоимость
                    </a>
                    <a href="#" class="s-subcatSections__button s-subcatSections__button--secondary">
                        <i class="fas fa-ruler"></i>
                        Бесплатный замер
                    </a>
                    <a href="{{ \App\Support\CanonicalUrl::route('subcategory.show', ['category_slug' => $category->slug, 'subcategory_slug' => $subcatData['subcategory']->slug]) }}" class="s-subcatSections__button s-subcatSections__button--catalog">
                        <i class="fas fa-th"></i>
                        Каталог
                    </a>
                </div>
            </div>
        @endif
    @endforeach
</section>

<style>
.s-subcatSections {
    margin-bottom: 80px;
}

.s-subcatSections__section {
    margin-bottom: 60px;
}

.s-subcatSections__section:last-child {
    margin-bottom: 0;
}

.s-subcatSections__title {
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 30px;
    color: #333;
}

.s-subcatSections__slider {
    position: relative;
    margin-bottom: 30px;
}

.s-subcatSections__swiper {
    overflow: hidden;
}

.s-subcatSections__slide {
    width: auto;
}

.s-subcatSections__buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.s-subcatSections__button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.s-subcatSections__button--primary {
    background-color: #007bff;
    color: white;
}

.s-subcatSections__button--primary:hover {
    background-color: #0056b3;
}

.s-subcatSections__button--secondary {
    background-color: #6c757d;
    color: white;
}

.s-subcatSections__button--secondary:hover {
    background-color: #545b62;
}

.s-subcatSections__button--catalog {
    background-color: #28a745;
    color: white;
}

.s-subcatSections__button--catalog:hover {
    background-color: #1e7e34;
}

@media (max-width: 768px) {
    .s-subcatSections__title {
        font-size: 24px;
    }
    
    .s-subcatSections__buttons {
        flex-direction: column;
        gap: 10px;
    }
    
    .s-subcatSections__button {
        justify-content: center;
        width: 100%;
    }
}
</style>
