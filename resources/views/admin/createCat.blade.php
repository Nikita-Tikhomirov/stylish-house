<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>

<style>
    .deliveryCards {
        margin-top: 20px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-start;
        width: 100%;
    }

    .deliveryCards .card-header {
        width: 100%;

    }

    .deliveryCards_card {
        width: 100%;
    }

    .deliveryCards__addCard {
        margin-top: 20px;
        order: 10
    }

    .deliveryCards_delete {
        color: #ff407b !important;
    }

    .deliveryCards_delete:hover {
        color: #fff !important
    }

    .faq {
        display: flex;
        flex-direction: column;
        width: 100%;
        align-items: flex-start;
        justify-content: flex-start;

    }

    .add-faq-button {
        order: 10;
        margin-top: 20px;
    }

    .faq-cards-container {
        width: 100%;
    }

    .faq-card:not(:last-child) {
        margin-bottom: 20px;
    }
</style>
<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Создать категорию</h1>
        </div>
    </div>
</div>



<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

        <div class="card">
            <h5 class="card-header">Первый экран</h5>
            <div class="card-body">
                <form action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="title">Заголовок(meta)</label>
                        <input id="title" name="title" type="text"
                            class="form-control" value="">
                    </div>
                    <div class="form-group">
                        <label for="description">Описание(meta)</label>
                        <input id="description" name="description" type="text"
                            class="form-control" value="">
                    </div>
                    <div class="form-group">
                        <label for="slug">slug</label>
                        <input id="slug" name="slug" type="text"
                            class="form-control" value="">
                    </div>
                    <div class="form-group">
                        <label for="titleh1">Заголовок h1</label>
                        <input id="titleh1" name="titleh1" type="text"
                            class="form-control" value="">
                    </div>

                    <div class="form-group">
                        <label for="first_screen_text">Текст</label>
                        <textarea class="form-control" name="first_screen_text" id="first_screen_text" name="first_screen_text"></textarea>
                        {{-- <input id="first_screen_text" name="first_screen_text" type="text" class="form-control"
                            value=""> --}}
                    </div>
                    <div class="form-group">
                        <label for="img">Фото категории</label>
                        <input name="img" type="file" class="form-control">


                    </div>

                    <div class="form-group">
                        <label for="subcat_title">Заголовок для блока с подкатегорияи</label>
                        <input id="subcat_title" name="subcat_title" type="text" class="form-control"
                            value="">
                    </div>
                    <button class="btn btn-primary" type="submit" id="">Сохранить</button>


                </form>
            </div>

        </div>
    </div>
</div>





<x-admin.footer></x-admin.footer>
