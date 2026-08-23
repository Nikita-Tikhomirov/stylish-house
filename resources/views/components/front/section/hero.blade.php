<section class="hero blueControls">
    <div class="hero__slider swiper">
        <div class="swiper-wrapper">

            @foreach ( $mainSlider as $slide)
                <div class="swiper-slide wrapper">
                    <div class="hero__bg">
                        <img src="{{ Storage::url($slide->image_path) }}" alt="" class="hero__bg">
                    </div>
                    <h2 class="hero__title">{{ $slide->title }}</h2>
                    <div class="hero__text">
                        {{$slide->description}}
                    </div>
                    <a href="{{ \App\Support\CanonicalUrl::to($slide->link) }}" class="hero__link btn"> <span>Подробнее</span><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            @endforeach




        </div>

        <div class="swiper-pagination"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>

    </div>

</section>




{{--
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const hero = new Swiper('.hero__slider', {
            // Optional parameters
            loop: true,

            // If we need pagination
            pagination: {
                el: '.hero__slider .swiper-pagination',
            },

            // Navigation arrows
            navigation: {
                nextEl: '.hero__slider .swiper-button-next',
                prevEl: '.hero__slider .swiper-button-prev',
            },

        });
    })
</script> --}}
