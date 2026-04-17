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
    .prodsFilter{
        margin-top: 20px;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 20px;
    }
    .prodsFilter input{
        max-width: 600px;
    }
    .fabricksPagination{
        padding: 20px;
    }
</style>
<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Ткани</h1>
            <div class="add-category">
                <a href="{{ route('admin.fabrics.create') }}" class="btn-sm btn-primary">Добавить ткань</a>

            </div>
            <form id="filter-form" class="prodsFilter">
                <input type="text" name="search" id="search" placeholder="Поиск по названию"
                    class="form-control">

                <button type="submit" class="btn-sm btn-primary">Применить</button>
            </form>
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


                <div id="cloth-container">
                    @include('admin.partials.fabricks')
                </div>

                <div id="pagination" class="fabricksPagination">
                    {{ $fabricks->onEachSide(1)->links() }}
                </div>



            </div>


        </div>
    </div>
</div>

<script>

    function deleteProd() {
            document.querySelectorAll('.delete-model').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                if (!confirm('Удалить этот товар?')) return;

                let url = this.getAttribute('href');
                let card = this.closest('.catCard');

                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        card.remove();
                    } else {
                        alert('Ошибка при удалении');
                    }
                })
                .catch(error => console.error('Ошибка:', error));
            });
        });
        }
    deleteProd()


    function fetchProducts(url) {
        let formData = new FormData(document.getElementById("filter-form"));
        let query = new URLSearchParams(formData).toString();
        let fullUrl = url.includes("?") ? url + "&" + query : url + "?" + query;

        fetch(fullUrl, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById("cloth-container").innerHTML = data.fabricks;
                document.getElementById("pagination").innerHTML = data.pagination;
                deleteProd()
            });
    }

    document.getElementById("filter-form").addEventListener("submit", function(e) {
        e.preventDefault();
        fetchProducts("{{ route('admin.fabrics.index') }}");
    });

    document.body.addEventListener("click", function(e) {
        if (e.target.closest("#pagination a")) {
            e.preventDefault();
            fetchProducts(e.target.closest("#pagination a").getAttribute("href"));
        }
    });

</script>


<x-admin.footer></x-admin.footer>
