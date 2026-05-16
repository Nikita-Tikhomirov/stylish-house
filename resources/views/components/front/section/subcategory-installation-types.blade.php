@php
    use Illuminate\Support\Facades\Storage;

    $installationImageUrl = static function (?string $path): string {
        if (!$path) {
            return '';
        }

        $version = Storage::disk('public')->exists($path)
            ? '?v=' . Storage::disk('public')->lastModified($path)
            : '';

        return Storage::url($path) . $version;
    };
@endphp

<section class="s-rollets-installation wrapper">
    <h2 style="margin-bottom: 30px;" class="s-rollets-installation__title title">
        <span>Виды монтажа</span>
        <svg width="114" height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
        </svg>
    </h2>

    @foreach ($installationTypes as $item)
        <div class="accardionJs">
            <div class="accardion__title">
                @if ($item->image)
                    <div class="accardion__title-img">
                        <img src="{{ $installationImageUrl($item->image) }}" alt="{{ $item->title }}">
                    </div>
                @endif
                <div class="accardion__title-text">{{ $item->title }}</div>
            </div>
            <div class="accardion__content">
                @if ($item->detail_image || $item->image)
                    <div class="accardion__content-img">
                        <img src="{{ $installationImageUrl($item->detail_image ?: $item->image) }}" alt="{{ $item->title }}">
                    </div>
                @endif
                <div class="accardion__content-text">
                    {!! $item->description !!}
                </div>
            </div>
        </div>
    @endforeach
</section>

<style>
    .s-rollets-installation .accardionJs {
        border: 1px solid #e1e4e8;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(29, 41, 57, 0.06);
        overflow: hidden;
    }
    .s-rollets-installation .accardionJs + .accardionJs {
        margin-top: 18px;
    }
    .s-rollets-installation .accardionJs::before {
        top: 61px;
        right: 28px;
        z-index: 3;
    }
    .s-rollets-installation .accardionJs::after {
        top: 53px;
        right: 35px;
        z-index: 3;
    }
    .s-rollets-installation .accardionJs p {
        background-color: transparent;
        color: #333;
        padding: 0;
    }
    .s-rollets-installation .accardionJs .accardion__content-img img {
        display: block;
        width: 100%;
        max-width: 500px;
        aspect-ratio: 1 / 1;
        height: auto;
        object-fit: contain;
        border: 1px solid #dfe4ea;
        border-radius: 6px;
        background: #f5f7f9;
    }
    .s-rollets-installation .accardion__title {
        display: flex;
        align-items: center;
        gap: 22px;
        justify-content: flex-start;
        min-height: 124px;
        padding: 12px 22px;
        background: linear-gradient(90deg, #f7f9fb 0%, #ffffff 100%);
        cursor: pointer;
    }
    .s-rollets-installation .accardion__title-img {
        width: 100px;
        height: 100px;
        max-width: 100px;
        max-height: 100px;
        flex: 0 0 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid #dfe4ea;
        border-radius: 6px;
        background: #f5f7f9;
    }
    .s-rollets-installation .accardion__title-img img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .s-rollets-installation .accardion__title-text {
        color: #1f2933;
        font-size: 24px;
        font-weight: 700;
        line-height: 1.2;
    }
    .s-rollets-installation .accardion__content {
        display: grid;
        grid-template-columns: minmax(260px, 500px) minmax(0, 1fr);
        gap: 34px;
        align-items: start;
        padding: 28px;
        background: #fff;
    }
    .s-rollets-installation .accardion__content-img {
        width: 100%;
        max-width: 500px;
    }

    .s-rollets-installation .accardion__content-text {
        color: #333;
        line-height: 1.55;
        font-size: 16px;
    }

    .s-rollets-installation .accardion__content-text p {
        margin: 0 0 14px;
    }

    .s-rollets-installation .accardion__content-text p:last-child {
        margin-bottom: 0;
    }

    .s-rollets-installation .accardion__content-text ul,
    .s-rollets-installation .accardion__content-text ol {
        margin: 0 0 16px 0;
        padding-left: 22px;
    }

    .s-rollets-installation .accardion__content-text ul {
        list-style: disc;
    }

    .s-rollets-installation .accardion__content-text ol {
        list-style: decimal;
    }

    .s-rollets-installation .accardion__content-text li {
        margin-bottom: 8px;
    }

    .s-rollets-installation .accardion__content-text li:last-child {
        margin-bottom: 0;
    }

    .s-rollets-installation .accardion__content-text h2,
    .s-rollets-installation .accardion__content-text h3,
    .s-rollets-installation .accardion__content-text h4 {
        color: #222;
        line-height: 1.3;
        margin: 18px 0 12px;
        font-weight: 700;
    }

    .s-rollets-installation .accardion__content-text h2 {
        font-size: 24px;
    }

    .s-rollets-installation .accardion__content-text h3 {
        font-size: 20px;
    }

    .s-rollets-installation .accardion__content-text h4 {
        font-size: 18px;
    }

    .s-rollets-installation .accardion__content-text strong {
        font-weight: 700;
    }

    .s-rollets-installation .accardion__content-text a {
        color: #0989ff;
        text-decoration: underline;
    }

    .s-rollets-installation .accardion__content-text a:hover {
        text-decoration: none;
    }

    @media (max-width: 991px) {
        .s-rollets-installation .accardion__content {
            grid-template-columns: 1fr;
            gap: 22px;
        }

        .s-rollets-installation .accardion__content-img {
            max-width: 500px;
            margin: 0 auto;
        }

        .s-rollets-installation .accardion__content-text {
            font-size: 15px;
        }

        .s-rollets-installation .accardion__content-text h2 {
            font-size: 22px;
        }

        .s-rollets-installation .accardion__content-text h3 {
            font-size: 19px;
        }
    }

    @media (max-width: 767px) {
        .s-rollets-installation .accardion__title {
            min-height: auto;
            gap: 14px;
            padding: 12px 48px 12px 12px;
        }

        .s-rollets-installation .accardionJs::before {
            top: 47px;
            right: 20px;
        }

        .s-rollets-installation .accardionJs::after {
            top: 39px;
            right: 27px;
        }

        .s-rollets-installation .accardion__title-img {
            width: 74px;
            height: 74px;
            max-width: 74px;
            max-height: 74px;
            flex-basis: 74px;
        }

        .s-rollets-installation .accardion__title-text {
            font-size: 18px;
        }

        .s-rollets-installation .accardion__content {
            padding: 16px;
        }

        .s-rollets-installation .accardion__content-text {
            font-size: 14px;
            line-height: 1.5;
        }

        .s-rollets-installation .accardion__content-text h2 {
            font-size: 20px;
        }

        .s-rollets-installation .accardion__content-text h3 {
            font-size: 18px;
        }

        .s-rollets-installation .accardion__content-text h4 {
            font-size: 16px;
        }

        .s-rollets-installation .accardion__content-text ul,
        .s-rollets-installation .accardion__content-text ol {
            padding-left: 18px;
            margin-bottom: 14px;
        }
    }
</style>
