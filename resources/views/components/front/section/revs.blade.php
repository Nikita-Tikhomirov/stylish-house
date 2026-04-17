<div class="s-revs wrapper blueControls">
    <h2 class="s-revs__title title"> <span>Отзывы</span><svg width="114" height="35" viewBox="0 0 114 35"
            fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4"
                stroke-miterlimit="3.8637" stroke-linecap="round"></path>
        </svg></h2>
    <div class="s-revs__cards">
        <div class="s-revs__swiper swiper">
            <div class="swiper-wrapper">
                @foreach ($reviews as $rev)
                    <div class="revCard swiper-slide">
                        <div class="revCard__wrap">
                            <div class="revCard__avatar">
                                <img src="{{ Storage::url($rev->avatar) }}" alt="" />

                            </div>
                            <div class="revCard__name">{{ $rev->title }}</div>
                        </div><i class="far fa-comments"></i>
                        <div class="revCard__text">{{ $rev->text }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="swiper-pagination"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"> </div>
    </div>
</div>
