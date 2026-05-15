@props(['systems' => []])
@php
    use Illuminate\Support\Facades\Storage;

    $systemImageUrl = static function (?string $path): string {
        if (!$path) {
            return '';
        }

        $version = Storage::disk('public')->exists($path)
            ? '?v=' . Storage::disk('public')->lastModified($path)
            : '';

        return Storage::url($path) . $version;
    };

    $componentLines = static function (?string $components): array {
        if (!$components) {
            return [];
        }

        return collect(preg_split('/\R/u', trim($components)))
            ->map(static fn ($line) => trim(preg_replace('/^\s*\d+[\.\)]\s*/u', '', $line)))
            ->filter()
            ->values()
            ->all();
    };
@endphp
<div class="s-rollets-systems wrapper">
    <h2 class="s-rollets-systems__title title"> <span>Варианты управления рольставнями</span><svg width="114" height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
    </svg></h2>

    <div class="tabs tabsWrapJs s-seo">
        @if ($systems->isNotEmpty())
        <div class="tabs__nav tabsNavJs">
            @foreach ($systems as $system)
                <div class="tabs__link">{{ $system->title }}</div>
            @endforeach
        </div>

        <div class="tabs__container tabsJs">
            @foreach ($systems as $system)
                <div class="tabs__item">
                    <div class="tabs__content">
                        <div class="tabs__img">
                            @if ($system->image)
                                <img src="{{ $systemImageUrl($system->image) }}" alt="{{ $system->title }}" />
                            @endif
                        </div>
                        <div class="tabs__text">
                            <h3>{{ $system->title }}</h3>
                            @if ($system->description)
                                <div class="tabs__description">{!! $system->description !!}</div>
                            @endif
                            @php($components = $componentLines($system->components))
                            @if (!empty($components))
                                <ol class="tabs__components">
                                    @foreach ($components as $component)
                                        <li>{{ $component }}</li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @else
            <p>Нет данных о системах управления.</p>
        @endif
    </div>
</div>

<style>
    .s-rollets-systems {
        padding-top: 60px;
    }

    .s-rollets-systems .tabs {
        background: #fff;
    }

    .s-rollets-systems .tabs__nav {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 24px;
        border-bottom: 1px solid #dfe4ea;
    }
    
    .s-rollets-systems .tabs__link {
        min-height: 48px;
        padding: 13px 22px;
        cursor: pointer;
        border: none;
        border-radius: 6px 6px 0 0;
        background: #f7f9fb;
        color: #374151;
        font-size: 16px;
        font-weight: 700;
        line-height: 1.2;
        transition: all 0.3s ease;
        border-bottom: 3px solid transparent;
        margin-bottom: -1px;
    }
    
    .s-rollets-systems .tabs__link:hover {
        color: #0989ff;
        background: #eef7ff;
    }
    
    .s-rollets-systems .tabs__link.active {
        color: #0989ff;
        border-bottom: 3px solid #0989ff;
        background: #eef7ff;
    }
    
    .s-rollets-systems .tabs__container {
        position: relative;
    }
    
    .s-rollets-systems .tabs__item {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    
    .s-rollets-systems .tabs__item.active {
        display: block;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .s-rollets-systems .tabs__content {
        display: grid;
        grid-template-columns: minmax(260px, 500px) minmax(0, 1fr);
        gap: 34px;
        align-items: start;
        padding: 28px;
        border: 1px solid #e1e4e8;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(29, 41, 57, 0.06);
    }
    
    .s-rollets-systems .tabs__img {
        width: 100%;
        max-width: 500px;
    }
    
    .s-rollets-systems .tabs__img img {
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
    
    .s-rollets-systems .tabs__text h3 {
        color: #1f2933;
        margin-bottom: 12px;
        font-size: 24px;
        font-weight: 700;
        line-height: 1.2;
    }
    
    .s-rollets-systems .tabs__description {
        color: #4b5563;
        font-size: 17px;
        line-height: 1.5;
        margin-bottom: 18px;
    }

    .s-rollets-systems .tabs__description p {
        margin-bottom: 10px;
        padding: 0;
        background: transparent;
        color: inherit;
    }
    
    .s-rollets-systems .tabs__components {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px 22px;
        margin: 0;
        padding-left: 22px;
        list-style-position: outside;
    }
    
    .s-rollets-systems .tabs__components li {
        color: #333;
        line-height: 1.35;
        padding-left: 4px;
    }
    
    @media (max-width: 900px) {
        .s-rollets-systems .tabs__content {
            grid-template-columns: 1fr;
            gap: 22px;
        }

        .s-rollets-systems .tabs__img {
            margin: 0 auto;
        }
    }

    @media (max-width: 768px) {
        .s-rollets-systems .tabs__link {
            flex: 1 1 50%;
            min-width: 150px;
            text-align: center;
            font-size: 14px;
            padding: 12px 15px;
        }

        .s-rollets-systems .tabs__components {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 480px) {
        .s-rollets-systems .tabs__link {
            flex: 1 1 100%;
        }

        .s-rollets-systems .tabs__content {
            padding: 16px;
        }

        .s-rollets-systems .tabs__text h3 {
            font-size: 20px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Находим все таб-обертки на странице
        const tabsWrappers = document.querySelectorAll('.tabsWrapJs');
        
        tabsWrappers.forEach(wrapper => {
            const tabsNav = wrapper.querySelector('.tabsNavJs');
            const tabsContainer = wrapper.querySelector('.tabsJs');
            const tabsLinks = tabsNav.querySelectorAll('.tabs__link');
            const tabsItems = tabsContainer.querySelectorAll('.tabs__item');
            
            // Добавляем активный класс первому табу
            if (tabsLinks.length > 0 && tabsItems.length > 0) {
                tabsLinks[0].classList.add('active');
                tabsItems[0].classList.add('active');
            }
            
            // Обработчик клика по табам
            tabsLinks.forEach((link, index) => {
                link.addEventListener('click', function() {
                    // Удаляем активный класс у всех табов
                    tabsLinks.forEach(tab => tab.classList.remove('active'));
                    tabsItems.forEach(item => item.classList.remove('active'));
                    
                    // Добавляем активный класс текущему табу
                    link.classList.add('active');
                    if (tabsItems[index]) {
                        tabsItems[index].classList.add('active');
                    }
                });
            });
        });
    });
</script>
