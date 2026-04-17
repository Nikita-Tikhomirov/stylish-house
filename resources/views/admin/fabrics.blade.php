<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Добавить ткань</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Инфо</h5>
            <div class="card-body">
                <form action="{{ route('admin.fabrics.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="name">Название ткани</label>
                        <input id="name" name="name" type="text" class="form-control" value="{{$fabric->name}}">
                    </div>

                    <div class="form-group">
                        <label for="image">Фото ткани</label>
                        <input name="image" type="file" class="form-control">
                        <img src="{{ Storage::url($fabric->img) }}" alt="">

                    </div>

                    <button class="btn btn-primary" type="submit" id="">Сохранить</button>


                </form>
            </div>

        </div>
    </div>
</div>





<x-admin.footer></x-admin.footer>
