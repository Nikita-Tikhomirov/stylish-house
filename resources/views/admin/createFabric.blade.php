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
                <form id="fabricForm" action="{{ route('admin.fabrics.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="name">Название ткани</label>
                        <input id="name" name="name" type="text" class="form-control" value="">
                    </div>

                    <div class="form-group">
                        <label for="image">Фото ткани</label>
                        <input name="image" type="file" class="form-control">


                    </div>

                    <button class="btn btn-primary" type="submit" id="">Сохранить</button>

                    <div id="errorContainer"></div>
                </form>

                <div id="successMessage" style="display: none; color: green;">Ткань успешно добавлена!</div>


            </div>

        </div>
    </div>
</div>

<script>
    document.getElementById('fabricForm').addEventListener('submit', function (e) {
    e.preventDefault();

    let formData = new FormData(this);
    let errorContainer = document.getElementById('errorContainer');
    let successMessage = document.getElementById('successMessage');

    errorContainer.innerHTML = '';
    successMessage.style.display = 'none';

    fetch("{{ route('admin.fabrics.store') }}", {
        method: "POST",
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.errors) {
            let errorHtml = '<ul>';
            for (let key in data.errors) {
                errorHtml += `<li>${data.errors[key]}</li>`;
            }
            errorHtml += '</ul>';
            errorContainer.innerHTML = errorHtml;
        } else {
            successMessage.style.display = 'block';
            document.getElementById('fabricForm').reset();
        }
    });
});

</script>



<x-admin.footer></x-admin.footer>
