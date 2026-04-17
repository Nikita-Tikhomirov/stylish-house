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
        grid-template-columns: 0.2fr 0.4fr 2fr 0.5fr 0.5fr 0.5fr;
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
        grid-template-columns: 0.2fr 0.4fr 2fr 0.5fr 0.5fr 0.5fr;
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
    .subcategory-row{
        display: grid;
        grid-template-columns: 0.2fr 0.4fr 2fr 0.5fr 0.5fr 0.5fr;

    }
</style>
<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Категории</h1>
            <div class="add-category">
                <a href="{{ route('categories.create') }}" class="btn-sm btn-primary">Добавить категорию</a>
                <a href="{{ route('subcategories.create') }}" class="btn-sm btn-brand">Добавить Подкатегорию</a>

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
                    <div class="cardsHeader__title">Подкатегории</div>
                </div>
                @foreach ($cats as $cat)
                    <div class="catCard">
                        <div class="catCard_catWrap">
                            <div class="cardCell">{{ $cat->id }}</div>
                            <div class="cardCell"><img src="{{ Storage::url($cat->img) }}" alt=""></div>
                            <div class="cardCell">
                                <a href="{{ route('category.show', ['slug' => $cat->slug]) }}">
                                    {{ $cat->titleh1 }}
                                </a>
                            </div>
                            <div class="cardCell control">
                                <!-- Ссылка на редактирование категории -->
                                <a href="{{ route('categories.edit', ['slug' => $cat->slug]) }}"
                                    class="badge badge-primary">Редактировать</a>
                            </div>
                            <div class="cardCell control">
                                <!-- Ссылка на удаление категории -->
                                <button class="badge badge-secondary delete-category"
                                    data-slug="{{ $cat->slug }}">Удалить</button>
                            </div>
                            <div class="cardCell control">
                                <div class="badge badge-info">Подкатегории <i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                        <div class="catCard__subCatsWrap alert-primary">
                            @foreach ($cat->subcategories as $subcat)
                                <div class="subcategory-row" id="subcategory-{{ $subcat->id }}">
                                    <div class="cardCell">{{ $subcat->id }}</div>
                                    <div class="cardCell">
                                        <img src="{{ Storage::url($subcat->img) }}" alt="">
                                    </div>
                                    <div class="cardCell">
                                        <a
                                            href="{{ route('subcategory.show', ['category_slug' => $cat->slug, 'subcategory_slug' => $subcat->slug]) }}">
                                            {{ $subcat->titleh1 }}
                                        </a>
                                    </div>
                                    <div class="cardCell control">
                                        <a href="{{ route('subcategories.edit', ['category_slug' => $cat->slug, 'subcategory_slug' => $subcat->slug]) }}"
                                            class="badge badge-primary">
                                            Редактировать
                                        </a>
                                    </div>
                                    <div class="cardCell control">
                                        <button type="button" class="badge badge-secondary"
                                            onclick="deleteSubcategory('{{ route('subcategories.destroy', ['category_slug' => $cat->slug, 'subcategory_slug' => $subcat->slug]) }}', {{ $subcat->id }})">
                                            Удалить
                                        </button>
                                    </div>
                                </div>
                            @endforeach


                        </div>
                    </div>
                @endforeach



            </div>


        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        const buttonsOpenSubcats = document.querySelectorAll('.control .badge-info')
        buttonsOpenSubcats.forEach(element => {
            element.addEventListener('click', () => {
                element.classList.toggle('active')
                const subcat = element.parentElement.parentElement.nextElementSibling
                subcat.classList.toggle('active')
            })
        });
    })
</script>

{{-- Удалить категорию --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.delete-category').forEach(button => {
            button.addEventListener('click', function() {
                let slug = this.getAttribute('data-slug');

                if (confirm('Вы уверены, что хотите удалить эту категорию?')) {
                    fetch(`/admin/categories/${slug}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').getAttribute('content'),
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.closest('.catCard')
                                    .remove(); // Удаляем карточку категории из DOM
                            } else {
                                alert('Ошибка при удалении категории');
                            }
                        });
                }
            });
        });
    });
</script>
<script>
    function deleteSubcategory(url, subcategoryId) {
        if (confirm('Вы уверены, что хотите удалить эту подкатегорию?')) {
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}', // CSRF токен для безопасности
                }
            }).then(response => {
                if (response.ok) {
                    // Удаляем элемент с подкатегорией из DOM
                    const subcategoryRow = document.getElementById('subcategory-' + subcategoryId);
                    if (subcategoryRow) {
                        subcategoryRow.remove();
                    }
                }
            }).catch(error => {
                console.error('Ошибка при удалении подкатегории:', error);
            });
        }
    }
</script>



<x-admin.footer></x-admin.footer>
