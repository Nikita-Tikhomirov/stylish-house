<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<section class="navigation-editor" data-navigation-editor>
    <header class="navigation-editor__header">
        <div>
            <p class="navigation-editor__eyebrow">Шапка сайта</p>
            <h1>Структура меню</h1>
            <p>Соберите вкладки, колонки и ссылки. Изменения появятся на сайте после сохранения.</p>
        </div>
        <div class="navigation-editor__actions">
            <button type="button" class="btn btn-outline-primary" data-add-root="quick">+ Быстрая ссылка</button>
            <button type="button" class="btn btn-outline-primary" data-add-root="utility">+ Служебная ссылка</button>
            <button type="button" class="btn btn-primary" data-add-root="mega">+ Вкладка каталога</button>
        </div>
    </header>

    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Меню не сохранено.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.navigation.update') }}" data-navigation-form>
        @csrf
        @method('PUT')
        <input type="hidden" name="menu_structure" data-navigation-payload>

        <div class="navigation-editor__layout">
            <div>
                <div class="navigation-editor__legend">
                    <span>Перетаскивайте карточки за маркер</span>
                    <span>Иерархия: вкладка → колонка → ссылка</span>
                </div>
                <div data-navigation-tree></div>
            </div>

            <aside class="navigation-preview" aria-label="Предпросмотр меню">
                <div class="navigation-preview__title">Предпросмотр</div>
                <div data-navigation-preview></div>
            </aside>
        </div>

        <div class="navigation-editor__savebar">
            <span data-navigation-count></span>
            <button type="submit" class="btn btn-primary btn-lg">Сохранить меню</button>
        </div>
    </form>

    <script type="application/json" data-navigation-initial>{!! json_encode(old('items', $editorItems), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <script type="application/json" data-navigation-sources>{!! json_encode($navigationSources, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
</section>

<x-admin.footer></x-admin.footer>
