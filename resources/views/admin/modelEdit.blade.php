<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Редактировать модель {{ $model->title }}</h1>
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
                <form id="editModelForm" action="{{ route('model.update', $model->id) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="title">Название в xls таблице</label>
                        <input id="title" name="title" type="text" class="form-control"
                            value="{{ $model->title }}">
                    </div>

                    <div class="form-group">
                        <label for="h1">Название на сайте</label>
                        <input id="h1" name="h1" type="text" class="form-control"
                            value="{{ $model->h1 }}">
                    </div>

                    <div class="form-group">
                        <label>Текущее изображение</label>
                        <div>
                            <img id="currentImage" src="{{ asset('storage/' . $model->image) }}" width="150">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="image">Загрузить новое изображение</label>
                        <input type="file" id="image-loader" name="image" class="form-control">
                        <div>
                            <img id="newImagePreview" src="" width="150" style="display:none;">
                        </div>
                    </div>

                    <button class="btn btn-primary" type="submit">Сохранить</button>
                </form>

                <div id="responseMessage"></div>


            </div>

        </div>
    </div>
</div>


<script>
    document.getElementById('editModelForm').addEventListener('submit', function(e) {
        e.preventDefault();

        let formData = new FormData(this);
        let url = this.getAttribute('action');

        fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('responseMessage').innerHTML =
                        '<div class="alert alert-success">' + data.message + '</div>';
                    document.getElementById('currentImage').src = data
                    .image_url; // Обновляем старую картинку
                } else {
                    document.getElementById('responseMessage').innerHTML =
                        '<div class="alert alert-danger">Ошибка при обновлении</div>';
                }
            })
            .catch(error => console.error('Ошибка:', error));
    });

    // Предпросмотр нового изображения перед отправкой
    document.getElementById('image-loader').addEventListener('change', function() {
        let file = this.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(e) {
                let preview = document.getElementById('newImagePreview');
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
</script>



<x-admin.footer></x-admin.footer>
