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

    .order-item-details {
        padding: 12px 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .order-item-details dl {
        margin: 8px 0 0;
    }

    .order-item-details dl div {
        display: grid;
        grid-template-columns: minmax(120px, .8fr) 1.2fr;
        gap: 10px;
        padding: 2px 0;
    }

    .order-item-details dt {
        color: #6b7280;
    }

    .order-item-details dd {
        margin: 0;
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

    .cards-wrap {
        overflow-x: auto;
        border: 1px solid #e6e8ef;
        border-radius: 6px;
    }

    .cardsHeader,
    .catCard_catWrap {
        min-width: 1120px;
        grid-template-columns: 56px 140px 150px minmax(190px, 1fr) 110px minmax(240px, 1.2fr) 90px;
        align-items: center;
    }

    .catCard {
        border-bottom: 1px solid #edf0f5;
    }

    .catCard:last-child {
        border-bottom: 0;
    }

    .cardCell {
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .orderInfo {
        height: auto;
        overflow: visible;
        align-items: center;
    }

    .orderInfoToggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 34px;
        padding: 6px 12px;
        color: #334155;
        font-weight: 600;
        background: #f4f6ff;
        border: 1px solid #dbe1ff;
        border-radius: 4px;
    }

    .orderInfoToggle__icon {
        color: #5969ff;
        font-size: 18px;
        line-height: 1;
        transition: transform .2s;
    }

    .orderInfoToggle.is-open .orderInfoToggle__icon {
        transform: rotate(90deg);
    }

    .orderInfoPanel {
        display: none;
        min-width: 1120px;
        grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
        gap: 24px;
        padding: 20px 24px;
        background: #f8fafc;
        border-top: 1px solid #e6e8ef;
    }

    .orderInfoPanel.is-open {
        display: grid;
    }

    .orderInfoPanel__items {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .orderInfoPanel .order-item-details {
        padding: 14px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
    }

    .orderInfoPanel .order-item-details__title {
        display: block;
        margin-bottom: 8px;
        color: #1e293b;
    }

    .orderInfoPanel__meta {
        margin: 0;
        padding: 14px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
    }

    .orderInfoPanel__meta div {
        display: grid;
        grid-template-columns: 110px minmax(0, 1fr);
        gap: 10px;
        padding: 5px 0;
    }

    .orderInfoPanel__meta dt {
        color: #64748b;
        font-weight: 600;
    }

    .orderInfoPanel__meta dd {
        margin: 0;
        overflow-wrap: anywhere;
    }

    @media (max-width: 1350px) {
        .orderInfoPanel__items {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767px) {
        .cards-wrap {
            overflow: visible;
            border: 0;
            background: transparent;
        }

        .cardsHeader {
            display: none;
        }

        .catCard {
            margin-bottom: 16px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e6e8ef;
            border-radius: 6px;
        }

        .catCard_catWrap {
            min-width: 0;
            grid-template-columns: 1fr 1fr;
            align-items: stretch;
        }

        .cardCell {
            display: block;
            padding: 10px 12px;
            border-bottom: 1px solid #edf0f5;
        }

        .cardCell::before {
            display: block;
            margin-bottom: 3px;
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .cardCell:nth-child(1)::before { content: 'Заказ'; }
        .cardCell:nth-child(2)::before { content: 'Покупатель'; }
        .cardCell:nth-child(3)::before { content: 'Телефон'; }
        .cardCell:nth-child(4)::before { content: 'Состав'; }
        .cardCell:nth-child(5)::before { content: 'Стоимость'; }
        .cardCell:nth-child(6)::before { content: 'Статус'; }
        .cardCell:nth-child(7)::before { content: 'Действия'; }

        .cardCell:nth-child(3),
        .cardCell:nth-child(4),
        .cardCell:nth-child(6) {
            grid-column: 1 / -1;
        }

        .orderInfoToggle,
        .order-status {
            width: 100%;
        }

        .orderInfoToggle {
            justify-content: space-between;
        }

        .orderPrice {
            text-align: left;
        }

        .orderInfoPanel {
            min-width: 0;
            grid-template-columns: 1fr;
            gap: 12px;
            padding: 12px;
        }

        .orderInfoPanel__items,
        .orderInfoPanel__meta {
            min-width: 0;
        }

        .orderInfoPanel__meta div,
        .order-item-details dl div {
            grid-template-columns: 1fr;
            gap: 2px;
        }
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
                                {{ trim(
                                    data_get($order->customer_details, 'name', $order->user->name ?? 'Покупатель')
                                    . ' '
                                    . data_get($order->customer_details, 'secondname', $order->user->secondname ?? '')
                                ) }}
                            </div>

                            <div class="cardCell">
                                {{ data_get($order->customer_details, 'phone', $order->user->phone ?? 'Не указан') }}
                            </div>

                            <div class="cardCell orderInfo">
                                <button class="orderInfoToggle" type="button" aria-expanded="false">
                                    Товаров: {{ count($order->normalized_items) }}
                                    <span class="orderInfoToggle__icon">›</span>
                                </button>
                            </div>



                            <div class="cardCell orderPrice">
                                {{ number_format($order->total_price, 0, ',', ' ') }} ₽
                            </div>

                            <div class="cardCell">
                                <select name="status" class="form-control order-status"
                                    data-order-id="{{ $order->id }}">
                                    @foreach (\App\Models\Order::STATUSES as $status => $label)
                                        <option value="{{ $status }}" {{ (int) $order->status === $status ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
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
                        <div class="orderInfoPanel">
                            <div class="orderInfoPanel__items">
                                @forelse ($order->normalized_items as $item)
                                    @include('partials.order-item-details', ['item' => $item])
                                @empty
                                    <p>Товары отсутствуют.</p>
                                @endforelse
                            </div>
                            <dl class="orderInfoPanel__meta">
                                <div>
                                    <dt>Доставка</dt>
                                    <dd>
                                        {{ $order->delivery_label }}
                                        @if ((float) $order->delivery_cost > 0)
                                            ({{ number_format($order->delivery_cost, 0, ',', ' ') }} ₽)
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt>Адрес</dt>
                                    <dd>{{ data_get($order->customer_details, 'addres', $order->user->addres ?? 'Не указан') }}</dd>
                                </div>
                                <div>
                                    <dt>Email</dt>
                                    <dd>{{ data_get($order->customer_details, 'email', $order->user->email ?? 'Не указан') }}</dd>
                                </div>
                                <div>
                                    <dt>Комментарий</dt>
                                    <dd>{{ $order->comment ?: 'Нет' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                @endforeach




            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.order-status').forEach((select) => {
        select.addEventListener('change', function() {
        const orderId = this.getAttribute('data-order-id');
        const status = this.value;

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
    });

    document.querySelectorAll('.orderInfoToggle').forEach(button => {
        button.addEventListener('click', () => {
            const panel = button.closest('.catCard').querySelector('.orderInfoPanel');
            const isOpen = panel.classList.toggle('is-open');

            button.classList.toggle('is-open', isOpen);
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
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
                        body: JSON.stringify({})
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
