@props(['installationTypes' => null])
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

<div class="s-rollets-installation wrapper">
    <h2 style="margin-bottom: 30px;" class="s-rollets-installation__title title"> <span>Виды монтажа</span><svg width="114" height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
    </svg></h2>

    @if ($installationTypes && $installationTypes->isNotEmpty())
        @foreach ($installationTypes as $item)
            <div class="accardionJs">
                <div class="accardion__title">
                    <div class="accardion__title-img">
                        <img src="{{ $item->image ? $installationImageUrl($item->image) : 'img/korob-vnutri.jpg' }}" alt="{{ $item->title }}" />
                    </div>
                    <div class="accardion__title-text">{{ $loop->index + 1 }}. {{ $item->title }}</div>
                </div>
                <div class="accardion__content">
                    @if ($item->detail_image || $item->image)
                    <div class="accardion__content-img">
                        <img src="{{ $installationImageUrl($item->detail_image ?: $item->image) }}" alt="{{ $item->title }}" />
                    </div>
                    @endif
                    <div class="accardion__content-text">
                        {!! $item->description !!}
                    </div>
                </div>
            </div>
        @endforeach
    @else
        {{-- Fallback: hardcoded content when no installation types exist --}}
        <div class="accardionJs">
            <div class="accardion__title">
                <div class="accardion__title-img">
                    <img src="img/korob-vnutri.jpg" alt="Короб внутри (скрытый)" />
                </div>
                <div class="accardion__title-text">1. Короб внутри (скрытый)</div>
            </div>
            <div class="accardion__content">
                <div class="accardion__content-img">
                    <img src="img/korob-vnutri-big.jpg" alt="Короб внутри (скрытый)" />
                </div>
                <div class="accardion__content-text">
                    <p>Рольставни устанавливаются так, что короб находится внутри ниши и полностью скрыт от глаз. Это обеспечивает аккуратный и эстетичный вид, особенно важно для ванных комнат и туалетов. Рекомендуется монтировать до укладки плитки — так получится ровное и красивое соединение без щелей.</p>
                    <p><strong>Плюсы:</strong></p>
                    <ul>
                        <li>Эстетичный внешний вид, короб полностью скрыт</li>
                        <li>Идеально сочетается с интерьером ванной</li>
                        <li>Позволяет избежать щелей между направляющими и плиткой при установке до отделки</li>
                    </ul>
                    <p><strong>Минусы:</strong></p>
                    <ul>
                        <li>Требует точных замеров и аккуратной установки</li>
                        <li>Размер короба может ограничивать пространство (обычно 137–180 мм)</li>
                        <li>Более сложный монтаж по сравнению с наружным вариантом</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accardionJs">
            <div class="accardion__title">
                <div class="accardion__title-img">
                    <img src="img/korob-snaruzhi.jpg" alt="Короб снаружи" />
                </div>
                <div class="accardion__title-text">2. Короб снаружи</div>
            </div>
            <div class="accardion__content">
                <div class="accardion__content-img">
                    <img src="img/korob-snaruzhi-big.jpg" alt="Короб снаружи" />
                </div>
                <div class="accardion__content-text">
                    <p>Короб размещается с внешней стороны ниши. Такой монтаж проще и быстрее, его можно делать в любое время, даже после отделки помещения. Размер короба зависит от высоты изделия, но при наружной установке он заметен.</p>
                    <p><strong>Плюсы:</strong></p>
                    <ul>
                        <li>Простота и скорость монтажа</li>
                        <li>Можно устанавливать в любое время, даже после укладки плитки</li>
                        <li>Не требуется точная подгонка под нишу</li>
                    </ul>
                    <p><strong>Минусы:</strong></p>
                    <ul>
                        <li>Короб виден снаружи, что может влиять на эстетику</li>
                        <li>Менее аккуратное соединение с плиткой при последующем монтаже</li>
                        <li>Занимает немного больше пространства перед нишей</li>
                    </ul>
                </div>
            </div>
        </div>
    @endif

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
    .s-rollets-installation .accardionJs::before{
        top: 61px;
        right: 28px;
        z-index: 3;
    }
    .s-rollets-installation .accardionJs::after{
        top: 53px;
        right: 35px;
        z-index: 3;
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
            padding: 12px 48px 12px 12px;
        }
        .s-rollets-installation .accardionJs::before{
            top: 47px;
            right: 20px;
        }
        .s-rollets-installation .accardionJs::after{
            top: 39px;
            right: 27px;
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
