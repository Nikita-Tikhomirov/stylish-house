@props([
    'workExamples' => collect(),
    'workExampleGroups' => null,
    'videoReviews' => collect(),
])

@php
    $workExampleGroups = collect($workExampleGroups ?: ['Примеры работ' => $workExamples])
        ->filter(fn ($items) => collect($items)->isNotEmpty());
@endphp

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
                                            <img src="{{ $poster }}" alt="{{ $video->title }}" loading="lazy" decoding="async">
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

    @if ($workExampleGroups->isNotEmpty())
        <div class="sitePortfolio__block sitePortfolio__worksBlock blueControls" data-portfolio-tabs>
            <h2 class="sitePortfolio__heading">Примеры работ</h2>
            <div class="sitePortfolio__tabs" role="tablist" aria-label="Категории примеров работ">
                @foreach ($workExampleGroups as $groupTitle => $groupWorks)
                    <button
                        class="sitePortfolio__tab {{ $loop->first ? 'is-active' : '' }}"
                        type="button"
                        role="tab"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        data-portfolio-tab="{{ $loop->index }}"
                    >
                        <span>{{ $groupTitle }}</span>
                        <small>{{ collect($groupWorks)->count() }}</small>
                    </button>
                @endforeach
            </div>
            @foreach ($workExampleGroups as $groupTitle => $groupWorks)
                @php
                    $galleryName = 'portfolioWorks' . $loop->index;
                @endphp
            <div class="sitePortfolio__panel {{ $loop->first ? 'is-active' : '' }}" data-portfolio-panel="{{ $loop->index }}">
                <div class="sitePortfolio__panelHead">
                    <h3>{{ $groupTitle }}</h3>
                    <span>{{ collect($groupWorks)->count() }} фото</span>
                </div>
            <div class="sitePortfolio__slider">
                <div class="sitePortfolio__worksSwiper swiper">
                    <div class="swiper-wrapper">
                        @foreach ($groupWorks as $work)
                            @php
                                $image = $work->thumb ?: $work->image;
                            @endphp
                            @if ($image)
                                <div class="swiper-slide">
                                    <a class="sitePortfolio__work" data-fslightbox="{{ $galleryName }}" href="{{ Storage::url($work->image ?: $image) }}">
                                        <img src="{{ Storage::url($image) }}" alt="{{ $work->title ?: $groupTitle }}" loading="lazy" decoding="async">
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
            @endforeach
        </div>
    @endif
</section>



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

        function activatePortfolioTab(root, index) {
            root.querySelectorAll('[data-portfolio-tab]').forEach(function (tab) {
                const isActive = tab.dataset.portfolioTab === index;
                tab.classList.toggle('is-active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            root.querySelectorAll('[data-portfolio-panel]').forEach(function (panel) {
                const isActive = panel.dataset.portfolioPanel === index;
                panel.classList.toggle('is-active', isActive);
                panel.querySelectorAll('.swiper').forEach(function (swiperElement) {
                    if (isActive && swiperElement.swiper) {
                        swiperElement.swiper.update();
                    }
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

        document.querySelectorAll('[data-portfolio-tabs]').forEach(function (root) {
            root.querySelectorAll('[data-portfolio-tab]').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    activatePortfolioTab(root, tab.dataset.portfolioTab);
                });
            });
        });

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
