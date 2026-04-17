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
                        <img src="{{ Storage::url($item->image) }}" alt="{{ $item->title }}">
                    </div>
                @endif
                <div class="accardion__title-text">{{ $item->title }}</div>
            </div>
            <div class="accardion__content">
                @if ($item->image)
                    <div class="accardion__content-img">
                        <img src="{{ Storage::url($item->image) }}" alt="{{ $item->title }}">
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
    .s-rollets-installation .accardionJs p {
        background-color: transparent;
        color: #333;
        padding: 0;
    }
    .s-rollets-installation .accardionJs .accardion__content img {
        display: block;
        margin-bottom: 20px;
        max-width: 100%;
        height: auto;
    }
    .s-rollets-installation .accardion__title {
        display: flex;
        align-items: center;
        gap: 20px;
        justify-content: flex-start;
    }
    .s-rollets-installation .accardion__title-img {
        width: 50px;
        height: 50px;
        max-width: 50px;
        max-height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .s-rollets-installation .accardion__title-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
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
            gap: 12px;
        }

        .s-rollets-installation .accardion__title-img {
            width: 42px;
            height: 42px;
            max-width: 42px;
            max-height: 42px;
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
