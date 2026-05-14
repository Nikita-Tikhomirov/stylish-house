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
    .s-rollets-installation .accardionJs p{
        background-color: transparent;
        color: #333;
        padding: 0;
    }
    .s-rollets-installation .accardionJs ul{
        margin-bottom: 20px;
    }
    .s-rollets-installation .accardionJs .accardion__content img{
        display: block;
        margin-bottom: 20px;
        max-width: 100%;
        height: auto;
    }
    .s-rollets-installation .accardion__title{
        display: flex;
        align-items: center;
        gap: 20px;
        justify-content: flex-start;
    }
    .s-rollets-installation .accardion__title-img{
        width: 50px;
        height: 50px;
        max-width: 50px;
        max-height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .s-rollets-installation .accardion__title-img img{
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .s-rollets-installation .accardionJs .accardion__content p{
        line-height: 130%;
    }
    .s-rollets-installation .accardionJs .accardion__content ul {
        list-style: disc;
        list-style-position: inside;
    }
    .s-rollets-installation .accardionJs .accardion__content ul li{
        margin-bottom: 10px;
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
