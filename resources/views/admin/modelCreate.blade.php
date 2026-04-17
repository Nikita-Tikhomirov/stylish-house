<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Создать модель</h1>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"
    integrity="sha512-CeIsOAsgJnmevfCi2C7Zsyy6bQKi43utIjdA87Q0ZY84oDqnI0uwfM9+bKiIkI75lUeI00WG/+uJzOmuHlesMA=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Параметры</h5>
            <div class="card-body">
                <form action="{{ route('model.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="title">Название в xls таблице</label>
                        <input id="title" name="title" type="text" class="form-control" value="">
                    </div>
                    <div class="form-group">
                        <label for="h1">Название на сайте</label>
                        <input id="h1" name="h1" type="text" class="form-control"
                            value="">
                    </div>
                    <div class="form-group">
                        <label for="image">Загрузить изображение</label>
                        <input type="file" id="image-loader" name="image" class="form-control">
                    </div>

                    <button class="btn btn-primary" type="submit" id="">Сохранить</button>
                </form>

            </div>

        </div>
    </div>
</div>






<x-admin.footer></x-admin.footer>
