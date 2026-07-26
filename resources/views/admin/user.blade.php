<x-front.head title="Личный кабинет"></x-front.head>
@vite('resources/css/user.css')

<body class="p-cart">
    <x-front.header :categoriesInCatalogMenu="$categoriesInCatalogMenu" :categoriesInHeaderMenu="$categoriesInHeaderMenu" :headerInfo="$headerInfo" :cart="$cart"></x-front.header>

    <main class="layout customer-account">
        <section class="wrapper">
            <div class="customer-account__heading">
                <div>
                    <h1 class="title">Личный кабинет</h1>
                    <p>{{ $user->name }}, здесь хранятся ваши заказы и избранные товары.</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="customer-account__logout" type="submit">Выйти</button>
                </form>
            </div>

            <div class="customer-account__tabs" role="tablist">
                <button class="is-active" type="button" data-account-tab="orders">Заказы ({{ $orders->count() }})</button>
                <button type="button" data-account-tab="favorites">Избранное ({{ $favoriteProducts->count() }})</button>
                <a href="{{ route('cart.show') }}">Корзина</a>
            </div>

            <div class="customer-account__panel is-active" data-account-panel="orders">
                @forelse ($orders as $order)
                    <article class="customer-order">
                        <header>
                            <div>
                                <strong>Заказ №{{ $order->id }}</strong>
                                <span>{{ $order->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                            <span class="customer-order__status">{{ $order->status_label }}</span>
                        </header>

                        <div class="customer-order__items">
                            @foreach ($order->normalized_items as $item)
                                @include('partials.order-item-details', ['item' => $item])
                            @endforeach
                        </div>

                        <dl class="customer-order__meta">
                            <div>
                                <dt>Получатель</dt>
                                <dd>
                                    {{ trim(data_get($order->customer_details, 'name', '') . ' ' . data_get($order->customer_details, 'secondname', '')) }}
                                </dd>
                            </div>
                            <div>
                                <dt>Телефон</dt>
                                <dd>{{ data_get($order->customer_details, 'phone', 'Не указан') }}</dd>
                            </div>
                            <div>
                                <dt>Адрес</dt>
                                <dd>{{ data_get($order->customer_details, 'addres', 'Не указан') ?: 'Не указан' }}</dd>
                            </div>
                            <div>
                                <dt>Комментарий</dt>
                                <dd>{{ $order->comment ?: 'Нет' }}</dd>
                            </div>
                        </dl>

                        <footer>
                            <span>
                                {{ $order->delivery_label }}
                                @if ((float) $order->delivery_cost > 0)
                                    ({{ number_format($order->delivery_cost, 0, ',', ' ') }} ₽)
                                @endif
                            </span>
                            <strong>{{ number_format($order->total_price, 0, ',', ' ') }} ₽</strong>
                        </footer>
                    </article>
                @empty
                    <div class="customer-account__empty">У вас пока нет заказов.</div>
                @endforelse
            </div>

            <div class="customer-account__panel" data-account-panel="favorites">
                <div class="customer-favorites">
                    @forelse ($favoriteProducts as $product)
                        @php
                            $productUrl = $product->category && $product->subcategory
                                ? route('product.show', [
                                    'category_slug' => $product->category->slug,
                                    'subcategory_slug' => $product->subcategory->slug,
                                    'product_slug' => $product->slug,
                                ])
                                : '#';
                            $imagePath = $product->image_thumb_path ?: $product->image_path;
                            $imageUrl = $imagePath
                                ? (str_starts_with(ltrim($imagePath, '/'), 'storage/')
                                    ? asset(ltrim($imagePath, '/'))
                                    : Storage::url($imagePath))
                                : null;
                        @endphp
                        <article class="customer-favorite" data-favorite-card="{{ $product->id }}">
                            <a href="{{ $productUrl }}">
                                @if ($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $product->h1 ?: $product->title }}">
                                @endif
                                <strong>{{ $product->h1 ?: $product->title }}</strong>
                            </a>
                            <button type="button" data-favorite-product="{{ $product->id }}" aria-label="Удалить из избранного">
                                <i class="fas fa-heart"></i>
                            </button>
                        </article>
                    @empty
                        <div class="customer-account__empty">Добавляйте товары сердечком, и они появятся здесь.</div>
                    @endforelse
                </div>
            </div>
        </section>
    </main>

    <x-front.footer :headerInfo="$headerInfo" :curtainSubcats="$curtainSubcats" :blindSubcats="$blindSubcats"></x-front.footer>
    <x-front.popups></x-front.popups>

    <style>
        .customer-account { padding: 55px 0 90px; }
        .customer-account__heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; }
        .customer-account__heading p { margin-top: 12px; color: #5e6875; }
        .customer-account__logout { border: 1px solid #d6dce3; background: #fff; padding: 10px 18px; cursor: pointer; }
        .customer-account__tabs { display: flex; gap: 8px; margin: 34px 0 24px; border-bottom: 1px solid #dfe3e8; }
        .customer-account__tabs button, .customer-account__tabs a { border: 0; background: transparent; color: #52606d; padding: 13px 18px; font: inherit; cursor: pointer; text-decoration: none; }
        .customer-account__tabs .is-active { color: #111820; border-bottom: 2px solid #0989ff; }
        .customer-account__panel { display: none; }
        .customer-account__panel.is-active { display: block; }
        .customer-order { border-top: 1px solid #dfe3e8; padding: 22px 0; }
        .customer-order header, .customer-order footer { display: flex; align-items: center; justify-content: space-between; gap: 18px; }
        .customer-order header div { display: flex; gap: 14px; align-items: baseline; }
        .customer-order header span, .customer-order footer span { color: #66727f; }
        .customer-order__status { color: #087cea !important; font-weight: 700; }
        .customer-order__items { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 40px; padding: 20px 0; }
        .customer-order__meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px 40px; margin: 0 0 20px; padding: 16px 0; border-top: 1px solid #eef1f4; }
        .customer-order__meta div { display: grid; grid-template-columns: minmax(110px, .6fr) 1.4fr; gap: 12px; }
        .customer-order__meta dt { color: #687480; }
        .customer-order__meta dd { margin: 0; }
        .order-item-details__title { display: block; margin-bottom: 10px; }
        .order-item-details dl { margin: 0; }
        .order-item-details dl div { display: grid; grid-template-columns: minmax(130px, .8fr) 1.2fr; gap: 12px; padding: 4px 0; }
        .order-item-details dt { color: #687480; }
        .order-item-details dd { margin: 0; }
        .customer-favorites { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; }
        .customer-favorite { position: relative; border: 1px solid #e0e5ea; }
        .customer-favorite a { display: block; color: #111820; text-decoration: none; }
        .customer-favorite img { display: block; width: 100%; aspect-ratio: 1 / 1; object-fit: cover; }
        .customer-favorite strong { display: block; padding: 14px 46px 14px 14px; }
        .customer-favorite button { position: absolute; right: 10px; bottom: 9px; width: 34px; height: 34px; border: 0; background: transparent; color: #0989ff; cursor: pointer; }
        .customer-account__empty { padding: 42px 0; color: #66727f; }
        @media (max-width: 800px) {
            .customer-account { padding-top: 32px; }
            .customer-account__heading { align-items: center; gap: 12px; }
            .customer-account__heading .title { font-size: 30px; line-height: 1.15; }
            .customer-account__tabs { overflow-x: auto; }
            .customer-account__tabs button, .customer-account__tabs a { white-space: nowrap; padding-inline: 12px; }
            .customer-order header { flex-direction: column; align-items: flex-start; gap: 8px; }
            .customer-order footer { align-items: flex-start; }
            .customer-order header div { flex-direction: column; gap: 4px; }
            .customer-order__status { align-self: flex-start; }
            .customer-order__items { grid-template-columns: 1fr; }
            .customer-order__meta { grid-template-columns: 1fr; }
            .customer-order__meta div { grid-template-columns: 1fr; gap: 2px; }
            .customer-favorites { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .order-item-details dl div { grid-template-columns: 1fr; gap: 2px; }
        }
    </style>

    @vite('resources/js/main.js')
    <script src="https://kit.fontawesome.com/9d3fa3c0db.js" crossorigin="anonymous"></script>
    <script>
        document.querySelectorAll('[data-account-tab]').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('[data-account-tab]').forEach((item) => item.classList.remove('is-active'));
                document.querySelectorAll('[data-account-panel]').forEach((item) => item.classList.remove('is-active'));
                button.classList.add('is-active');
                document.querySelector(`[data-account-panel="${button.dataset.accountTab}"]`)?.classList.add('is-active');
            });
        });
    </script>
</body>
</html>
