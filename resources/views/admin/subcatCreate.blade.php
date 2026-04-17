<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Создать подкатегорию</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Первый экран</h5>
            <div class="card-body">
                <form action="{{ route('subcategories.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="title">Заголовок(meta)</label>
                        <input id="title" name="title" type="text" class="form-control" value="">
                    </div>
                    <div class="form-group">
                        <label for="description">Описание(meta)</label>
                        <input id="description" name="description" type="text" class="form-control" value="">
                    </div>
                    <div class="form-group">
                        <label for="slug">slug</label>
                        <input id="slug" name="slug" type="text" class="form-control" value="">
                    </div>
                    <div class="form-group">
                        <label for="titleh1">Заголовок h1</label>
                        <input id="titleh1" name="titleh1" type="text" class="form-control" value="">
                    </div>

                    <div class="form-group">
                        <label for="first_screen_text">Текст</label>
                        <textarea class="form-control" name="first_screen_text" id="first_screen_text"
                            name="first_screen_text"></textarea>

                    </div>
                    <div class="form-group">
                        <label for="img">Фото категории</label>
                        <input name="img" type="file" class="form-control">


                    </div>


                    <div class="form-group">
                        <label for="category_id">Выберите родительскую категорию</label>
                        <select id="category_id" name="category_id" class="form-control">
                            <option value="">-- Выберите категорию --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit" id="">Сохранить</button>


                </form>
            </div>

        </div>
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Показываем ошибки валидации --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>






<x-admin.footer></x-admin.footer>