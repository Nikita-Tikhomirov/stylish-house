<x-front.head title="Оформление заказа"></x-front.head>
@vite('resources/css/checkout.css')

<body class="p-checkout">

    <x-front.header :categoriesInCatalogMenu="$categoriesInCatalogMenu" :categoriesInHeaderMenu="$categoriesInHeaderMenu" :cart="$cart" :headerInfo="$headerInfo"></x-front.header>

    <main class="layout">

        <section class="s-checkout wrapper">
            <div class="s-checkout__title title">Заказ </div>
            <div class="breadcrumbs">
                <ul class="breadcrumbs__list">
                    <li class="breadcrumbs__item"><a class="breadcrumbs__link" href="/">Главная</a></li>
                    <li class="breadcrumbs__item"><svg class="breadcrumbs__arrow" width="5" height="9"
                            viewBox="0 0 5 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#000"
                                d="M3.4575 4.5L2.97878 5.0625L5.28601e-05 8.2125L0.744734 9L5.00005 4.5L0.744733 3.72007e-07L5.2211e-05 0.7875L2.97878 3.9375L3.4575 4.5Z">
                            </path>
                        </svg><span class="breadcrumbs__active">Заказ</span></li>
                </ul>
            </div>
            <div class="s-checkout__wrap">

                {{-- <form method='POST' action='https://771897592440.server.paykeeper.ru/create/'>
                    Введите сумму оплаты:
                    <input type='text' name='sum' value='100' /> <br />
                    Введите номер заказа:
                    <input type='text' name='orderid' value='123456' /> <br />
                    Введите название услуги:
                    <input type='text' name='service_name' value='Тестовая оплата' /> <br />
                    <input type='submit' value='Перейти к оплате' />
                </form> --}}

                {{-- <form id="orderForm" class="userContacts" method="POST"
                    action="https://771897592440.server.paykeeper.ru/create/">
                    @csrf
                    <div class="userContacts__title">Оформить заказ</div>
                    <div class="userContacts__wrap">
                        <div class="userContacts__inputWrap">
                            <label> <span> Имя <span>*</span></span><input type="text" name="name"
                                    placeholder="Имя ">
                            </label>
                        </div>
                        <div class="userContacts__inputWrap"> <label> <span> Фамилия <span>*</span></span><input
                                    type="text" name="secondname" placeholder="Фамилия "></label></div>
                    </div>
                    <div class="userContacts__inputWrap"> <label> <span>Адрес </span><input type="text"
                                name="addres" placeholder="Адрес"></label></div>
                    <div class="userContacts__inputWrap"> <label> <span> Телефон <span>*</span></span><input
                                type="text" name="phone" placeholder="Телефон "></label></div>
                    <div class="userContacts__inputWrap"> <label> <span> Email <span>*</span></span><input
                                type="text" name="email" placeholder="petrov@mail.com"></label></div>
                    <div class="userContacts__inputWrap"> <label> <span>Примечания к заказу</span>
                            <textarea name="comment" placeholder="Примечания к заказу"> </textarea>
                        </label></div>
                    <input type="hidden" name="items" id="itemsInput">

                    <input style="display: none" type='text' name='sum' value='10' />
                    <input style="display: none" type='text' name='service_name'
                        value='Заказ на сайте stylish-house.net' />
                    <input style="display: none" type='text' name='client_email' value='test@gmail.com' />
                    <input style="display: none" type='text' name='client_phone' value='+79201134644' />

                    <input style="display: none" type='text' name='orderid' value='123456' />
                    <input style="display: none" type='text' name='user_result_callback'
                        value='http://stylish-house.net/profile/' />

                </form> --}}
                <form id="orderForm" class="userContacts" method="POST" action="{{ route('createOrder') }}">
                    @csrf
                    <div class="userContacts__title">Оформить заказ</div>
                    <div class="userContacts__wrap">
                        <div class="userContacts__inputWrap">
                            <label> <span> Имя <span>*</span></span><input type="text" name="name"
                                    placeholder="Имя ">
                            </label>
                        </div>
                        <div class="userContacts__inputWrap"> <label> <span> Фамилия <span>*</span></span><input
                                    type="text" name="secondname" placeholder="Фамилия "></label></div>
                    </div>
                    <div class="userContacts__inputWrap"> <label> <span>Адрес </span><input type="text"
                                name="addres" placeholder="Адрес"></label></div>
                    <div class="userContacts__inputWrap"> <label> <span> Телефон <span>*</span></span><input
                                type="text" name="phone" placeholder="Телефон "></label></div>
                    <div class="userContacts__inputWrap"> <label> <span> Email <span>*</span></span><input
                                type="text" name="email" placeholder="petrov@mail.com"></label></div>
                    <div class="userContacts__inputWrap"> <label> <span>Примечания к заказу</span>
                            <textarea name="comment" placeholder="Примечания к заказу"> </textarea>
                        </label></div>
                    <input type="hidden" name="items" id="itemsInput">

                    <input style="display: none" type='text' name='sum' value='10' />
                    <input style="display: none" type='text' name='service_name'
                        value='Заказ на сайте stylish-house.net' />
                    <input style="display: none" type='text' name='client_email' value='test@gmail.com' />
                    <input style="display: none" type='text' name='client_phone' value='+79201134644' />

                    <input style="display: none" type='text' name='orderid' value='123456' />
                    <input style="display: none" type='text' name='user_result_callback'
                        value='http://stylish-house.net/profile/' />

                    <x-front.consent />

                </form>

                <div class="checkoutInfo">
                    <div class="checkoutInfo__title">Ваш заказ </div>
                    <div class="checkoutInfo__head"> <span>Товар</span><span>Цена</span></div>
                    <div class="checkoutInfo__wrap">
                        <style>
                            .checkoutInfo__wrap {
                                flex-wrap: wrap
                            }

                            .checkoutInfo__prod {
                                width: 100%;
                                display: flex;
                                align-items: flex-start;
                                justify-content: space-between;
                                gap: 20px;
                                margin-bottom: 5px;
                            }

                            .checkoutInfo__details {
                                display: grid;
                                gap: 3px;
                                margin-top: 7px;
                                color: #687480;
                                font-size: 13px;
                                line-height: 1.35;
                            }

                            .checkoutInfo__details span {
                                display: block;
                            }

                            @media (max-width: 600px) {
                                .checkoutInfo__prod {
                                    flex-direction: column;
                                    gap: 8px;
                                }
                            }
                        </style>




                        @if (isset($cart) && count($cart) > 0)
                            @foreach ($cart as $item)
                                @php
                                    // Ищем товар по ID из корзины
                                    $product = $products->firstWhere('id', (int) $item['productId']);
                                    $checkoutItemDetails = app(\App\Support\CartItemNormalizer::class)->details($item);
                                @endphp
                                <div class="checkoutInfo__prod">
                                    <div class="checkoutInfo__infoName">
                                        {{ $product?->h1 ?? ($item['productName'] ?? 'Товар') }}
                                        <div class="checkoutInfo__details">
                                            @foreach ($checkoutItemDetails as $label => $value)
                                                <span><strong>{{ $label }}:</strong> {{ $value }}</span>
                                            @endforeach
                                            <span><strong>Количество:</strong> {{ $item['quantity'] ?? 1 }}</span>
                                        </div>
                                    </div>
                                    <div class="checkoutInfo__info checkoutProdPrice">
                                        {{ $item['price'] }} р
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p>Ваша корзина пуста.</p>
                        @endif





                    </div>
                    <div class="checkoutInfo__wrap">
                        <div class="checkoutInfo__infoName">Цена без доставки</div>
                        <div class="checkoutInfo__info blue checkoutSubtotal">5000р</div>
                    </div>
                    <div class="checkoutInfo__wrap">
                        <div class="checkoutInfo__infoName">Доставка</div>
                        <div class="checkoutInfo__info">
                            <ul>
                                <li>
                                    <label>
                                        <span>Самовывоз из магазина</span>
                                        <span class="checkoutInfo__deliveryPrice">0 р</span>
                                        <input type="radio" name="delivery" value="0" checked>
                                    </label>
                                </li>

                                <li>
                                    <label>
                                        <span>Доставка в пределах МКАД</span>
                                        <span class="checkoutInfo__deliveryPrice">700 р</span>
                                        <input type="radio" name="delivery" value="700"
                                            @if (session('delivery_cost') == 700) checked @endif>
                                    </label>
                                </li>

                                <li>
                                    <label>
                                        <span>Доставка за МКАД (предварительный расчет)</span>
                                        <input type="radio" name="delivery" value="delivery"
                                            @if (session('delivery_cost') == 'delivery') checked @endif>
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="checkoutInfo__wrap" style="margin-bottom: 20px">
                        <div class="checkoutInfo__infoName">Общий </div>
                        <div class="checkoutInfo__info checkoutProdPriceSum"></div>
                    </div>
                    <button type="submit" form="orderForm" class="checkoutInfo__btn btn">Оформить заказ</button>
                </div>
            </div>
        </section>

    </main>


    <x-front.footer :headerInfo="$headerInfo" :curtainSubcats="$curtainSubcats" :blindSubcats="$blindSubcats"></x-front.footer>
    <x-front.popups></x-front.popups>

    @vite('resources/js/main.js')
    {{-- @vite('resources/js/swiper.js') --}}
    <script src="https://kit.fontawesome.com/9d3fa3c0db.js" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkoutProdPrice = document.querySelectorAll('.checkoutProdPrice');
            const checkoutSubtotal = document.querySelector('.checkoutSubtotal');
            const checkoutProdPriceSum = document.querySelector('.checkoutProdPriceSum');

            const deliveryInputs = document.querySelectorAll('input[name="delivery"]');

            function calculateTotal() {
                let totalPrice = 0;
                checkoutProdPrice.forEach(element => {
                    const price = parseFloat(element.innerText.replace(/[^\d.]/g,
                        '')); // Убираем буквы и пробелы, преобразуем в число
                    if (!isNaN(price)) {
                        totalPrice += price;
                    }
                });

                checkoutSubtotal.innerText = totalPrice + ' р';

                // Получаем выбранную доставку и преобразуем в число
                const selectedDelivery = parseFloat(document.querySelector('input[name="delivery"]:checked').value);

                // Проверяем, если доставка стоит 700
                if (selectedDelivery === 700) {
                    totalPrice += 700; // Добавляем стоимость доставки
                    checkoutProdPriceSum.innerText = totalPrice + ' р';
                } else {
                    checkoutProdPriceSum.innerText = totalPrice + ' р';
                }
            }

            // Инициализируем начальный расчет
            calculateTotal();

            // Добавляем обработчик для изменения выбора доставки
            deliveryInputs.forEach(input => {
                input.addEventListener('change', calculateTotal);
            });

        });
    </script>



    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Данные корзины, которые уже есть на сервере
            const cart = @json($cart);
            const products = @json($products);

            // Собираем данные о товарах
            const items = products.map(product => {
                return {
                    id: product.id,
                    name: product.h1,
                    quantity: cart[product.id],
                    price: product.price
                };
            });

            // Помещаем JSON строку в скрытое поле
            document.getElementById('itemsInput').value = JSON.stringify(items);
        });
    </script>

    <script>
        document.getElementById('orderForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // let total = document.querySelector('.checkoutProdPriceSum').innerText.replace(/\D/g, '');


            // document.querySelector('input[name="client_email"]').value = document.querySelector(
            //     'input[name="email"]').value;
            // document.querySelector('input[name="client_phone"]').value = document.querySelector(
            //     'input[name="phone"]').value;

            // const form = e.target;
            // const formData = new FormData(form);

            // try {
            //     const response = await fetch('{{ route('createOrder') }}', {
            //         method: 'POST',
            //         body: formData,
            //         headers: {
            //             'X-CSRF-TOKEN': '{{ csrf_token() }}'
            //         }
            //     });

            //     if (response.ok) {
            //         const data = await response.json();


            //         document.querySelector('input[name="orderid"]').value = data.order_id;
            //         document.querySelector('input[name="user_result_callback"]').value = data.redirect_url;

            //         form.submit(); 
            //     } else {
            //         console.error('Ошибка создания заказа:', await response.json());
            //     }
            // } catch (error) {
            //     console.error('Ошибка отправки:', error);
            // }

            const form = e.target;
            const formData = new FormData(form);

            try {
                const response = await fetch('{{ route('createOrder') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                if (response.ok) {
                    const data = await response.json();

                    if (data.success) {
                        alert("Ваша заказ принят в обработку, Мы свяжемся с Вами в ближайшее время.")
                        setTimeout(() => {
                            window.location.href = data.redirect_url; // просто редирект на профиль

                        }, 2500);
                    } else {
                        console.error('Ошибка:', data.message);
                    }
                } else {
                    console.error('Ошибка сервера:', await response.json());
                }
            } catch (error) {
                console.error('Ошибка отправки:', error);
            }
        });
    </script>
</body>

</html>







{{-- <h1>Ваша корзина</h1>

    {{ print_r($cart) }}

    <h1>Корзина</h1>

    @if (isset($cart) && count($cart) > 0)
        <ul>
            @foreach ($cart as $productId => $quantity)
                <li>Товар ID: {{ $productId }}, Количество: {{ $quantity }}</li>
            @endforeach
        </ul>
    @else
        <p>Ваша корзина пуста.</p>
    @endif --}}
