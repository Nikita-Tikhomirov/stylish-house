<x-front.head title="Корзина"></x-front.head>
@vite('resources/css/cart.css')

<body class="p-cart">
    <style>
        .cartPageProd__configuration {
            display: grid;
            gap: 3px;
            margin-top: 8px;
            color: #67727e;
            font-size: 13px;
            line-height: 1.35;
        }

        .cartPageProd__configuration span {
            display: block;
        }
    </style>

    <x-front.header :categoriesInCatalogMenu="$categoriesInCatalogMenu" :categoriesInHeaderMenu="$categoriesInHeaderMenu" :cart="$cart" :headerInfo="$headerInfo"></x-front.header>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <main class="layout">
        <section class="s-cart wrapper">
            <div class="s-cart__title title">Корзина </div>
            <div class="breadcrumbs">
                <ul class="breadcrumbs__list">
                    <li class="breadcrumbs__item"><a class="breadcrumbs__link" href="/">Главная</a></li>
                    <li class="breadcrumbs__item"><svg class="breadcrumbs__arrow" width="5" height="9"
                            viewBox="0 0 5 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#000"
                                d="M3.4575 4.5L2.97878 5.0625L5.28601e-05 8.2125L0.744734 9L5.00005 4.5L0.744733 3.72007e-07L5.2211e-05 0.7875L2.97878 3.9375L3.4575 4.5Z">
                            </path>
                        </svg><span class="breadcrumbs__active">Корзина</span></li>
                </ul>
            </div>
            <div class="s-cart__wrap">
                <div class="cartTable">
                    <div class="cartTable__tableHead">
                        <div class="cartTable__colTitle">Товар</div>
                        <div class="cartTable__colTitle">Цена </div>
                        <div class="cartTable__colTitle">Размер </div>
                        <div class="cartTable__colTitle">Управление</div>
                        <div class="cartTable__colTitle">Количество</div>
                        <div class="cartTable__colTitle">Редактировать</div>
                    </div>

                    @if (isset($cart) && count($cart) > 0)
                        @foreach ($cart as $key => $item)
                            <div class="cartPageProd" data-key="{{ $key }}">
                                <div class="cartPageProd__prodInfo">
                                    <div class="cartPageProd__img-wrap">

                                        @php
                                            $product = $products->firstWhere('id', $item['productId']);
                                        @endphp

                                        @if ($product->image_path)
                                            <img src="{{ asset($product->image_path) }}"
                                                alt="{{ $product->h1 ?? '' }}" />
                                        @else
                                            <img src="{{ asset($product->fabric_photo) }}"
                                                alt="{{ $product->h1 ?? '' }}" />
                                        @endif


                                    </div>
                                    <div class="cartPageProd__title">
                                        {{ $products->firstWhere('id', $item['productId'])?->h1 ?? ($item['productName'] ?? 'Товар') }}
                                        @php
                                            $cartItemDetails = app(\App\Support\CartItemNormalizer::class)->details($item);
                                            unset(
                                                $cartItemDetails['Ширина'],
                                                $cartItemDetails['Высота'],
                                                $cartItemDetails['Управление']
                                            );
                                        @endphp
                                        @if ($cartItemDetails)
                                            <div class="cartPageProd__configuration">
                                                @foreach ($cartItemDetails as $label => $value)
                                                    <span><strong>{{ $label }}:</strong> {{ $value }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>


                                </div>
                                <div class="cartPageProd__prodPrice">{{ $item['price'] }} р</div>
                                <div class="cartPageProd__widthWrap">
                                    <span class="width">{{ $item['width'] }}</span>
                                    <span>x</span>
                                    <span class="height">{{ $item['height'] }}</span>
                                </div>
                                <div class="control">
                                    {{ array_key_exists('control', $item) && $item['control'] !== null ? ($item['control'] ? 'Да' : 'Нет') : '—' }}
                                </div>
                                <div class="cartPageProd__counterWrap">
                                    {{-- <div class="cartPageProd__button minus">-</div>
                                    <input class="cartPageProd__input" type="text" value="" />
                                    <div class="cartPageProd__button plus">+</div> --}}
                                    <div class="cartPageProd__input">
                                        {{ $item['quantity'] }}

                                    </div>
                                </div>
                                <div class="cartPageProd__prodControls">

                                    <div class="cartPageProd__settings" data-key="{{ $key }}"
                                        data-modal="#popupProd"> <i class="fas fa-cog"></i></div>

                                    <div class="cartPageProd__remove" data-key="{{ $key }}">
                                        <i class="far fa-trash-alt"></i>
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    @else
                        <p>Ваша корзина пуста.</p>
                    @endif


                </div>

                <div class="cartForm">
                    <div class="cartForm__subtotal"> <span>Стоимость</span><span class="checkoutSubtotal">10000р</span>
                    </div>
                    <div class="cartForm__optionsList">
                        <div class="cartForm__listTitle">Доставка </div>
                        <ul>
                            <li>
                                <label>
                                    <input type="radio" name="delivery" value="0" checked>
                                    <span>Самовывоз</span>
                                    <span class="cartForm__deliveryPrice">0р</span>
                                </label>
                            </li>
                            <li>
                                <label>
                                    <input type="radio" name="delivery" value="700">
                                    <span>Доставка в пределах МКАД</span>
                                    <span class="cartForm__deliveryPrice">700р</span>
                                </label>
                            </li>
                            <li>
                                <label>
                                    <input type="radio" name="delivery" value="delivery">
                                    <span>Доставка за МКАД(предварительный расчет)</span>

                                </label>
                            </li>

                        </ul>
                    </div>
                    <div class="cartForm__total"> <span>Итого</span><span class="checkoutProdPriceSum">10500р</span>
                    </div>
                    <a href="/checkout" class="cartForm__button">Оформить заказ</a>
                </div>

            </div>
        </section>


    </main>


    <x-front.footer :headerInfo="$headerInfo" :curtainSubcats="$curtainSubcats" :blindSubcats="$blindSubcats"></x-front.footer>
    <x-front.popups></x-front.popups>

    @vite('resources/js/main.js')
    {{-- @vite('resources/js/swiper.js') --}}
    <script src="https://kit.fontawesome.com/9d3fa3c0db.js" crossorigin="anonymous"></script>

    {{-- Удалить товар --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteBtn = document.querySelectorAll('.cartPageProd__remove')
            console.log(deleteBtn);
            const cardCounter = document.querySelector('.header__cartCounter')

            deleteBtn.forEach(element => {
                element.addEventListener('click', function() {
                    let key = element.getAttribute('data-key')
                    // console.log(id);

                    fetch('/cart/remove', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify({
                                key
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Обновляем интерфейс, например, удаляем товар из DOM
                                document.querySelector(`.cartPageProd[data-key="${key}"]`)
                                    .remove();
                                cardCounter.innerHTML = data.cart_count
                            } else {
                                alert(data.message);
                            }
                        })
                })


            });
        })
    </script>

    {{-- загрузить в попап --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const checkoutProdPrice = document.querySelectorAll('.cartPageProd__prodPrice');
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

            const buttonsEdit = document.querySelectorAll('.cartPageProd__settings')

            function getPrice(arr, modelFromRequest, clothRequest, prodWidth, prodHeight) {
                arr.forEach(slide => {
                    const widthInput = slide.querySelector('.width-input');
                    const heightInput = slide.querySelector('.height-input');
                    const priceElement = slide.querySelector('.prodForm__price');
                    const modelSelect = slide.querySelector('.modelSelect');
                    const controlInput = slide.querySelector('.control') || {
                        checked: false
                    };
                    const clothInput = slide.querySelector('.cloth');
                    const discountInput = slide.querySelector('.discount');

                    let counterMinusBtn = slide.querySelector('.minus');
                    let counterPlusBtn = slide.querySelector('.plus');
                    let counterInput = slide.querySelector('.quantity-input');
                    // Удаляем старые обработчики перед добавлением новых
                    function removeEventListeners(element, events) {
                        const clone = element.cloneNode(true);
                        element.replaceWith(clone);
                        return clone;
                    }
                    // Очищаем обработчики перед добавлением
                    counterMinusBtn = removeEventListeners(counterMinusBtn, ['click']);
                    counterPlusBtn = removeEventListeners(counterPlusBtn, ['click']);
                    counterInput = removeEventListeners(counterInput, ['input']);
                    let priceNow = 0;

                    // Пересчет цены с учетом количества и скидки
                    function rebuildPrice(price, counterValue, discount = 0) {
                        const discountedPrice = price - (price * discount / 100);
                        let priceNow = counterValue * discountedPrice;
                        // Преобразуем цену в целое число
                        priceNow = Math.floor(priceNow);
                        priceElement.textContent = `Цена: ${priceNow}₽`;
                    }

                    // Функция для получения и обновления цены
                    function fetchPrice() {
                        const width = widthInput.value;
                        const height = heightInput.value;
                        const quantity = parseInt(counterInput.value) || 1;

                        let model = modelFromRequest || modelSelect.value;
                        let cloth = clothRequest || clothInput.value;
                        const control = controlInput.checked;
                        const discount = parseFloat(discountInput?.value) || 0;

                        if (!width || !height) return;

                        fetch(
                                `/sheet-names?width=${width}&height=${height}&model=${model}&control=${control}&cloth=${cloth}`
                            )
                            .then(response => response.json())
                            .then(data => {
                                const basePrice = data.price || 0;
                                rebuildPrice(basePrice, quantity, discount);
                            })
                            .catch(error => console.error('Ошибка при получении цены:', error));
                    }

                    // Инициализация количества
                    counterInput.value = counterInput.value || 1;

                    // Обработчики для изменения количества товаров
                    counterMinusBtn.addEventListener('click', () => {
                        let currentValue = parseInt(counterInput.value) || 1;
                        if (currentValue > 1) {
                            counterInput.value = currentValue - 1;
                            fetchPrice();
                        }
                    });

                    counterPlusBtn.addEventListener('click', () => {
                        let currentValue = parseInt(counterInput.value) || 1;
                        counterInput.value = currentValue + 1;
                        fetchPrice();
                    });

                    // Для ввода вручную
                    counterInput.addEventListener('input', () => {
                        let value = parseInt(counterInput.value);
                        if (isNaN(value) || value < 1) {
                            counterInput.value = 1;
                        }
                        fetchPrice();
                    });

                    // Изначальный расчет при загрузке
                    fetchPrice();

                    // Обновление цены при изменении ширины, высоты или других параметров
                    widthInput.addEventListener('input', fetchPrice);
                    heightInput.addEventListener('input', fetchPrice);
                    if (controlInput && controlInput instanceof Element) {
                        controlInput.addEventListener('input', fetchPrice);
                    }
                });
            }

            buttonsEdit.forEach(element => {
                element.addEventListener('click', function() {
                    let key = element.getAttribute('data-key')
                    fetch(`/cart/edit/${key}`)
                        .then(response => response.json())
                        .then(item => {
                            // setTimeout(() => {
                            const popup = document.querySelector('.prodPopup');
                            let popupTitle = popup.querySelector('.prodForm__formSubtitle')
                            popupTitle.innerText = item.title;
                            popup.querySelector('.prodForm__imgWrap img').src =
                                `/${item.image}`;
                            // console.log(item.description);
                            popup.querySelector('.prodForm__formTitle').innerText =
                                'Редактировать товар'

                            popup.querySelector('.prodForm__description p').innerText = item
                                .description;

                            // Устанавливаем значения параметров
                            popup.querySelector('.width-input').value = item.width;
                            popup.querySelector('.height-input').value = item.height;
                            popup.querySelector('.quantity-input').value = item.quantity;
                            const popupControl = popup.querySelector('.control');
                            if (popupControl) {
                                popupControl.checked = Boolean(item.control);
                            }

                            // Тип замера по ширине из корзины

                            let widthTypearr = popup.querySelectorAll('.widthType')
                            if (item.widthType === "Ширина по ткани") {
                                widthTypearr[0].checked = true
                                widthTypearr[1].checked = false

                            } else {
                                widthTypearr[1].checked = true
                                widthTypearr[0].checked = false
                            }

                            // Левое или правое управление

                            // Левое или правое управление
                            const sideSelect = popup.querySelector(
                                '.select-js.side'); // Стандартный селект
                            const customLabel = popup.querySelector(
                                '.custom-select__label'); // Кастомный текст
                            const customOptions = popup.querySelector(
                                '.custom-select__options'); // Кастомные опции

                            // Данные для генерации селекта
                            const sides = [{
                                    value: "Левое",
                                    label: "Левое управление"
                                },
                                {
                                    value: "Правое",
                                    label: "Правое управление"
                                }
                            ];

                            // Очищаем стандартный селект
                            sideSelect.innerHTML = '';

                            // Генерируем новые опции для стандартного селекта
                            sides.forEach(side => {
                                const option = document.createElement('option');
                                option.value = side.value;
                                option.textContent = side.label;

                                // Устанавливаем выбранное значение
                                if (side.label === item.side) {
                                    option.selected = true;
                                }

                                sideSelect.appendChild(option);
                            });

                            // Обновляем текст кастомного селекта
                            // customLabel.textContent = item.side;
                            const selectedSide = sides.find(side => side.value === item.side);
                            if (selectedSide) {
                                customLabel.textContent = selectedSide
                                    .label; // Правильно обновляем текст
                            }
                            // Очищаем кастомные опции
                            customOptions.innerHTML = '';

                            // Генерируем новые кастомные опции
                            sides.forEach(side => {
                                const customOption = document.createElement('div');
                                customOption.className = 'custom-select__option';
                                customOption.dataset.value = side.value;
                                customOption.textContent = side.label;

                                // Обновляем кастомный текст и стандартный селект при выборе
                                customOption.addEventListener('click', () => {
                                    customLabel.textContent = side.label;
                                    sideSelect.value = side.value;
                                });

                                customOptions.appendChild(customOption);
                            });



                            // popup.querySelector('.modelSelect').value = item.model;

                            // Привязываем текущий ключ к кнопке сохранения
                            let popupButton = popup.querySelector('.prodForm__addToCart')
                            popupButton.innerText = 'Сохранить'
                            popupButton.dataset.key = key;

                            let discountInput = popup.querySelector('.discount')
                            discountInput.value = item.discount
                            console.log(discountInput);

                            setTimeout(() => {
                                getPrice([popup], item.model, item.cloth)
                            }, 50);


                            const popupButtonEdit = document.querySelector(
                                '.prodForm__addToCart');

                            popupButtonEdit.addEventListener('click', () => {
                                const key = popupButton.dataset.key;
                                const width = popup.querySelector('.width-input')
                                    .value;
                                const height = popup.querySelector('.height-input')
                                    .value;
                                const control = popup.querySelector('.control')
                                    .checked;
                                const quantity = popup.querySelector(
                                    '.quantity-input').value;
                                const priceElement = popup.querySelector(
                                    '.prodForm__price');
                                const priceText = priceElement
                                    .textContent; // "Цена: 2570₽"
                                const price = parseInt(priceText.replace(/\D/g,
                                    '')); // 2570

                                const cardCounter = document.querySelector(
                                    '.header__cartCounter')

                                fetch('/cart/update', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector(
                                                    'meta[name="csrf-token"]')
                                                .content
                                        },
                                        body: JSON.stringify({
                                            key,
                                            width,
                                            height,
                                            control,
                                            quantity,
                                            price,
                                        })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            alert(data.message);
                                            cardCounter.innerHTML = data.cart_count
                                            let parentWrap = element.parentElement
                                                .parentElement

                                            console.log(parentWrap);
                                            parentWrap.querySelector('.width')
                                                .innerText = width
                                            parentWrap.querySelector('.height')
                                                .innerText = height
                                            if (control == true) {
                                                parentWrap.querySelector('.control')
                                                    .innerText = 'Да'

                                            } else {
                                                parentWrap.querySelector('.control')
                                                    .innerText = 'Нет'

                                            }
                                            parentWrap.querySelector(
                                                    '.cartPageProd__input')
                                                .innerText =
                                                quantity

                                            parentWrap.querySelector(
                                                    '.cartPageProd__prodPrice')
                                                .innerText = price + ' р'
                                            // let widthToCart
                                            calculateTotal();

                                        } else {
                                            alert(data.message);
                                        }
                                    })
                                    .catch(error => console.error(
                                        'Ошибка при обновлении товара:', error));
                            });


                            // Показать попап
                            // popup.classList.add('visible');
                        })
                        .catch(error => console.error('Ошибка при загрузке данных товара:', error));
                })
            });


        })
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deliveryOptions = document.querySelectorAll('input[name="delivery"]');

            deliveryOptions.forEach(option => {
                option.addEventListener('change', function() {
                    const deliveryCost = this.value;

                    // Отправляем AJAX-запрос на сервер
                    fetch('/cart/update-delivery', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({
                                deliveryCost: deliveryCost
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Стоимость доставки сохранена в корзине');
                                // Можно обновить итоговую сумму на странице
                                // updateTotalPrice(data.totalPrice);
                            } else {
                                console.error('Ошибка при сохранении стоимости доставки:', data
                                    .message);
                            }
                        })
                        .catch(error => {
                            console.error('Ошибка сети:', error);
                        });
                });
            });

            // function updateTotalPrice(totalPrice) {
            //     document.querySelector('.checkoutProdPriceSum').textContent = totalPrice + 'р';
            // }
        });
    </script>
</body>

</html>
