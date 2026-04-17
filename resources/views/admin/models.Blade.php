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

    .cards-wrap {
        background: #fff;
        /* padding: 20px; */
    }

    .cardsHeader {
        display: grid;
        grid-template-columns: 0.2fr 0.4fr 2fr 0.5fr 0.5fr;
        background: #f9f9ff;
    }

    .cardsHeader__title {
        text-align: center;
        padding: 10px;
    }

    .cardsHeader__title:nth-child(1),
    .cardsHeader__title:nth-child(2),
    .cardsHeader__title:nth-child(3) {
        text-align: left;
    }

    .catCard_catWrap {
        display: grid;
        grid-template-columns: 0.2fr 0.4fr 2fr 0.5fr 0.5fr;
    }

    .cardCell {
        padding: 10px;
    }

    .catCard__subCatsWrap {

        display: none;
    }

    .catCard__subCatsWrap.active {
        display: grid;

    }

    .control {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .control .badge-info {
        cursor: pointer;
        transition: 0.2s
    }

    .control .badge-info.active i {
        transform: rotate(90deg)
    }

    .cardCell {
        display: flex;
        align-items: center;
    }

    .cardCell img {
        max-width: 50px;
        height: auto;
    }
</style>
<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Модели</h1>
            <div class="add-category">
                <a href="{{ route('model.create') }}" class="btn-sm btn-primary">Добавить модель</a>


            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-blok">
            <div class="cards-wrap">
                <div class="cardsHeader">
                    <div class="cardsHeader__title">Id</div>
                    <div class="cardsHeader__title">Фото</div>
                    <div class="cardsHeader__title">Название</div>
                    <div class="cardsHeader__title">Редактировать</div>
                    <div class="cardsHeader__title">Удалить</div>
                </div>
                @foreach ($models as $model)
                    <div class="catCard">
                        <div class="catCard_catWrap">
                            <div class="cardCell">{{ $model->id }}</div>
                            <div class="cardCell"><img src="{{ Storage::url($model->image) }}" alt=""></div>
                            <div class="cardCell">
                                <a href="">
                                    {{ $model->h1 }}
                                </a>
                            </div>

                            <div class="cardCell control">
                                <a href="{{ route('model.edit', $model->id) }}" class="badge badge-primary">Редактировать</a>
                            </div>
                            <div class="cardCell control">
                                <button
                                    type="button"
                                    class="badge badge-secondary delete-model"
                                    data-id="{{ $model->id }}">Удалить
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach




            </div>


        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
    const deletemodelButtons = document.querySelectorAll('.delete-model');

    deletemodelButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const modelId = button.getAttribute('data-id');

            // Подтверждение удаления
            if (confirm('Вы уверены, что хотите удалить эту модель?')) {
                try {
                    // Отправка DELETE запроса
                    const response = await fetch(`/admin/models/destroy/${modelId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    });

                    // Если запрос прошел успешно
                    if (response.ok) {
                        const modelWrap = button.closest('.catCard');
                        modelWrap.remove(); // Удаление модели из DOM
                    } else {
                        alert('Ошибка при удалении модели.');
                    }
                } catch (error) {
                    console.error('Ошибка:', error);
                    alert('Ошибка при удалении модели.');
                }
            }
        });
    });
});

</script>


<x-admin.footer></x-admin.footer>

