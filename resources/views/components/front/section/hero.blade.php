<section class="hero blueControls">
    <div class="hero__slider swiper">
        <div class="swiper-wrapper">

            @foreach ( $mainSlider as $slide)
                <div class="swiper-slide wrapper">
                    <div class="hero__bg">
                        <img src="{{ Storage::url($slide->image_path) }}" alt="" class="hero__bg">
                    </div>
                    <div class="hero__title">
                        {{$slide->title}}
                    </div>
                    <div class="hero__text">
                        {{$slide->description}}
                    </div>
                    <a href="{{$slide->link}}" class="hero__link btn"> <span>Подробнее</span><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            @endforeach




        </div>

        <div class="swiper-pagination"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>

    </div>

</section>


<style>
    .hero .swiper-slide{
        padding-top: 70px;
        padding-bottom: 70px;
        position: relative;
        z-index: 3;
        min-height: 450px;
    }
    .hero__bg{
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;

    }
    .hero__bg img{
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .hero__title,.hero__text,.hero__link{
        position: relative;
        z-index: 4;
        max-width: 650px;
    }

    .hero__link{
        width: fit-content;
    }
    .hero__title{
        margin-bottom: 18px;
        font-size: 48px;
        font-weight: 600;
    }
    .hero__text{
        margin-bottom: 67px;
        max-width: 350px;
    }

</style>

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
