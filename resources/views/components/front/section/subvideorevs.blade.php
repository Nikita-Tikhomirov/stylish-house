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
                                    <div class="videoCard__img"> <img src="{{ Storage::url($rev->cover_image) }}" alt="{{ $rev->title }}" /></div>
                                @elseif ($isExternalVideo)
                                    <iframe class="videoCard__video" src="{{ $embedUrl }}" loading="lazy" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                                @else
                                    <video class="videoCard__video" preload="metadata" muted playsinline>
                                        <source src="{{ $videoUrl }}" type="video/mp4">
                                    </video>
                                @endif
                                <a data-fslightbox="videoRevs" href="{{ $embedUrl }}" class="videoCard__playBtn"> <i class="fas fa-play"></i></a>
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
</section>
