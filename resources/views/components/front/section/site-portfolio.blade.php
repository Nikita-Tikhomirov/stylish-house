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
                                    <a class="sitePortfolio__videoPreview"
                                       @if ($isExternalVideo)
                                           data-fslightbox="portfolioVideos"
                                           href="{{ $embedUrl }}"
                                       @else
                                           href="{{ $videoUrl }}"
                                           data-video-modal
                                           data-video-src="{{ $videoUrl }}"
                                           data-video-poster="{{ $poster }}"
                                           data-video-title="{{ $video->title }}"
                                       @endif>
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
    .sitePortfolio__slider{position:relative}
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
    .sitePortfolio__slider .swiper-button-next{top:calc(50% - 34px)}
    .sitePortfolio__slider > .swiper-pagination{
        position:static;
        display:flex;
        justify-content:center;
        gap:8px;
        width:100%;
        margin-top:24px;
        transform:none;
    }
    .sitePortfolio__slider > .swiper-pagination .swiper-pagination-bullet{
        margin:0;
    }
    .siteVideoModal{
        position:fixed;
        inset:0;
        z-index:9999;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:36px;
        background:rgba(0,0,0,.78);
    }
    .siteVideoModal__inner{
        position:relative;
        width:min(1180px,100%);
        background:#050505;
        border-radius:8px;
        overflow:hidden;
        box-shadow:0 24px 80px rgba(0,0,0,.42);
    }
    .siteVideoModal__video{
        display:block;
        width:100%;
        max-height:82vh;
        aspect-ratio:16/9;
        background:#050505;
        object-fit:contain;
    }
    .siteVideoModal__play{
        position:absolute;
        left:50%;
        top:50%;
        display:flex;
        align-items:center;
        justify-content:center;
        width:78px;
        height:78px;
        border:0;
        border-radius:50%;
        color:#0989ff;
        background:#fff;
        box-shadow:0 14px 34px rgba(0,0,0,.28);
        transform:translate(-50%,-50%);
        cursor:pointer;
    }
    .siteVideoModal__play.is-hidden{
        display:none;
    }
    .siteVideoModal__close{
        position:absolute;
        right:14px;
        top:14px;
        z-index:2;
        display:flex;
        align-items:center;
        justify-content:center;
        width:38px;
        height:38px;
        border:0;
        border-radius:50%;
        color:#fff;
        background:rgba(0,0,0,.48);
        font-size:28px;
        line-height:1;
        cursor:pointer;
    }
    @media(max-width:620px){
        .sitePortfolio__heading{font-size:23px}
        .sitePortfolio__slider{padding-bottom:58px}
        .sitePortfolio__slider .swiper-button-prev,
        .sitePortfolio__slider .swiper-button-next{
            top:auto;
            bottom:0;
            transform:none;
        }
        .sitePortfolio__slider .swiper-button-prev{left:calc(50% - 74px)}
        .sitePortfolio__slider .swiper-button-next{right:calc(50% - 74px)}
        .sitePortfolio__work{height:240px}
        .sitePortfolio__video h3{min-height:auto}
        .siteVideoModal{padding:14px}
        .siteVideoModal__play{width:62px;height:62px}
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

        document.querySelectorAll('[data-video-modal]').forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                event.preventDefault();

                const src = trigger.dataset.videoSrc;
                if (!src) {
                    return;
                }

                const poster = trigger.dataset.videoPoster || '';
                const title = trigger.dataset.videoTitle || '';
                const modal = document.createElement('div');
                modal.className = 'siteVideoModal';
                modal.innerHTML = `
                    <div class="siteVideoModal__inner" role="dialog" aria-modal="true" aria-label="${title}">
                        <button class="siteVideoModal__close" type="button" aria-label="Закрыть">&times;</button>
                        <video class="siteVideoModal__video" controls preload="metadata" playsinline ${poster ? `poster="${poster}"` : ''}>
                            <source src="${src}" type="video/mp4">
                        </video>
                        <button class="siteVideoModal__play" type="button" aria-label="Воспроизвести">
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
                `;

                const video = modal.querySelector('video');
                const play = modal.querySelector('.siteVideoModal__play');
                const close = function () {
                    video.pause();
                    modal.remove();
                    document.body.style.overflow = '';
                };

                play.addEventListener('click', function () {
                    video.play();
                });
                video.addEventListener('play', function () {
                    play.classList.add('is-hidden');
                });
                video.addEventListener('pause', function () {
                    if (!video.ended) {
                        play.classList.remove('is-hidden');
                    }
                });
                modal.querySelector('.siteVideoModal__close').addEventListener('click', close);
                modal.addEventListener('click', function (clickEvent) {
                    if (clickEvent.target === modal) {
                        close();
                    }
                });
                document.addEventListener('keydown', function escHandler(keyEvent) {
                    if (keyEvent.key === 'Escape' && document.body.contains(modal)) {
                        document.removeEventListener('keydown', escHandler);
                        close();
                    }
                });

                document.body.style.overflow = 'hidden';
                document.body.appendChild(modal);
            });
        });
    });
</script>
