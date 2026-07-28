<section class="s-videoRevs wrapper blueControls">
    <style>
        .videoCard__video{
            display:block;
            width:100%;
            height:270px;
            border:0;
            border-radius:0 8px 8px;
            background:#111;
            object-fit:cover;
        }
        @media (max-width: 767px){
            .s-videoRevs__cards{
                padding-bottom: 58px;
            }
            .s-videoRevs__cards .swiper-button-prev,
            .s-videoRevs__cards .swiper-button-next{
                top: auto;
                bottom: 0;
                transform: none;
            }
            .s-videoRevs__cards .swiper-button-prev{
                left: calc(50% - 74px);
            }
            .s-videoRevs__cards .swiper-button-next{
                right: calc(50% - 74px);
            }
            .s-videoRevs__cards .swiper-pagination{
                bottom: 10px;
            }
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
        @media (max-width: 620px){
            .siteVideoModal{padding:14px}
            .siteVideoModal__play{width:62px;height:62px}
        }
    </style>
    <h2 class="s-videoRevs__title title"> <span>Видеообзор {{ $subcategory->titleh1 }}</span><svg width="114" height="35"
            viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4"
                stroke-miterlimit="3.8637" stroke-linecap="round"></path>
        </svg></h2>
    <div class="s-videoRevs__cards">
        <div class="s-videoRevs__swiper swiper">
            <div class="swiper-wrapper">

                @foreach ($videoreviews as $rev)
                    @php
                        $isExternalVideo = \Illuminate\Support\Str::startsWith($rev->video, ['http://', 'https://']);
                        $videoUrl = $isExternalVideo ? $rev->video : Storage::url($rev->video);
                        $embedUrl = $videoUrl;
                        if (preg_match('~drive\.google\.com/file/d/([^/]+)~', $videoUrl, $matches)) {
                            $embedUrl = 'https://drive.google.com/file/d/' . $matches[1] . '/preview';
                        }
                    @endphp
                    <div class="s-videoRevs__slide swiper-slide">
                        <div class="videoCard">
                            <div class="videoCard__imgWrap">
                                <div class="videoCard__date">{{$rev->created_at}}</div>
                                @if ($rev->cover_image)
                                    <div class="videoCard__img"> <img src="{{ Storage::url($rev->cover_image) }}" alt="{{ $rev->title }}" loading="lazy" decoding="async" /></div>
                                @elseif ($isExternalVideo)
                                    <iframe class="videoCard__video" src="{{ $embedUrl }}" loading="lazy" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                                @else
                                    <video class="videoCard__video" preload="metadata" muted playsinline>
                                        <source src="{{ $videoUrl }}" type="video/mp4">
                                    </video>
                                @endif
                                <a class="videoCard__playBtn"
                                   @if ($isExternalVideo)
                                       data-fslightbox="videoRevs"
                                       href="{{ $embedUrl }}"
                                   @else
                                       href="{{ $videoUrl }}"
                                       data-video-modal
                                       data-video-src="{{ $videoUrl }}"
                                       data-video-poster="{{ $rev->cover_image ? Storage::url($rev->cover_image) : '' }}"
                                       data-video-title="{{ $rev->title }}"
                                   @endif> <i class="fas fa-play"></i></a>
                            </div>
                            <div class="videoCard__info">
                                <div class="videoCard__title">{{$rev->title}}</div>
                                <div class="videoCard__text">{{$rev->description}}</div>
                            </div>
                        </div>
                    </div>

                @endforeach








            </div>

        </div>
        <div class="swiper-pagination"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"> </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.siteVideoModalReady) {
                return;
            }
            window.siteVideoModalReady = true;

            document.addEventListener('click', function (event) {
                const trigger = event.target.closest('[data-video-modal]');
                if (!trigger) {
                    return;
                }

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

                const escHandler = function (keyEvent) {
                    if (keyEvent.key === 'Escape' && document.body.contains(modal)) {
                        document.removeEventListener('keydown', escHandler);
                        close();
                    }
                };
                document.addEventListener('keydown', escHandler);
                document.body.style.overflow = 'hidden';
                document.body.appendChild(modal);
            });
        });
    </script>
</section>
