{{-- @include('front.head') --}}
<x-front.head title="{{ $homePageFields->meta_title }}"
    description="{{ $homePageFields->meta_description }}"></x-front.head>

<body class="p-index">

    <x-front.header :categoriesInCatalogMenu="$categoriesInCatalogMenu" :categoriesInHeaderMenu="$categoriesInHeaderMenu" :cart="$cart" :headerInfo="$headerInfo"></x-front.header>

    <main class="layout">
        <x-front.section.hero :mainSlider="$mainSlider"></x-front.section.hero>

        {{-- <section class="s-main">
            <div class="s-main__shape"> <img src="img/slider-shape-1.png" alt="" /></div>
            <div class="s-main__shape"> <img src="img/slider-shape-4.png" alt="" /></div>
            <div class="s-main__swiper swiper">
                <div class="swiper-wrapper">
                    @foreach ($sliders as $slide)
                    <div class="s-main__slide swiper-slide wrapper">
                        <div class="s-main__info">
                            <div class="s-main__subtitle"> <span>{{ $slide->subtitle }} </span>

                            </div>
                            <div class="s-main__title">{{ $slide->title }}</div>
                            <div class="s-main__discount"> <span>{{ $slide->description_start }}
                                </span><span>{{ $slide->description_colored }} <svg width="94" height="20"
                                        viewBox="0 0 94 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M74.8576 4.63367L78.6048 5.11367C80.9097 5.35155 82.8309 5.75148 84.4483 5.97993L86.6581 6.31091L88.4262 6.63948C89.4684 6.81761 90.2699 6.9312 90.8805 6.99186C93.3213 7.24888 92.7011 6.63674 92.8183 6.12534C92.9355 5.61394 93.7175 5.37081 91.3267 4.45886C90.73 4.24001 89.9345 3.97481 88.8826 3.65818L87.1034 3.12577L84.8643 2.63282C83.236 2.28025 81.2402 1.82307 78.8684 1.52138L75.0177 0.981633C73.6188 0.823014 72.1417 0.730003 70.5389 0.582533C63.0297 0.0282543 55.4847 0.193022 48.0068 1.07459C39.9065 2.04304 31.9328 3.87384 24.2213 6.53586C18.0824 8.61764 12.1674 11.3089 6.56479 14.5692C4.88189 15.5255 3.25403 16.5756 1.68892 17.7145C0.568976 18.5077 -0.00964231 18.9932 0.0547097 19.0858C0.388606 19.6584 10.6194 13.1924 25.151 8.99361C32.789 6.72748 40.6283 5.20536 48.5593 4.44848C55.8569 3.76455 63.1992 3.69678 70.5082 4.24591L74.8223 4.62335"
                                            fill="currentColor"></path>
                                    </svg></span><span>{{ $slide->description_end }}</span></div>
                            <a class="s-main__button"
                                href="{{ route('product.show', ['category_slug' => $slide->product->category->slug, 'subcategory_slug' => $slide->product->subcategory->slug, 'product_slug' => $slide->product->slug]) }}/">
                                <span>Подробнее</span><i class="fas fa-arrow-right"></i></a>
                        </div>
                        <div class="prodForm calculator">
                            <div class="prodForm__galleryWrapOuter">
                                <div class="prodForm__galleryWrap">


                                    <div class="prodForm__imgWrap">
                                        <img src="{{ Storage::url($slide->product->image_path) }}" alt="" />
                                    </div>
                                    <div class="prodForm__bar">
                                        @foreach ($slide->product->relatedProducts ?? [] as $relatedProduct)
                                        <a style="margin-bottom: 5px;display: block;"
                                            href="{{ route('product.show', ['category_slug' => $relatedProduct->category->slug, 'subcategory_slug' => $relatedProduct->subcategory->slug, 'product_slug' => $relatedProduct->slug]) }}/">
                                            <img src="{{ Storage::url($relatedProduct->image_path) }}"
                                                alt="{{ $relatedProduct->title }}" />
                                        </a>
                                        @endforeach
                                    </div>

                                </div>
                                <div class="sidebarFilter__statusWrap filterColors colorsForCalc">
                                    <div class="sidebarFilter__labelText complectTitle">Цвет комплектации</div>

                                    <div class="sidebarFilter__paramsWrap">


                                        <div class="radio-buttons">
                                            <label class="radio-button active">
                                                <input class="controlColor" type="radio" name="choice{{$slide->id}}"
                                                    value="#fff" checked />
                                                <img src="img/fur.jpg" alt="Option 1">
                                            </label>
                                            <label class="radio-button">
                                                <input class="controlColor" type="radio" name="choice{{$slide->id}}"
                                                    value="#000" />
                                                <img src="img/fur.jpg" alt="Option 2">
                                            </label>
                                            <label class="radio-button">
                                                <input class="controlColor" type="radio" name="choice{{$slide->id}}"
                                                    value="#eee" />
                                                <img src="img/fur.jpg" alt="Option 3">
                                            </label>
                                        </div>




                                    </div>

                                </div>
                            </div>


                            <div class="prodForm__calcFormWrap">


                                <input type="hidden" name="cloth" class="cloth" value="{{ $slide->product->cloth }}">



                                <div class="prodForm__formSubtitle">
                                    {{ $slide->product->category->title ?? 'Без категории' }}</div>
                                <div class="prodForm__formTitle">{{ $slide->product->h1 }}</div>

                                <input type="hidden" class="modelSelect" value="{{ $slide->product->model_title }}">

                                <input type="hidden" class="discount" value="{{ $slide->product->discount }}">

                                <div class="prodForm__sizeWrap">
                                    <label class="prodForm__label">
                                        <p>Ширина, мм</p>
                                        <input class="prodForm__input width-input" type="number" name="width"
                                            value="500" required />
                                    </label>
                                    <label class="prodForm__label">
                                        <p>Высота, мм</p>
                                        <input class="prodForm__input height-input" type="number" name="height"
                                            value="500" required />
                                    </label>
                                </div>
                                <div class="calcWidhType">
                                    <div class="cartForm__optionsList">
                                        <div class="cartForm__listTitle">Тип замера </div>
                                        <ul>
                                            <li>
                                                <label>
                                                    <input class="widthType" type="radio"
                                                        name="widhType{{ $slide->product->id }}" value="Ширина по ткани"
                                                        checked>
                                                    <span>Ширина по ткани</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label>
                                                    <input class="widthType" type="radio"
                                                        name="widhType{{ $slide->product->id }}"
                                                        value="Ширина по габариту">
                                                    <span>Ширина по габариту</span>

                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <select class="select-js side" name="select">
                                    <option value="Левое" selected="selected">Левое управление</option>
                                    <option value="Правое">Правое управление</option>
                                </select>

                                @if ($slide->product->control)
                                <div class="sidebarFilter" style="margin-bottom: 20px">
                                    <div class="sidebarFilter__paramsWrap">

                                        <label class="sidebarFilter__label" for="control{{ $loop->index }}">
                                            <input class="control" type="checkbox" id="control{{ $loop->index }}"
                                                name="control">
                                            <span class="checkmark"><i class="fas fa-check"
                                                    aria-hidden="true"></i></span>
                                            <span class="sidebarFilter__labelText">Электропривод + пульт
                                                управления</span>
                                        </label>


                                    </div>
                                </div>
                                @endif

                                <div class="prodForm__howMany">
                                    <button class="minus">-</button>
                                    <input type="number" class="quantity-input" value="1" min="1" />
                                    <button class="plus">+</button>
                                </div>



                                <div class="prodForm__priceAndAddToCart">
                                    <div class="prodForm__price" data-coef="{{ $slide->product->coef }}">Цена:
                                        1200₽</div>
                                    <button class="prodForm__addToCart" id="{{ $slide->product->id }}">Добавить в
                                        корзину</button>
                                </div>



                            </div>
                        </div>
                    </div>
                    @endforeach





                </div>
                <div class="s-main__swiper-pagination swiper-pagination"></div>
                <div class="s-main__swiper-button-prev swiper-button-prev"></div>
                <div class="s-main__swiper-button-next swiper-button-next"></div>
            </div>
        </section> --}}





        <x-front.section.actions :homeActions="$homeActions"></x-front.section.actions>

        <x-front.section.populars :homePopulars="$homePopulars" :categories="$categories"></x-front.section.populars>

        {{-- @foreach ($homePopulars as $categoryProducts)
        @if ($categoryProducts->isNotEmpty())
        <h2>{{ $categoryProducts->first()->category->titleh1 }}</h2>
        @foreach ($categoryProducts as $product)
        <h1>{{ $product->h1 }}</h1>
        @endforeach
        @endif
        @endforeach --}}



        <x-front.section.how :title="$homePageFields->section_request_title" :subtitle="$homePageFields->section_request_subtitle" :text="$homePageFields->section_request_text"></x-front.section.how>

        <x-front.section.delivery :title="$homePageFields->section_delivery_title" :topText="$homePageFields->section_delivery_top_text" :bottomText="$homePageFields->section_delivery_bottom_text"
            :iconCards="$iconCards"></x-front.section.delivery>

        <x-front.section.faq :title="$homePageFields->faq_title" :faqs="$faqs"></x-front.section.faq>

        <x-front.section.revs :reviews="$reviews"></x-front.section.revs>

        <x-front.section.seo :seoSection="$seoSection->content"></x-front.section.seo>

        <x-front.section.map></x-front.section.map>

    </main>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .sidebarFilter__label {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            -webkit-box-pack: start;
            -ms-flex-pack: start;
            justify-content: flex-start;
            color: #55585b;
            font-family: Jost, Tahoma, sans-serif
        }

        .sidebarFilter__label:not(:last-child) {
            margin-bottom: 10px
        }

        .sidebarFilter__label input {
            opacity: 0;
            height: 0;
            width: 0
        }

        .sidebarFilter .checkmark {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            -webkit-box-pack: center;
            -ms-flex-pack: center;
            justify-content: center;
            height: 23px;
            width: 22px;
            background-color: #fff;
            border: 2px solid #e7e7e7;
            margin-right: 10px;
            cursor: pointer
        }

        .sidebarFilter .checkmark i {
            opacity: 0;
            color: #fff
        }

        .sidebarFilter__label input:checked~.checkmark {
            background-color: #0989ff;
            border: 2px solid #0989ff
        }

        .sidebarFilter__label input:checked~.checkmark i {
            opacity: 1
        }

        .sidebarFilter .checkmark:after {
            content: "";
            position: absolute;
            display: none
        }

        .sidebarFilter__label .checkmark:after {
            left: 8px;
            top: 6px;
            width: 6px;
            height: 11px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            -webkit-transform: rotate(45deg);
            -ms-transform: rotate(45deg);
            transform: rotate(45deg)
        }
    </style>


    {{-- <x-front.footer :headerInfo="$headerInfo" ></x-front.footer> --}}
    <x-front.footer :headerInfo="$headerInfo" :curtainSubcats="$curtainSubcats" :blindSubcats="$blindSubcats"></x-front.footer>

    <x-front.popups></x-front.popups>

    @vite('resources/js/main.js')
    @vite('resources/js/swiper.js')
    <script src="https://kit.fontawesome.com/9d3fa3c0db.js" crossorigin="anonymous"></script>


    {{-- Табы --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const tabs = document.querySelectorAll('.s-populars__tabsNav li');
            const productsContainer = document.getElementById('products-container');
            const slides = document.querySelectorAll('.s-main__slide');

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
                                `/sheet-names?width=${width}&height=${height}&model=${model}&control=${control}&cloth=${cloth}&modelId=${modelId}&prodTitle=${prodTitleTorequest}`
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


            getPrice(slides);



            function loadPopupsContent() {
                let allQuickButtons = document.querySelectorAll('.quickProd')


                allQuickButtons.forEach(element => {
                    element.addEventListener('click', () => {
                        let prodId = element.getAttribute('data-prod')
                        console.log(prodId);
                        setTimeout(() => {
                            wrapPopupProd();
                        }, 50);
                        // Получаем данные о товаре с сервера
                        fetch(`/popup/${prodId}`)
                            .then(response => response.json())
                            .then(product => {
                                // Заполняем попап данными товара
                                document.querySelector('#popupProd .prodForm__formSubtitle')
                                    .innerText = product.title;
                                // document.querySelector('#popupProd .prodForm__formTitle').innerText = `Заказать ${product.title}`;
                                document.querySelector('#popupProd .prodForm__description p')
                                    .innerText = product.first_screenn_description + ' ';
                                let prodImg = document.querySelectorAll(
                                    '#popupProd .prodForm__imgWrap img')

                                let img1src = `${product.image_path}`
                                let img2src = `${product.fabric_photo}`

                                if (img1src != 'null') {
                                    prodImg[0].src = img1src || '';
                                    prodImg[1].src = img2src || '';
                                } else {

                                    prodImg[1].style.display = 'none'
                                    prodImg[0].src = img2src;

                                }

                                // prodImg.src =
                                //     `/${product.image_path}`; 
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
                                    .setAttribute('id', prodId)

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


                                if (heightInput && product.min_height) {
                                    heightInput.value = product.min_height;
                                }


                                setTimeout(() => {
                                    getPrice([prodWrap], product.model, product.cloth,
                                        product.min_width, product.min_height,
                                        product.model_id)
                                }, 50);
                            })
                            .catch(error => {
                                console.error('Ошибка при загрузке данных товара:', error);
                            });

                    })
                });
            }

            loadPopupsContent()


            function renderStaticCardPrice(product) {
                const minPrice = Number(product.min_price) || 0;
                const discount = Number(product.discount) || 0;

                if (minPrice <= 0) {
                    return '<span class="discount">Цена по запросу</span>';
                }

                if (discount > 0) {
                    const discountedPrice = Math.floor(minPrice * (1 - discount / 100));

                    return `
                            <span class="normalPrice" style="text-decoration: line-through;">${minPrice}₽</span>
                            <span class="discount">${discountedPrice}₽</span>
                    `;
                }

                return `<span class="discount">${minPrice}₽</span>`;
            }

            function buildCardMinDimensions(product) {
                const minWidth = parseInt(product.min_width, 10) || 0;
                const minHeight = parseInt(product.min_height, 10) || 0;

                if (!minWidth && !minHeight) {
                    return '';
                }

                const widthText = minWidth ? `${minWidth} мм` : '';
                const heightText = minHeight ? `${minHeight} мм` : '';
                const separator = widthText && heightText ? ' x ' : '';

                return `<div class="bigProdCard__meta">От ${widthText}${separator}${heightText}</div>`;
            }


tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Убираем активный класс у всех вкладок
                    tabs.forEach(t => t.classList.remove('active'));
                    // Добавляем активный класс к выбранной вкладке
                    this.classList.add('active');

                    const categoryId = this.getAttribute('data-catid');

                    // Очищаем контейнер товаров
                    productsContainer.innerHTML = '';

                    // Загружаем товары для выбранной категории через AJAX
                    fetch(`/products/${categoryId}`)
                        .then(response => response.json())
                        .then(products => {

                            // Для каждого товара создаем HTML и добавляем в контейнер
                            products.forEach(product => {
                                let categorySlug = product.category ? product.category
                                    .slug : '';
                                let subcategorySlug = product.subcategory ? product
                                    .subcategory
                                    .slug : '';
                                let productSlug = product.slug ? product.slug : '';
                                let mainImage = product.image_thumb_path || product.image_path;
                                let fabricImage = product.fabric_thumb_path || product.fabric_photo;
                                let normalizeImagePath = (path) => {
                                    if (!path) {
                                        return '';
                                    }
                                    return (path.startsWith('http://') || path.startsWith('https://') || path
                                        .startsWith('/')) ? path : `/${path}`;
                                };
                                let mainImageSrc = normalizeImagePath(mainImage);
                                let fabricImageSrc = normalizeImagePath(fabricImage);
                                const productHTML = `
                                        <div class="s-populars__slide swiper-slide card" id="prod${product.id}" data-modelid="${product.model_id}"  data-model="${product.model_title}" data-cloth="${product.cloth}"  data-discount="">
                                            <div class="bigProdCard">
                                                <div class="bigProdCard__wrap">
                                                    <div class="bigProdCard__img-wrap">
               <div class="bigProdCard__imgCustomWrap">
                            ${mainImageSrc ? `<img src="${mainImageSrc}" alt="${product.h1}" />` : ''}
                            ${fabricImageSrc ? `<img src="${fabricImageSrc}" alt="${product.h1}" />` : ''}
                        </div>
                                                        <div class="bigProdCard__controls">
                                                            <div class="bigProdCard__controls">
                                                    <div class="bigProdCard__cart control"><i class="fas fa-cart-arrow-down" aria-hidden="true"></i>
                                                        <div class="bigProdCard__toolTip">В корзину</div>
                                                    </div>
                                                    <div class="bigProdCard__quckView control quickProd" data-prod="${product.id}" data-modal="#popupProd"><i class="fas fa-eye" aria-hidden="true"></i>
                                                        <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                                    </div>
                                                    <div class="bigProdCard__favorites control"><i class="far fa-heart" aria-hidden="true"></i>
                                                        <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                                    </div>
                                                </div>
                                                        </div>
                                                    </div>
                                                    <div class="bigProdCard__info">
                                                        <a class="bigProdCard__category" href="${categorySlug ? '/' + categorySlug : '#'}">${product.category ? product.category.titleh1 : 'Без категории'}</a>
                                                        <a class="bigProdCard__title" href="${productSlug ? '/' + categorySlug + '/' + subcategorySlug + '/' + productSlug : '#'}">${product.h1}</a>
                        ${buildCardMinDimensions(product)}
                                                        <div class="bigProdCard__priceWrap">
                                                                             ${renderStaticCardPrice(product)}
                                                            </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>`;
                                productsContainer.insertAdjacentHTML('beforeend',
                                    productHTML);
                            });
                            setTimeout(() => {
                                loadPopupsContent()
                            }, 50);

                        });
                });
            });
        });

    </script>


    <script></script>

    {{-- Попапы --}}

    {{-- Добавить в корзину --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.prodForm__addToCart');

            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();

                    const productId = this.id;
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')
                        .getAttribute('content');

                    const formWrapper = button.parentElement.parentElement
                    const widthToCalc = formWrapper.querySelector('.width-input').value
                    const heightToCalc = formWrapper.querySelector('.height-input').value
                    const controlCheck = formWrapper.querySelector('.control').checked
                    const prodsCouunter = formWrapper.querySelector('.quantity-input').value
                    const prodPriceText = formWrapper.querySelector('.prodForm__price').innerText;

                    let side = formWrapper.querySelector('.side').value;
                    console.log(side);

                    let widthType = formWrapper.querySelector('.widthType:checked')
                    widthType = widthType.value
                    let controlColor = formWrapper.parentElement
                    controlColor = controlColor.querySelector('.controlColor:checked')
                    // controlColor = controlColor.value
                    console.log(side, widthType, controlColor);


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
                                side: side,
                                widthType: widthType,
                                controlColor: controlColor,
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

    {{--
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const hero = new Swiper('.hero__slider', {
                // Optional parameters
                loop: true,

                // If we need pagination
                pagination: {
                    el: '.hero__slider .swiper-pagination',
                },

                // Navigation arrows
                navigation: {
                    nextEl: '.hero__slider .swiper-button-next',
                    prevEl: '.hero__slider .swiper-button-prev',
                },
                // And if we need scrollbar

            });
        })
    </script> --}}

</body>

</html>
