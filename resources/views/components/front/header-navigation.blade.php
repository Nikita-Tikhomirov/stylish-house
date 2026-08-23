<nav class="header-navigation" data-header-navigation aria-label="Каталог товаров">
    <div class="header-navigation__bar">
        <button class="header__catalogOpenBtn header-navigation__toggle" type="button"
            data-navigation-toggle aria-expanded="false" aria-controls="header-navigation-panel">
            <span class="header-navigation__menu-icon" aria-hidden="true">
                <span></span><span></span><span></span>
            </span>
            <span>Каталог</span>
            <i class="fas fa-chevron-down" aria-hidden="true"></i>
        </button>

        <div class="header-navigation__quick-links" aria-label="Популярные разделы">
            @foreach ($navigation['quickLinks'] as $link)
                <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
            @endforeach
        </div>
    </div>

    <div class="hidenCatalog header-navigation__panel" id="header-navigation-panel"
        data-navigation-panel aria-hidden="true" hidden>
        <button class="header-navigation__backdrop" type="button" data-navigation-close
            aria-label="Закрыть каталог"></button>

        <div class="header-navigation__dialog" role="dialog" aria-modal="true" aria-label="Каталог">
            <header class="header-navigation__dialog-header">
                <div>
                    <span class="header-navigation__eyebrow">Каталог</span>
                    <strong>Выберите нужное решение</strong>
                </div>
                <label class="header-navigation__search">
                    <span class="sr-only">Поиск по меню</span>
                    <input type="search" data-navigation-search placeholder="Найти раздел" autocomplete="off">
                    <i class="fas fa-search" aria-hidden="true"></i>
                </label>
                <button class="header-navigation__close" type="button" data-navigation-close
                    aria-label="Закрыть каталог">×</button>
            </header>

            <div class="header-navigation__desktop">
                <aside class="header-navigation__rail">
                    <div class="header-navigation__tabs" role="tablist" aria-label="Разделы каталога">
                        @foreach ($navigation['tabs'] as $tabIndex => $tab)
                            <button type="button" role="tab" data-navigation-tab="{{ $tab['id'] }}"
                                aria-selected="{{ $tabIndex === 0 ? 'true' : 'false' }}"
                                aria-controls="navigation-tab-panel-{{ $tab['id'] }}"
                                class="{{ $tabIndex === 0 ? 'is-active' : '' }}">
                                {{ $tab['label'] }}
                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </button>
                        @endforeach
                    </div>

                    <div class="header-navigation__utility">
                        @foreach ($navigation['utilityLinks'] as $link)
                            <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                        @endforeach
                    </div>

                    <a class="header-navigation__measure" href="/shop-pages/zamer/">
                        <span>Нужна помощь с выбором?</span>
                        <strong>Заказать бесплатный замер</strong>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </aside>

                <div class="header-navigation__content">
                    @forelse ($navigation['tabs'] as $tabIndex => $tab)
                        <section id="navigation-tab-panel-{{ $tab['id'] }}" role="tabpanel"
                            data-navigation-tab-panel="{{ $tab['id'] }}" @if ($tabIndex !== 0) hidden @endif>
                            <div class="header-navigation__content-heading">
                                <div>
                                    <span>Раздел</span>
                                    <h2>{{ $tab['label'] }}</h2>
                                </div>
                                <a href="{{ $tab['url'] }}">Смотреть все <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                            </div>

                            <div class="header-navigation__columns">
                                @foreach ($tab['sections'] as $section)
                                    <section class="header-navigation__column" data-navigation-section>
                                        <h3>{{ $section['label'] }}</h3>
                                        <ul>
                                            @foreach ($section['links'] as $link)
                                                <li data-navigation-link data-navigation-label="{{ mb_strtolower($link['label']) }}">
                                                    <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </section>
                                @endforeach
                            </div>
                            <p class="header-navigation__no-results" data-navigation-empty hidden>Ничего не найдено.</p>
                        </section>
                    @empty
                        <p class="header-navigation__empty">Разделы каталога настраиваются.</p>
                    @endforelse
                </div>
            </div>

            <div class="header-navigation__mobile">
                @foreach ($navigation['tabs'] as $tab)
                    <section class="header-navigation__accordion" data-mobile-accordion="{{ $tab['id'] }}">
                        <button type="button" data-mobile-accordion-toggle aria-expanded="false"
                            aria-controls="mobile-navigation-panel-{{ $tab['id'] }}">
                            <span>{{ $tab['label'] }}</span>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div id="mobile-navigation-panel-{{ $tab['id'] }}" data-mobile-accordion-panel hidden>
                            <a class="header-navigation__mobile-all" href="{{ $tab['url'] }}">Все в разделе</a>
                            @foreach ($tab['sections'] as $section)
                                <section data-navigation-section>
                                    <h3>{{ $section['label'] }}</h3>
                                    @foreach ($section['links'] as $link)
                                        <a href="{{ $link['url'] }}" data-navigation-link
                                            data-navigation-label="{{ mb_strtolower($link['label']) }}">{{ $link['label'] }}</a>
                                    @endforeach
                                </section>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <div class="header-navigation__mobile-utility">
                    @foreach ($navigation['utilityLinks'] as $link)
                        <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                    @endforeach
                    <a class="header-navigation__measure" href="/shop-pages/zamer/">
                        <strong>Заказать бесплатный замер</strong>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>
