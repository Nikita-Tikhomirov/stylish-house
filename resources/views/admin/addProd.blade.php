<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Создать товар</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Первый экран</h5>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul style="margin: 0; padding-left: 18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('product.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="title">Заголовок(meta)</label>
                        <input id="title" name="title" type="text"
                            class="form-control" value="{{ old('title') }}">
                    </div>
                    <div class="form-group">
                        <label for="description">Описание(meta)</label>
                        <input id="description" name="description" type="text"
                            class="form-control" value="{{ old('description') }}">
                    </div>
                    <div class="form-group">
                        <label for="slug">slug</label>
                        <input id="slug" name="slug" type="text"
                            class="form-control" value="{{ old('slug') }}">
                    </div>
                    <div class="form-group">
                        <label for="h1">Заголовок h1</label>
                        <input id="h1" name="h1" type="text"
                            class="form-control" value="{{ old('h1') }}">
                    </div>
                    <div class="form-group">
                        <label for="coef">Коэффициент</label>
                        <input id="coef" name="coef" type="text"
                            class="form-control" value="{{ old('coef') }}">
                    </div>

                    <div class="form-group">
                        <label for="first_screenn_description">Текст</label>
                        <textarea class="form-control" name="first_screenn_description" id="first_screenn_description">{{ old('first_screenn_description') }}</textarea>

                    </div>

                    <div class="form-group">
                        <label for="subcategory_id">Выберите подкатегорию</label>
                        <select id="subcategory_id" name="subcategory_id" class="form-control">
                            <option value="">-- Выберите подкатегорию --</option>
                            @foreach($subcategories as $subcategory)
                                <option value="{{ $subcategory->id }}" {{ (string) old('subcategory_id') === (string) $subcategory->id ? 'selected' : '' }}>{{ $subcategory->titleh1 }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button class="btn btn-primary" type="submit" id="">Сохранить</button>


                </form>
            </div>

        </div>
    </div>
</div>





<x-admin.footer></x-admin.footer>
