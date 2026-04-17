<x-admin.head></x-admin.head>
<x-admin.header></x-admin.header>
<x-admin.sidebar></x-admin.sidebar>


<style>
    .deleteOrderBtn{
        cursor: pointer;
    }
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
        grid-template-columns: 0.2fr 0.4fr 1fr 3fr 1fr 2fr 0.5fr;
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
        grid-template-columns: 0.2fr 0.4fr 1fr 3fr 1fr 2fr 0.5fr;
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
        width: 100%;
    }

    .cardCell img {
        max-width: 50px;
        height: auto;
    }

    .subcategory-row {
        display: grid;
        grid-template-columns: 0.2fr 0.4fr 1fr 3fr 1fr 2fr 0.5fr;

    }

    .orderPrice {
        text-align: center;
        justify-content: center;
    }

    .orderInfo {
        position: relative;
        height: 36px;
        overflow: hidden;
        display: flex;
        align-content: flex-start;
        justify-content: flex-start;
        align-items: flex-start;
    }

    .orderInfo.active {
        height: auto
    }

    .orderInfo.active .orderInfoArrow {
        transform: translateY(-3px) rotate(90deg)
    }

    .orderInfo ul {
        list-style: none;
        padding-left: 0;
    }

    .orderInfo ul li {
        margin-bottom: 4px;
    }

    .orderInfoArrow {
        /* position: absolute; */
        cursor: pointer;
        transform: translateY(-3px);
        margin-left: 20px;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        width: 25px;
        height: 25px;
        min-width: 25px;
        border-radius: 50%;
        background-color: #5969ff;
        color: #fff
    }
</style>

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="section-block" id="basicform" tabindex="-1">
            <h1 class="section-title">Заказы</h1>
            <div class="cards-wrap">
                <div class="cardsHeader">
                    <div class="cardsHeader__title">Id</div>
                    <div class="cardsHeader__title">Имя</div>
                    <div class="cardsHeader__title">Номер</div>
                    <div class="cardsHeader__title">Информация</div>
                    <div class="cardsHeader__title">стоимость</div>
                    <div class="cardsHeader__title">Статус</div>
                    <div class="cardsHeader__title">Удалить</div>
                </div>
                @foreach ($orders as $order)
                    <div class="catCard">
                        <div class="catCard_catWrap">
                            <div class="cardCell">{{ $order->id }}</div>

                            <div class="cardCell">
                                <a href="">
                                    {{ $order->user->name }}
                                </a>
                            </div>

                            <div class="cardCell">
                                {{ $order->user->phone }}
                            </div>

                            <div class="cardCell orderInfo">
                                @php
                                    $items = is_string($order->items)
                                        ? json_decode($order->items, true)
                                        : $order->items;
                                @endphp

                                @if (!empty($order->items))
                                    <ul>
                                        @foreach ($order->items as $item)
                                            <li>
                                                <strong>Товар:</strong> {{ $item['productName'] }}<br>
                                                <strong>Ширина:</strong> {{ $item['width'] }} мм<br>
                                                <strong>Высота:</strong> {{ $item['height'] }} мм<br>
                                                <strong>Управление:</strong> {{ $item['control'] ? 'Да' : 'Нет' }}<br>
                                                <strong>Количество:</strong> {{ $item['quantity'] }}<br>
                                                <strong>Цена:</strong> {{ $item['price'] }} ₽
                                            </li>
                                        @endforeach
                                        <strong>{{ $order->user->addres }}</strong>
                                    </ul>
                                @else
                                    <p>Товары отсутствуют.</p>
                                @endif
                                <div class="orderInfoArrow">></div>
                            </div>



                            <div class="cardCell orderPrice">
                                {{ $order->total_price }}
                            </div>

                            <div class="cardCell">
                                <select name="status" id="status" class="form-control"
                                    data-order-id="{{ $order->id }}">
                                    <option value="1" {{ $order->status == 1 ? 'selected' : '' }}>Заказ
                                        обрабатывается</option>
                                    <option value="2" {{ $order->status == 2 ? 'selected' : '' }}>Заказ в
                                        производстве</option>
                                    <option value="3" {{ $order->status == 3 ? 'selected' : '' }}>Заказ на складе
                                    </option>
                                    <option value="4" {{ $order->status == 4 ? 'selected' : '' }}>Заказ у клиента
                                    </option>
                                </select>

                            </div>

                            <div class="cardCell">

                                <div class="badge badge-secondary delete deleteOrderBtn"
                                    data-order-id="{{ $order->id }}">Удалить</div>
                            </div>
                            {{--
                            <div class="cardCell control">
                                <a href="{{ route('product.index', $prod->slug) }}"
                                    class="badge badge-primary">Редактировать</a>
                            </div>
                            <div class="cardCell control">
                                <a href="{{ route('product.destroy', $prod->slug) }}"
                                    class="badge badge-secondary delete-category">Удалить</a>
                            </div> --}}

                        </div>
                    </div>
                @endforeach




            </div>
        </div>
    </div>
</div>

<script>
    document.querySelector('#status').addEventListener('change', function() {
        const orderId = this.getAttribute('data-order-id'); // Получаем ID заказа
        const status = this.value; // Получаем выбранный статус

        fetch(`/orders/${orderId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                        'content')
                },
                body: JSON.stringify({
                    status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Статус заказа обновлен!');
                } else {
                    alert('Ошибка обновления статуса!');
                }
            })
            .catch(error => console.error('Ошибка:', error));
    });

    let infoArrows = document.querySelectorAll('.orderInfoArrow')
    infoArrows.forEach(element => {
        element.addEventListener('click', () => {
            element.parentElement.classList.toggle('active')
        })
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.deleteOrderBtn')

        deleteButtons.forEach(button => {
            button.addEventListener('click', () => {
                const orderId = button.getAttribute('data-order-id'); // Получаем ID заказа


                fetch(`/orders/${orderId}/delete`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify({
                            status
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Заказ удален!');
                            let cardNow = button.parentElement.parentElement.parentElement
                            cardNow.remove()
                        } else {
                            alert('Ошибка удаления заказа!');
                        }
                    })
                    .catch(error => console.error('Ошибка:', error));
            })
        })
    })
</script>

<x-admin.footer></x-admin.footer>
