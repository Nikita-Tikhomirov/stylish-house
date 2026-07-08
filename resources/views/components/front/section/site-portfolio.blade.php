<section class="sitePortfolio">
    @if ($videoReviews->isNotEmpty())
        <div class="sitePortfolio__block">
            <h2 class="sitePortfolio__heading">Видеообзоры нашей продукции</h2>
            <div class="sitePortfolio__videos">
                @foreach ($videoReviews as $video)
                    @php
                        $isExternalVideo = \Illuminate\Support\Str::startsWith($video->video, ['http://', 'https://']);
                        $videoUrl = $isExternalVideo ? $video->video : Storage::url($video->video);
                        $embedUrl = $videoUrl;
                        if (preg_match('~drive\.google\.com/file/d/([^/]+)~', $videoUrl, $matches)) {
                            $embedUrl = 'https://drive.google.com/file/d/' . $matches[1] . '/preview';
                        }
                    @endphp
                    <article class="sitePortfolio__video">
                        @if ($isExternalVideo)
                            <iframe src="{{ $embedUrl }}" loading="lazy" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                        @else
                            <video controls preload="metadata" @if($video->cover_image) poster="{{ Storage::url($video->cover_image) }}" @endif>
                                <source src="{{ $videoUrl }}" type="video/mp4">
                            </video>
                        @endif
                        <h3>{{ $video->title }}</h3>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    @if ($workExamples->isNotEmpty())
        <div class="sitePortfolio__block">
            <h2 class="sitePortfolio__heading">Примеры работ</h2>
            <div class="sitePortfolio__works">
                @foreach ($workExamples as $work)
                    @php
                        $image = $work->thumb ?: $work->image;
                    @endphp
                    @if ($image)
                        <a class="sitePortfolio__work" data-fslightbox="portfolioWorks" href="{{ Storage::url($work->image ?: $image) }}">
                            <img src="{{ Storage::url($image) }}" alt="{{ $work->title ?: 'Пример работы' }}">
                            @if ($work->title)
                                <span>{{ $work->title }}</span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</section>

<style>
    .sitePortfolio{margin-top:34px}
    .sitePortfolio__block{margin-top:34px}
    .sitePortfolio__heading{font-size:28px;margin:0 0 22px}
    .sitePortfolio__videos{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
    .sitePortfolio__video{border:1px solid #e2e7ed;border-radius:8px;overflow:hidden;background:#fff}
    .sitePortfolio__video video,.sitePortfolio__video iframe{display:block;width:100%;aspect-ratio:16/9;border:0;background:#111;object-fit:cover}
    .sitePortfolio__video h3{font-size:16px;line-height:1.35;margin:0;padding:14px}
    .sitePortfolio__works{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
    .sitePortfolio__work{position:relative;display:block;border-radius:8px;overflow:hidden;background:#f2f4f7;min-height:210px;color:#fff}
    .sitePortfolio__work img{width:100%;height:100%;min-height:210px;object-fit:cover;margin:0;transition:.2s}
    .sitePortfolio__work:hover img{transform:scale(1.03)}
    .sitePortfolio__work span{position:absolute;left:0;right:0;bottom:0;padding:18px;background:linear-gradient(transparent,rgba(0,0,0,.72));font-weight:700}
    @media(max-width:1000px){.sitePortfolio__videos{grid-template-columns:repeat(2,minmax(0,1fr))}.sitePortfolio__works{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:620px){.sitePortfolio__videos,.sitePortfolio__works{grid-template-columns:1fr}.sitePortfolio__work,.sitePortfolio__work img{min-height:240px}}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/fslightbox/3.4.2/index.min.js"></script>
