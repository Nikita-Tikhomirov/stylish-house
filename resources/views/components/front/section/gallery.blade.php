<section class="s-gallery wrapper blueControls">
    <div class="s-gallery__title-wrap">
        <h2 class="s-gallery__title title"> <span>Примеры работ</span><svg width="114" height="35"
                viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                    stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
            </svg></h2><a class="s-gallery__more btn" href=""> <span>Подробнее</span><i
                class="fas fa-arrow-right"></i></a>
    </div>
    <div class="s-gallery__cards">
        <div class="s-gallery__swiper swiper">
            <div class="swiper-wrapper">

                @foreach ($gallerys as $gallery)
                    @if ($gallery->category_id == $category->id && !$gallery->subcategory_id)
                        <div class="s-gallery__slide swiper-slide">
                            <a data-fslightbox="gallery" class="galleryCard" href="{{ Storage::url($gallery->image) }}">
                                <img src="{{ Storage::url($gallery->image) }}" alt="" />
                                <span class="galleryCard__icon">
                                    <i class="fas fa-camera"></i>
                                </span>
                            </a>
                        </div>
                    @endif
                @endforeach


            </div>
        </div>
        <div class="swiper-pagination"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"> </div>
    </div>
</section>


