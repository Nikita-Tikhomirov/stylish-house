@props(['installationTypes' => []])
@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="s-rollets-installation wrapper">
    <h2 style="margin-bottom: 30px;" class="s-rollets-installation__title title">
        <span>Виды монтажа рольставней</span>
        <svg width="114" height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
    </svg>
    </h2>

    @forelse ($installationTypes as $type)
    <div class="accardionJs">
        <div class="accardion__title">
            <div class="accardion__title-img">
                @if ($type->image)
                    <img src="{{ Storage::url($type->image) }}" alt="{{ $type->title }}" />
                @endif
            </div>
            <div class="accardion__title-text">{{ $type->title }}</div>
        </div>
        <div class="accardion__content">
            <div class="accardion__content-img">
                @if ($type->detail_image)
                    <img src="{{ Storage::url($type->detail_image) }}" alt="{{ $type->title }}" />
                @elseif ($type->image)
                    <img src="{{ Storage::url($type->image) }}" alt="{{ $type->title }}" />
                @endif
            </div>
            <div class="accardion__content-text">
                {!! $type->description !!}
            </div>
        </div>
    </div>
    @empty
    <p>Нет данных о типах монтажа.</p>
    @endforelse
</div>


<style>
    .s-rollets-installation .accardionJs{
        border: 1px solid #e1e4e8;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(29, 41, 57, 0.06);
        overflow: hidden;
    }
    .s-rollets-installation .accardionJs + .accardionJs{
        margin-top: 18px;
    }
    .s-rollets-installation .accardionJs p{
        background-color: transparent;
        color: #333;
        padding: 0;
    }
    .s-rollets-installation .accardionJs ul{
        margin-bottom: 20px;
    }
    .s-rollets-installation .accardionJs .accardion__content-img img{
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
    .s-rollets-installation .accardion__title{
        display: flex;
        align-items: center;
        gap: 22px;
        justify-content: flex-start;
        min-height: 124px;
        padding: 12px 22px;
        background: linear-gradient(90deg, #f7f9fb 0%, #ffffff 100%);
        cursor: pointer;
    }
    .s-rollets-installation .accardion__title-img{
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
    .s-rollets-installation .accardion__title-img img{
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .s-rollets-installation .accardion__title-text{
        color: #1f2933;
        font-size: 24px;
        font-weight: 700;
        line-height: 1.2;
    }
    .s-rollets-installation .accardion__content{
        display: grid;
        grid-template-columns: minmax(260px, 500px) minmax(0, 1fr);
        gap: 34px;
        align-items: start;
        padding: 28px;
        background: #fff;
    }
    .s-rollets-installation .accardion__content-img{
        width: 100%;
        max-width: 500px;
    }
    .s-rollets-installation .accardionJs .accardion__content p{
        line-height: 1.55;
        margin-bottom: 14px;
    }
    .s-rollets-installation .accardionJs .accardion__content ul {
        list-style: disc;
        list-style-position: outside;
        padding-left: 22px;
    }
    .s-rollets-installation .accardionJs .accardion__content ul li{
        margin-bottom: 10px;
        line-height: 1.45;
    }
    @media (max-width: 900px){
        .s-rollets-installation .accardion__content{
            grid-template-columns: 1fr;
            gap: 22px;
        }
        .s-rollets-installation .accardion__content-img{
            max-width: 500px;
            margin: 0 auto;
        }
    }
    @media (max-width: 560px){
        .s-rollets-installation .accardion__title{
            min-height: auto;
            gap: 14px;
            padding: 12px;
        }
        .s-rollets-installation .accardion__title-img{
            width: 74px;
            height: 74px;
            max-width: 74px;
            max-height: 74px;
            flex-basis: 74px;
        }
        .s-rollets-installation .accardion__title-text{
            font-size: 18px;
        }
        .s-rollets-installation .accardion__content{
            padding: 16px;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Исправление для аккордеонов с вложенными элементами
    const accordionTitles = document.querySelectorAll('.accardion__title');
    
    accordionTitles.forEach(title => {
        const childElements = title.querySelectorAll('*');
        childElements.forEach(child => {
            child.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                title.click();
            });
        });
    });
});
</script>
