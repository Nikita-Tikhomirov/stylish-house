<section class="sitePortfolio">
    @if ($videoReviews->isNotEmpty())
        <div class="sitePortfolio__block blueControls">
            <h2 class="sitePortfolio__heading">Видеообзоры нашей продукции</h2>
            <div class="sitePortfolio__slider">
                <div class="sitePortfolio__swiper swiper">
                    <div class="swiper-wrapper">
                        @foreach ($videoReviews as $video)
                            @php
                                $isExternalVideo = \Illuminate\Support\Str::startsWith($video->video, ['http://', 'https://']);
                                $videoUrl = $isExternalVideo ? $video->video : Storage::url($video->video);
                                $embedUrl = $videoUrl;
                                if (preg_match('~drive\.google\.com/file/d/([^/]+)~', $videoUrl, $matches)) {
                                    $embedUrl = 'https://drive.google.com/file/d/' . $matches[1] . '/preview';
                                }
                                $poster = $video->cover_image ? Storage::url($video->cover_image) : null;
                            @endphp
                            <div class="swiper-slide">
                                <article class="sitePortfolio__video">
                                    <a class="sitePortfolio__videoPreview" data-fslightbox="portfolioVideos" href="{{ $embedUrl }}">
                                        @if ($poster)
                                            <img src="{{ $poster }}" alt="{{ $video->title }}">
                                        @elseif ($isExternalVideo)
                                            <iframe src="{{ $embedUrl }}" loading="lazy" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                                        @else
                                            <video preload="metadata" muted playsinline>
                                                <source src="{{ $videoUrl }}" type="video/mp4">
                                            </video>
                                        @endif
                                        <span class="sitePortfolio__play"><i class="fas fa-play"></i></span>
                                    </a>
                                    <h3>{{ $video->title }}</h3>
                                </article>
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

    @if ($workExamples->isNotEmpty())
        <div class="sitePortfolio__block blueControls">
            <h2 class="sitePortfolio__heading">Примеры работ</h2>
            <div class="sitePortfolio__slider">
                <div class="sitePortfolio__worksSwiper swiper">
                    <div class="swiper-wrapper">
                        @foreach ($workExamples as $work)
                            @php
                                $image = $work->thumb ?: $work->image;
                            @endphp
                            @if ($image)
                                <div class="swiper-slide">
                                    <a class="sitePortfolio__work" data-fslightbox="portfolioWorks" href="{{ Storage::url($work->image ?: $image) }}">
                                        <img src="{{ Storage::url($image) }}" alt="{{ $work->title ?: 'Пример работы' }}">
                                        @if ($work->title)
                                            <span>{{ $work->title }}</span>
                                        @endif
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </div>
    @endif
</section>

<style>
    .sitePortfolio{margin-top:34px}
    .sitePortfolio__block{margin-top:34px}
    .sitePortfolio__heading{font-size:28px;line-height:1.25;margin:0 0 22px;font-weight:700}
    .sitePortfolio__slider{position:relative;padding-bottom:44px}
    .sitePortfolio__swiper,
    .sitePortfolio__worksSwiper{overflow:hidden}
    .sitePortfolio__video{height:100%;border:1px solid #e2e7ed;border-radius:8px;overflow:hidden;background:#fff}
    .sitePortfolio__videoPreview{position:relative;display:block;aspect-ratio:16/9;background:#111;color:#fff}
    .sitePortfolio__videoPreview img,
    .sitePortfolio__videoPreview video,
    .sitePortfolio__videoPreview iframe{display:block;width:100%;height:100%;border:0;object-fit:cover;margin:0}
    .sitePortfolio__play{position:absolute;left:50%;top:50%;display:flex;align-items:center;justify-content:center;width:58px;height:58px;border-radius:50%;background:#fff;color:#0989ff;transform:translate(-50%,-50%);box-shadow:0 10px 26px rgba(0,0,0,.18)}
    .sitePortfolio__video h3{font-size:16px;line-height:1.35;margin:0;padding:14px;min-height:62px}
    .sitePortfolio__work{position:relative;display:block;border-radius:8px;overflow:hidden;background:#f2f4f7;height:230px;color:#fff}
    .sitePortfolio__work img{width:100%;height:100%;object-fit:cover;margin:0;transition:.2s}
    .sitePortfolio__work:hover img{transform:scale(1.03)}
    .sitePortfolio__work span{position:absolute;left:0;right:0;bottom:0;padding:18px;background:linear-gradient(transparent,rgba(0,0,0,.72));font-weight:700}
    .sitePortfolio__slider .swiper-button-prev,
    .sitePortfolio__slider .swiper-button-next{top:calc(50% - 22px)}
    .sitePortfolio__slider .swiper-pagination{bottom:0}
    @media(max-width:620px){
        .sitePortfolio__heading{font-size:23px}
        .sitePortfolio__work{height:240px}
        .sitePortfolio__video h3{min-height:auto}
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/fslightbox/3.4.2/index.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function loadSwiper(callback) {
            if (typeof Swiper !== 'undefined') {
                callback();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js';
            script.onload = callback;
            document.head.appendChild(script);
        }

        function initPortfolioSwiper(selector, slides) {
            document.querySelectorAll(selector).forEach(function (swiperElement) {
                if (swiperElement.swiper || typeof Swiper === 'undefined') {
                    return;
                }
                const wrap = swiperElement.closest('.sitePortfolio__slider');
                new Swiper(swiperElement, {
                    slidesPerView: 1,
                    spaceBetween: 20,
                    navigation: {
                        nextEl: wrap.querySelector('.swiper-button-next'),
                        prevEl: wrap.querySelector('.swiper-button-prev')
                    },
                    pagination: {
                        el: wrap.querySelector('.swiper-pagination'),
                        clickable: true
                    },
                    breakpoints: slides
                });
            });
        }

        loadSwiper(function () {
            initPortfolioSwiper('.sitePortfolio__swiper', {
                560: { slidesPerView: 2 },
                1000: { slidesPerView: 3 }
            });
            initPortfolioSwiper('.sitePortfolio__worksSwiper', {
                560: { slidesPerView: 2 },
                900: { slidesPerView: 3 },
                1200: { slidesPerView: 4 }
            });
        });

        if (typeof refreshFsLightbox === 'function') {
            refreshFsLightbox();
        }
    });
</script>
