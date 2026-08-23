<section class="s-subcats wrapper blueControls">
    <h2 class="s-subcats__title title"> <span>{{ $category->subcat_title }}</span><svg width="114" height="35"
            viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4"
                stroke-miterlimit="3.8637" stroke-linecap="round"> </path>
        </svg></h2>
    <div class="s-subcats__cards">
        <div class="s-subcats__swiper swiper">
            <div class="swiper-wrapper">

                @foreach ($subcats->subcategories as $subcategory)
                    @if($subcategory->show_in_more_cats)
                        <a href="{{ \App\Support\CanonicalUrl::route('subcategory.show', ['category_slug' => $subcats->slug, 'subcategory_slug' => $subcategory->slug]) }}"
                            class="s-subcats__slide swiper-slide card">
                            <div class="bigProdCard bigCatCard">
                                <div class="bigProdCard__wrap">
                                    <div class="bigProdCard__img-wrap"><img src="{{ Storage::url($subcategory->img) }}"
                                            alt="" loading="lazy" decoding="async" />
                                        <div class="bigProdCard__controls">
                                            <div class="bigProdCard__cart"> </div>
                                            <div class="bigProdCard__quckView"> </div>
                                            <div class="bigProdCard__favorites"></div>
                                        </div>
                                    </div>
                                    <div class="bigProdCard__info">
                                        <div class="bigProdCard__title">{{ $subcategory->titleh1 }}</div>
                                        <div class="bigProdCard__priceWrap"> <span>Товаров в подкатегории:
                                                {{ $subcategory->products_count }}</span></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endif
                @endforeach





            </div>
        </div>
        <div class="swiper-pagination"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"> </div>
    </div>
</section>
