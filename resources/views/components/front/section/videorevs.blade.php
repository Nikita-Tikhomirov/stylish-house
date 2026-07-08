<section class="s-videoRevs wrapper blueControls">
    <h2 class="s-videoRevs__title title"> <span>Видеообзор Категории {{$category->titleh1}}</span><svg width="114" height="35"
            viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4"
                stroke-miterlimit="3.8637" stroke-linecap="round"></path>
        </svg></h2>
    <div class="s-videoRevs__cards">
        <div class="s-videoRevs__swiper swiper">
            <div class="swiper-wrapper">

                @foreach ($videoreviews as $rev)
                    @if ($rev->category_id == $category->id && !$rev->subcategory_id)
                    <div class="s-videoRevs__slide swiper-slide">
                        <div class="videoCard">
                            <div class="videoCard__imgWrap">
                                <div class="videoCard__date">{{$rev->created_at}}</div>
                                @if ($rev->cover_image)
                                    <div class="videoCard__img"> <img src="{{ Storage::url($rev->cover_image) }}" alt="{{ $rev->title }}" /></div>
                                @else
                                    <video class="videoCard__video" preload="metadata" muted playsinline>
                                        <source src="{{ Storage::url($rev->video) }}" type="video/mp4">
                                    </video>
                                @endif
                                <a data-fslightbox="videoRevs" href="{{ Storage::url($rev->video) }}" class="videoCard__playBtn"> <i class="fas fa-play"></i></a>
                            </div>
                            <div class="videoCard__info">
                                <div class="videoCard__title">{{$rev->title}}</div>
                                <div class="videoCard__text">{{$rev->description}}</div>
                            </div>
                        </div>
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
