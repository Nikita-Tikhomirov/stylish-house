{{-- @include('front.head') --}}
<x-front.head title="{{ $subcategory->title }}" description="{{ $subcategory->description }}"></x-front.head>

<body class="p-index">

    <x-front.header :categoriesInCatalogMenu="$categoriesInCatalogMenu" :categoriesInHeaderMenu="$categoriesInHeaderMenu" :cart="$cart" :headerInfo="$headerInfo"></x-front.header>

    <main class="layout">

        <!-- Первый экран -->
        <section class="s-catMain wrapper">
            <div class="s-catMain__img"><img src="{{ Storage::url($subcategory->img) }}" alt="" /></div>
            <div class="breadcrumbs">
                <ul class="breadcrumbs__list">
                    <li class=""><a class="breadcrumbs__link" href="{{ route('front.home') }}">Главная</a></li>

                    <li class="breadcrumbs__item"><svg class="breadcrumbs__arrow" width="5" height="9"
                            viewBox="0 0 5 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#000"
                                d="M3.4575 4.5L2.97878 5.0625L5.28601e-05 8.2125L0.744734 9L5.00005 4.5L0.744733 3.72007e-07L5.2211e-05 0.7875L2.97878 3.9375L3.4575 4.5Z">
                            </path>
                        </svg>
                        <a href="/{{ $subcategory->category->slug }}"
                            class="breadcrumbs__link">{{ $subcategory->category->titleh1 }}</a>

                    </li>
                    <li class="breadcrumbs__item"><svg class="breadcrumbs__arrow" width="5" height="9"
                            viewBox="0 0 5 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#000"
                                d="M3.4575 4.5L2.97878 5.0625L5.28601e-05 8.2125L0.744734 9L5.00005 4.5L0.744733 3.72007e-07L5.2211e-05 0.7875L2.97878 3.9375L3.4575 4.5Z">
                            </path>
                        </svg><span class="breadcrumbs__active">{{ $subcategory->titleh1 }}</span></li>

                </ul>
            </div>
            <h1 class="s-catMain__title title"> <span>{{ $subcategory->titleh1 }}</span><svg width="114"
                    height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                        stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"> </path>
                </svg></h1>
            <div class="s-catMain__text">{{ $subcategory->first_screen_text }}</div>
        </section>

        <!-- Вывод товаров как везде -->

            <section class="popularsWithFilter wrapper">
                <h2 class="popularsWithFilter__title title"> <span>Популярные товары</span><svg width="114"
                        height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                            stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                    </svg></h2>
                <div class="popularsWithFilter__wrap">
                    <aside class="sidebarFilter">


                        <style>
                            .popularsWithFilter__wrap{
                                grid-template-columns: 1fr
                            }
                            .popularsWithFilter__cards{
                                grid-template-columns: repeat(4, 1fr);
                            }
                            .filterMaterials {
                                display: flex;
                                flex-direction: column;
                            }

                            .filterMaterials .sidebarFilter__paramsWrap {
                                display: flex;
                                flex-wrap: wrap;
                                gap: 5px;
                            }

                            .filterMaterials .materialLabel {
                                position: relative;
                                display: inline-block;
                                width: 40px;
                                height: 40px;
                                cursor: pointer;
                                border: 2px solid transparent;
                                border-radius: 5px;
                                transition: border-color 0.3s;
                            }

                            .filterMaterials .materialLabel input:checked+.materialOverlay+.materialImage {
                                border: 2px solid #007bff;
                            }

                            .filterMaterials .materialOverlay {
                                position: absolute;
                                top: 0;
                                left: 0;
                                width: fit-content;
                                height: 100%;
                                padding: 5px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                background: rgba(0, 0, 0, 0.7);
                                color: #fff;
                                opacity: 0;
                                font-size: 12px;
                                text-align: center;
                                border-radius: 5px;
                                pointer-events: none;
                                transition: opacity 0.3s;
                                white-space: nowrap;
                                z-index: 2;
                            }

                            .filterMaterials .materialLabel:hover .materialOverlay {
                                opacity: 1;
                            }

                            .filterMaterials .materialImage {
                                width: 100%;
                                height: 100%;
                                object-fit: cover;
                                border-radius: 5px;
                            }
                        </style>
                    </aside>
                    <div class="popularsWithFilter__cardsWrap">
                        <div class="popularsWithFilter__cards" id="productsWrap">
                            @include('front.partials.products')
                        </div>
                        <div class="pagination" id="pagination">
                            {{ $filterProduts->onEachSide(1)->links() }}
                        </div>
                    </div>
                </div>
            </section>

        <!-- Виды монтажа -->
        @if (!empty($installationTypes) && $installationTypes->isNotEmpty())
            <x-front.section.subcategory-installation-types :installationTypes="$installationTypes" />
        @else
            <x-front.section.rollets-installation />
        @endif

        <!-- Калькулятор -->
        @if (!empty($firstProduct))
            <x-front.section.rollets-product-calculator :product="$firstProduct" :sameModelProducts="$sameModelProducts" :category="$category" />
        @endif

        @if (!empty($workExamples) && $workExamples->isNotEmpty())
            <x-front.section.subgallery :gallerys="$workExamples" :category='$category' title=""></x-front.section.gallery>
        @endif

        <!-- Оплата и доставка -->
        <x-front.section.delivery :title="$homePageFields->section_delivery_title" :topText="$homePageFields->section_delivery_top_text" :bottomText="$homePageFields->section_delivery_bottom_text"
            :iconCards="$iconCards"></x-front.section.delivery>

        <!-- ВОПРОСЫ И ОТВЕТЫ -->
        @if ($subcategory->faq_html)
            <section class="s-faq wrapper">
                <div class="s-faq__container">
                    <div class="s-faq__title-wrap">
                        <h2 class="s-faq__title title"> <span>Вопросы и ответы</span><svg width="114" height="35"
                                viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                                    stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                            </svg></h2>
                    </div>
                    <div class="customAccardeonWrap">
                        {!! $subcategory->faq_html !!}
                    </div>
                </div>
            </section>

            <style>
                .customAccardeonWrap {
                   
                }
                 .customAccardeonWrap .faq-block .faq-item {
                    position: relative;
                    z-index: 2;
                    margin-bottom: 26px;
                    transition: .2s;
                    border: 1px solid #0989ff;
                    border-radius: 5px;
                    box-shadow: 5px 10px 6px #2c5b811a;
                    background: #fff;
                    padding: 8px 8px 8px 18px;

                }

                .customAccardeonWrap .faq-block .faq-item .faq-question {
                    position: relative;
                    z-index: 3;
                    padding-right: 10px;
                    list-style: none;
                    cursor: pointer;
                }

                .customAccardeonWrap .faq-block .faq-item .faq-answer {
                    padding-top: 10px;
                }

                .customAccardeonWrap .faq-block .faq-item .faq-arrow {
                    display: none;
                }

                .customAccardeonWrap .faq-block .faq-item:before {

                    content: "";
                    display: block;
                    width: 17px;
                    height: 2px;
                    position: absolute;
                    top: 21px;
                    right: 20px;
                    background: #0989ff;
                    z-index: -1;
                }

                .customAccardeonWrap .faq-block .faq-item:after {
                    content: "";
                    display: block;
                    width: 2px;
                    height: 17px;
                    position: absolute;
                    top: 14px;
                    right: 27px;
                    background: #0989ff;
                    -webkit-transition: .2s;
                    -o-transition: .2s;
                    transition: .2s;
                    z-index: -1;
                }

                .customAccardeonWrap .faq-block .faq-item .faq-question::marker {
                    display: none;
                }
            </style>
        @else
            <x-front.section.faqcat title="Вопросы и ответы" :faqs="$faqs"></x-front.section.faqcat>
        @endif

        <!-- СЕО ТЕКСТ -->
        <x-front.section.seo :seoSection="$subcategory->seo"></x-front.section.seo>

        <!-- Все категории -->
        <section class="s-tags wrapper">
            <h2 class="s-tags__title title"> <span>Все категории</span><svg width="114" height="35"
                    viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                        stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                </svg></h2>
            <div class="s-tags__tags">
                <div class="accardionJs">
                    <div class="accardion__title">Все категории</div>
                    <div class="accardion__content">
                        @if ($seoCats->isNotEmpty())
                            @foreach ($seoCats as $cat)
                                <a class="s-tags__tag"
                                    href="{{ route('subcategory.show', ['category_slug' => $cat->category->slug, 'subcategory_slug' => $cat->slug]) }}">
                                    {{ $cat->titleh1 ?? $cat->title }}
                                </a>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </section>

    </main>

    <x-front.footer :headerInfo="$headerInfo" :curtainSubcats="$curtainSubcats" :blindSubcats="$blindSubcats"></x-front.footer>

    <x-front.popups></x-front.popups>

    @vite('resources/js/main.js')
    @vite('resources/js/swiper.js')
    <script src="https://kit.fontawesome.com/9d3fa3c0db.js" crossorigin="anonymous"></script>

        <script>
        document.addEventListener('DOMContentLoaded', function() {

            const tabs = document.querySelectorAll('.s-populars__tabsNav li');
            const productsContainer = document.getElementById('products-container');
            const slides = document.querySelectorAll('.s-main__slide');

            const calcForm = document.querySelectorAll('.catCalculator .prodForm')
            // console.log(calcForm);

            document.querySelectorAll('.product-params-accordion .accordion-header').forEach((header) => {
                header.addEventListener('click', () => {
                    const item = header.closest('.accordion-item');
                    if (item) {
                        item.classList.toggle('active');
                    }
                });
            });
            function wrapPopupProd() {
                if (document.querySelector('.modal')) {} else {
                    let popup = document.getElementById('popupProd');
                    if (!popup) return;

                    popup.style.display = "block";
                    let modal = document.createElement('div');
                    modal.className = 'modal fadeIn';
                    modal.style.display = 'block';

                    let container = document.createElement('div');
                    container.className = 'modal__container';

                    let closeButton = document.createElement('button');
                    closeButton.className = 'modal__close';

                    closeButton.addEventListener('click', () => {
                        modal.classList.add('fadeOut');

                        setTimeout(() => {
                            popup.style.display = '';
                            document.body.appendChild(popup);
                            document.body.removeChild(modal);
                        }, 450);
                    });

                    container.appendChild(closeButton);
                    container.appendChild(popup);
                    modal.appendChild(container);
                    document.body.appendChild(modal);

                }; // Если уже есть модалка, выходим


            }



            function loadPopupsContent() {

                let allQuickButtons = document.querySelectorAll('.quickProd')



                allQuickButtons.forEach(element => {
                    element.addEventListener('click', () => {

                        const prodId = element.dataset.prod;

                        console.log('prodId:', prodId);
                        if (!prodId) return;
                        setTimeout(() => {
                            wrapPopupProd();
                        }, 50);

                        console.log('Запрос к /popup с prodId:', prodId);

                        // Получаем данные о товаре с сервера
                        fetch(`/popup/${prodId}`)
                            .then(response => response.json())
                            .then(product => {
                                // Заполняем попап данными товара
                                document.querySelector('#popupProd .prodForm__formSubtitle')
                                    .innerText = product.title;
                                document.querySelector('#popupProd .prodForm__formTitle')
                                    .innerText = `Заказать ${product.title}`;
                                document.querySelector('#popupProd .prodForm__description p')
                                    .innerText = product.first_screenn_description + ' ';
                                let prodImg = document.querySelectorAll(
                                    '#popupProd .prodForm__imgWrap img')

                                let img1src = `${product.image_path}`
                                let img2src = `${product.fabric_photo}`


                                // prodImg[1].src = img2src; 

                                if (img1src != 'null') {
                                    prodImg[0].src = img1src || '';
                                    prodImg[1].src = img2src || '';


                                } else {

                                    prodImg[1].style.display = 'none'
                                    prodImg[0].src = img2src;

                                }
                                // Корректируем путь


                                // console.log(product.gallery);
                                // Очищаем старую галерею
                                let gallery = document.querySelector(
                                    '#popupProd .prodForm__bar');
                                gallery.innerHTML = '';

                                // Добавляем изображения с ссылками
                                product.gallery.forEach(related => {
                                    let link = document.createElement('a');
                                    link.href = related.link; // Ссылка на товар
                                    let img = document.createElement('img');
                                    if (related.image) {

                                        img.src =
                                            `${related.image}`; // Путь к изображению
                                    } else {

                                        img.src =
                                            `${related.fabric_photo}`; // Путь к изображению
                                    }


                                    link.appendChild(
                                        img); // Вставляем изображение в ссылку
                                    gallery.appendChild(
                                        link); // Добавляем ссылку в галерею

                                });

                                console.log(product.model);

                                // Добавить id для кнопки добавить в корзину
                                document.querySelector('#popupProd .prodForm__addToCart')
                                    .setAttribute('data-id', prodId)

                                let controlLabel = document.querySelector(
                                    '#popupProd .sidebarFilter__label')
                                let controlInput = document.querySelector('#popupProd .control')
                                controlLabel.setAttribute('for', 'control' + prodId)
                                controlInput.setAttribute('id', 'control' + prodId)
                                controlInput.checked = false;

                                const prodWrap = document.querySelector('#popupProd');

                                let modelInput = prodWrap.querySelector('.model');
                                modelInput.value = product.model;
                                let clothInput = prodWrap.querySelector('.cloth');
                                clothInput.value = product.cloth;

                                let discountInput = prodWrap.querySelector('.discount');
                                discountInput.value = product.discount;

                                let widthInput = prodWrap.querySelector('.width-input');
                                let heightInput = prodWrap.querySelector('.height-input');


                                if (widthInput && product.min_width) {
                                    widthInput.value = product.min_width;
                                }

                                if (heightInput && product.min_height) {
                                    heightInput.value = product.min_height;
                                }



                                setTimeout(() => {
                                    getPrice([prodWrap], product.model, product.cloth,
                                        product.min_width, product.min_height,
                                        product.model_id)
                                }, 100);
                            })
                            .catch(error => {
                                console.error('Ошибка при загрузке данных товара:', error);
                            });

                    })
                });
            }


            function getPrice(arr, modelFromRequest, clothRequest, prodWidth, prodHeight, medelId) {
                arr.forEach(slide => {
                    const widthInput = slide.querySelector('.width-input');
                    const heightInput = slide.querySelector('.height-input');
                    const priceElement = slide.querySelector('.prodForm__price');
                    const modelSelect = slide.querySelector('.modelSelect');
                    const modelId = medelId;
                    const prodTitleTorequest = slide.querySelector('.prodForm__formTitle').innerText
                    console.log(prodTitleTorequest);

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
                        if (price <= 0 || isNaN(price)) {
                            priceElement.textContent = 'Цена по запросу';
                            return;
                        }
                        const discountedPrice = price - (price * discount / 100);
                        let priceNow = counterValue * discountedPrice;

                        const form = slide.closest('.prodForm');

                        const installationType = form.querySelector('input[name="installation-type"]:checked');
                        if (installationType) {
                            priceNow += (parseInt(installationType.dataset.price || 0, 10) * counterValue);
                        }

                        const controlType = form.querySelector('input[name="control-type"]:checked');
                        if (controlType) {
                            priceNow += (parseInt(controlType.dataset.price || 0, 10) * counterValue);
                        }

                        const lockDevice = form.querySelector('input[name="lock-device"]:checked');
                        if (lockDevice) {
                            priceNow += (parseInt(lockDevice.dataset.price || 0, 10) * counterValue);
                        }

                        const ralPaint = form.querySelector('input[name="ral-paint"]:checked');
                        if (ralPaint) {
                            priceNow += (parseInt(ralPaint.dataset.price || 0, 10) * counterValue);
                        }

                        const photoPrint = form.querySelector('input[name="photo-print"]:checked');
                        if (photoPrint) {
                            priceNow += (parseInt(photoPrint.dataset.price || 0, 10) * counterValue);
                        }

                        // Преобразуем цену в целое число
                        priceNow = Math.floor(priceNow);
                        priceElement.textContent = `Цена: ${priceNow}₽`;
                    }

                    // Функция для получения и обновления цены
                    function fetchPrice() {
                        const width = widthInput.value;
                        const height = heightInput.value;
                        // console.log(width);

                        const quantity = parseInt(counterInput.value) || 1;

                        let model = modelFromRequest || modelSelect.value;
                        let cloth = clothRequest || clothInput.value;
                        const control = controlInput.checked;
                        const discount = parseFloat(discountInput?.value) || 0;

                        if (!width || !height) {
                            rebuildPrice(0, quantity, discount); // Здесь вместо 0 будет "Цена по запросу"
                            return;
                        }

                        // Опционально: индикатор загрузки, чтобы не показывало старое значение
                        priceElement.textContent = 'Расчёт...';

                        fetch(
                                `/sheet-names?width=${width}&height=${height}&model=${model}&control=${control}&cloth=${cloth}&modelId=${modelId}&prodTitle=${prodTitleTorequest}`
                            )
                            .then(response => response.json())
                            .then(data => {
                                const basePrice = data.price || 0;
                                rebuildPrice(basePrice, quantity, discount);
                            })
                            .catch(error => {
                                console.error('Ошибка при получении цены:', error);
                                rebuildPrice(0, quantity,
                                discount); // Здесь тоже "Цена по запросу" в случае ошибки
                            });
                    }

                    // Инициализация количества
                    counterInput.value = counterInput.value || 1;

                    // Инициализация UI fallback (на случай, если fetch не сработает сразу)
                    priceElement.textContent = 'Цена по запросу';

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

                    const optionInputs = slide.querySelectorAll(
                        'input[name="installation-type"], input[name="control-type"], input[name="lock-device"], input[name="ral-paint"], input[name="photo-print"]'
                    );
                    optionInputs.forEach((input) => {
                        input.addEventListener('change', fetchPrice);
                    });
                });
            }

            getPrice(slides);
            getPrice(calcForm);




            loadPopupsContent()

            function rebuilCardsPrice(params) {
                let allCards = document.querySelectorAll('.card')

                allCards.forEach(element => {

                    // Минмальную и максимальную брать из модели

                    let prodTitle = element.querySelector('.bigProdCard__title').innerText.trim();

                    let width, height;

                    let counterForDouble = 1

                    if (prodTitle.includes("Стандарт")) {
                        width = 500;
                        height = 500;
                    } else if (prodTitle.includes("Спринг")) {
                        width = 700;
                        height = 500;
                    } else if (prodTitle.includes("Гранд")) {
                        width = 700;
                        height = 500;
                    } else if (prodTitle.includes("Кватро классик")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Кватро люкс")) {
                        width = 700;
                        height = 500;
                    } else if (prodTitle.includes("Классик премиум")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Дабл классик")) {
                        width = 400;
                        height = 500;
                        counterForDouble = 2
                    } else if (prodTitle.includes("Люкс премиум")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Дабл люкс")) {
                        width = 400;
                        height = 500;
                        counterForDouble = 2
                    } else if (prodTitle.includes("Мини")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Мини нью")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Уни-1")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Уни-2")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Уни-1 ламинация")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Уни-2 ламинация")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Комбо мини нью")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Комбо уни-1")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Комбо уни-2")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Комбо уни-2 ламинация")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Комбо в-52 стандарт")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Комбо Классик")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Комбо в-52 люкс")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Комбо дабл классик")) {
                        width = 400;
                        height = 500;
                        counterForDouble = 2
                    } else if (prodTitle.includes("Комбо дабл люкс")) {
                        width = 400;
                        height = 500;
                        counterForDouble = 2
                    } else if (prodTitle.includes("Комбо кватро классик")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Комбо кватро люкс")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Алюминиевые 50 мм")) {
                        width = 400;
                        height = 500;
                    } else if (prodTitle.includes("Компакт Премиум")) {
                        width = 300;
                        height = 600;
                    } else if (prodTitle.includes("ХL Абсолют")) {
                        width = 300;
                        height = 600;
                    } else {
                        width = 700;
                        height = 700; // Значение по умолчанию
                    }


                    let model = element.getAttribute('data-model');
                    let control = false;
                    let cloth = element.getAttribute('data-cloth');
                    let priceElement = element.querySelector('.discount');
                    let normalPriceElement = element.querySelector('.normalPrice');
                    let modelId = element.dataset.modelid;

                    // console.log(prodTitle);



                    fetch(
                            `/sheet-names?width=${width}&height=${height}&model=${model}&control=${control}&cloth=${cloth}&modelId=${modelId}&prodTitle=${prodTitle}`
                        )
                        .then(response => response.json())
                        .then(data => {
                            const basePrice = data.price * counterForDouble || "Цена по запросу ";
                            const discount = element.getAttribute('data-discount')
                            if (discount > 0) {
                                const discountedPrice = basePrice * (1 - discount / 100);
                                // Преобразуем цену в целое число без копеек
                                const priceNow = Math.floor(discountedPrice);
                                priceElement.innerText = `${priceNow}₽`;
                                normalPriceElement.innerText = `${basePrice}₽`;

                                normalPriceElement.style.textDecoration = "line-through";
                            } else {
                                priceElement.innerText = `${basePrice}₽`;
                                normalPriceElement.innerText = ""; // Очищаем старую цену
                            }
                        })
                        .catch(error => console.error('Ошибка при получении цены:', error));
                });

            }
            rebuilCardsPrice()

            // Пагинация


            function fetchProducts(url) {
                fetch(url, {
                        headers: {
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    })
                    .then(response => response.json()) // Получаем данные в формате JSON
                    .then(data => {
                        // Обновляем контент продуктов
                        document.getElementById("productsWrap").innerHTML = data.filterProduts;
                        // Обновляем пагинацию
                        document.getElementById("pagination").innerHTML = data.pagination;
                    })
                    .catch(error => console.error('Ошибка:', error)); // Обработка ошибок
            }

            document.body.addEventListener("click", function(e) {
                let pageLink = e.target.closest("#pagination a");
                if (pageLink) {
                    e.preventDefault(); // Отменяем стандартный переход
                    let pageUrl = new URL(pageLink.href); // Получаем URL из ссылки
                    let pageNumber = pageUrl.searchParams.get("page"); // Берем номер страницы
                    fetchFilteredProducts(pageNumber);
                    loadPopupsContent()
                }
            });

            document.querySelectorAll('.sidebarFilter__label input[type="checkbox"]').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    fetchFilteredProducts(1); // При изменении фильтра загружаем первую страницу
                    rebuilCardsPrice()
                    loadPopupsContent()
                });
            });

            function fetchFilteredProducts(page) {
                let selectedModels = Array.from(document.querySelectorAll(
                        '.modelLabel input[type="checkbox"]:checked'))
                    .map(el => el.id.replace('modelid', ''));
                let selectedColors = Array.from(document.querySelectorAll('input[name="color[]"]:checked'))
                    .map(el => el.value);
                let selectedMaterials = Array.from(document.querySelectorAll('input[name="material[]"]:checked'))
                    .map(el => el.value);

                fetch('/filter-subcat-products/{{ $subcategory->id }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            models: selectedModels,
                            colors: selectedColors,
                            materials: selectedMaterials,
                            page: page, // Передаем страницу в запрос
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        let productsContainer = document.querySelector('.popularsWithFilter__cards');
                        productsContainer.innerHTML = data.products.map(product => `
            <div class="bigProdCard card" id="prod${product.id}" data-modelId="${product.modelid}" data-model="${product.model}" data-cloth="${product.cloth}" data-discount="">
                <div class="bigProdCard__wrap">
                    <div class="bigProdCard__img-wrap">
                        <div class="bigProdCard__imgCustomWrap">
                            ${product.image_path ? `<img src="/${product.image_path}" alt="${product.h1}" />` : ''}
                            ${product.fabric_photo ? `<img src="${product.fabric_photo}" alt="${product.h1}" />` : ''}
                        </div>
                        <div class="bigProdCard__controls">
                            <div class="bigProdCard__cart control"><i class="fas fa-cart-arrow-down"></i>
                                <div class="bigProdCard__toolTip">В корзину</div>
                            </div>
                            <div class="bigProdCard__quckView control quickProd" data-modal="#popupProd" data-prod="${product.id}"><i class="fas fa-eye"></i>
                                <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                            </div>
                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                <div class="bigProdCard__toolTip">Добавить в избранное</div>
                            </div>
                        </div>
                    </div>
                    <div class="bigProdCard__info">
                        <a class="bigProdCard__category" href="${product.category ? '/' + product.category.slug : '#'}">${product.category ? product.category.titleh1 : 'Без категории'}</a>
                        <a class="bigProdCard__title" href="${product.slug ? '/' + product.category.slug + '/' + product.subcategory.slug + '/' + product.slug : '#'}">${product.h1}</a>
                        <div class="bigProdCard__priceWrap">
                            <span class="normalPrice" style="text-decoration: line-through;">${product.price}₽</span>
                            <span class="discount">${product.old_price}₽</span>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

                        // Обновляем пагинацию
                        document.querySelector('.pagination').innerHTML = data.pagination;
                        rebuilCardsPrice()
                        loadPopupsContent()
                    });
            }



            function initPricefilter() {
                const slider = document.querySelector('.custom-range-slider');
                const track = slider.querySelector('.track');
                const range = slider.querySelector('.range');
                const leftThumb = slider.querySelector('.left-thumb');
                const rightThumb = slider.querySelector('.right-thumb');
                const minPriceDisplay = document.getElementById('min-price-display');
                const maxPriceDisplay = document.getElementById('max-price-display');
                const minPriceInput = document.getElementById('min-price');
                const maxPriceInput = document.getElementById('max-price');
                const productCards = document.querySelectorAll('.card'); // Карточки товаров

                let min = 0,
                    max = 15000;
                let currentMin = min,
                    currentMax = max;

                // Функция обновления положения ползунков
                function updateThumbPosition(thumb, value) {
                    const percent = ((value - min) / (max - min)) * 100;
                    thumb.style.left = `${percent}%`;
                }

                // Функция обновления диапазона
                function updateRange() {
                    const minPercent = ((currentMin - min) / (max - min)) * 100;
                    const maxPercent = ((currentMax - min) / (max - min)) * 100;
                    range.style.left = `${minPercent}%`;
                    range.style.width = `${maxPercent - minPercent}%`;
                }

                // Функция фильтрации товаров
                function filterProducts() {
                    productCards.forEach(card => {
                        const discountSpan = card.querySelector('.discount');
                        const price = parseFloat(discountSpan?.textContent.replace('₽', '').trim()) || 0;

                        if (price >= currentMin && price <= currentMax) {
                            card.style.display = ''; // Показываем карточку
                        } else {
                            card.style.display = 'none'; // Скрываем карточку
                        }
                    });
                }

                // Функция перемещения ползунка
                function moveThumb(thumb, event) {
                    const rect = slider.getBoundingClientRect();
                    const offsetX = event.touches ? event.touches[0].clientX - rect.left : event.clientX - rect
                        .left;
                    const percent = Math.min(Math.max((offsetX / rect.width) * 100, 0), 100);
                    const value = Math.round(min + ((max - min) * percent) / 100);

                    if (thumb === leftThumb && value < currentMax) {
                        currentMin = value;
                        minPriceDisplay.textContent = value;
                        minPriceInput.value = value;
                    } else if (thumb === rightThumb && value > currentMin) {
                        currentMax = value;
                        maxPriceDisplay.textContent = value;
                        maxPriceInput.value = value;
                    }

                    updateThumbPosition(thumb, value);
                    updateRange();
                    filterProducts(); // Фильтруем товары сразу после перемещения
                }

                // Обработчики событий для перемещения ползунков
                [leftThumb, rightThumb].forEach((thumb) => {
                    thumb.addEventListener('mousedown', (e) => {
                        const moveHandler = (event) => moveThumb(thumb, event);
                        document.addEventListener('mousemove', moveHandler);
                        document.addEventListener('mouseup', () => {
                            document.removeEventListener('mousemove', moveHandler);
                        }, {
                            once: true
                        });
                    });

                    thumb.addEventListener('touchstart', (e) => {
                        const moveHandler = (event) => moveThumb(thumb, event);
                        document.addEventListener('touchmove', moveHandler);
                        document.addEventListener('touchend', () => {
                            document.removeEventListener('touchmove', moveHandler);
                        }, {
                            once: true
                        });
                    });
                });

                // Инициализация
                updateThumbPosition(leftThumb, currentMin);
                updateThumbPosition(rightThumb, currentMax);
                updateRange();
                filterProducts();
            }

            initPricefilter()

        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.prodForm__addToCart');

            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    const productId = button.getAttribute('data-id');
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')
                        .getAttribute('content');

                    const formWrapper = button.parentElement.parentElement
                    const widthToCalc = formWrapper.querySelector('.width-input').value
                    const heightToCalc = formWrapper.querySelector('.height-input').value
                    const controlElement = formWrapper.querySelector('.control');
                    const controlCheck = controlElement ? controlElement.checked : false;
                    const prodsCouunter = formWrapper.querySelector('.quantity-input').value
                    const prodPriceText = formWrapper.querySelector('.prodForm__price').innerText;

                    // const prodsCouunter = formWrapper.querySelector('.quantity-input').value
                    // const prodsCouunter = formWrapper.querySelector('.quantity-input').value


                    const prodPrice = parseInt(prodPriceText.replace(/\D/g, ''), 10);
                    const cardCounter = document.querySelector('.header__cartCounter')


                    fetch('/cart/add', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken // CSRF токен для защиты
                            },
                            body: JSON.stringify({
                                productId: productId,
                                width: widthToCalc,
                                height: heightToCalc,
                                control: controlCheck,
                                quantity: prodsCouunter,
                                price: prodPrice,
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert(data.message);
                                cardCounter.innerHTML = data.cart_count

                                // Здесь можно обновить количество товаров в корзине, если нужно

                            } else {
                                alert('Ошибка добавления товара в корзину');
                            }
                        })
                        .catch(error => {
                            console.error('Ошибка:', error);
                            alert('Произошла ошибка. Попробуйте еще раз.');
                        });
                });
            });
        });
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/fslightbox/3.4.2/index.min.js"></script>


</body>
</html>

