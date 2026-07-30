{{-- @include('front.head') --}}
<x-front.head title="{{ $subcategory->title }}" description="{{ $subcategory->description }}"></x-front.head>

<body class="p-index">

    <x-front.header :cart="$cart" :headerInfo="$headerInfo"></x-front.header>

    <main class="layout">


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
        @isset($firstProduct)


            <section class="popularsWithFilter wrapper">
                <h2 class="popularsWithFilter__title title"> <span>Популярные товары</span><svg width="114"
                        height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                            stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                    </svg></h2>
                <div class="popularsWithFilter__wrap">
                    <aside class="sidebarFilter">

                        @include('front.partials.price-filter')


                        <style>
                            .price-filter {
                                width: 100%;
                                max-width: 400px;
                                margin: 20px auto;
                                text-align: center;
                            }

                            .custom-range-slider {
                                position: relative;
                                width: 100%;
                                height: 40px;
                                margin: 20px 0;
                            }

                            .track {
                                position: absolute;
                                top: 50%;
                                left: 0;
                                width: 100%;
                                height: 6px;
                                background-color: #ddd;
                                border-radius: 3px;
                                transform: translateY(-50%);
                            }

                            .range {
                                position: absolute;
                                top: 50%;
                                height: 6px;
                                background-color: #007bff;
                                border-radius: 3px;
                                transform: translateY(-50%);
                                z-index: 1;
                            }

                            .thumb {
                                position: absolute;
                                top: 50%;
                                width: 20px;
                                height: 20px;
                                background-color: #007bff;
                                border: 2px solid #fff;
                                border-radius: 50%;
                                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                                transform: translate(-50%, -50%);
                                cursor: pointer;
                                z-index: 2;
                            }

                            .thumb.left-thumb {
                                left: 0;
                            }

                            .thumb.right-thumb {
                                right: 0;
                            }

                            .price-values {
                                font-size: 16px;
                                color: #333;
                            }
                        </style>

                        <div class="sidebarFilter__statusWrap">
                            <div class="sidebarFilter__title">Модели</div>

                            <div class="sidebarFilter__paramsWrap">

                                @foreach ($models as $model)
                                    <label class="sidebarFilter__label modelLabel">
                                        <input type="checkbox" id="modelid{{ $model->id }}" name="model_id_to_filter[]"
                                            value="{{ $model->id }}" @if (in_array($model->id, $selectedModels)) checked @endif />
                                        <span class="checkmark"><i class="fas fa-check"></i></span>
                                        <span class="sidebarFilter__labelText">{{ $model->h1 }}</span>
                                    </label>
                                @endforeach


                            </div>

                        </div>




                        {{-- <div class="sidebarFilter__statusWrap filterColors">
                            <div class="sidebarFilter__title">Цвет</div>

                            <div class="sidebarFilter__paramsWrap">


                                @foreach ($filterColors as $color)
                                    <label class="sidebarFilter__label colorLabel">
                                        <input type="checkbox" name="color[]" value="{{ $color }}"
                                            class="sidebarFilter__checkbox" />
                                        <span class="sidebarFilter__labelText colorLabel__text"
                                            style="background-color: {{ $color }};">
                                            <span class="colorArrow" style="color:{{ $color }} !important">✔</span>
                                        </span>
                                    </label>
                                @endforeach


                            </div>

                        </div> --}}
                        <div class="sidebarFilter__statusWrap filterMaterials">
                            <div class="sidebarFilter__title" style="margin-bottom: 0px;">Материал</div>
                            <div class="sidebarFilter__paramsWrap">
                                @foreach ($materials as $material)
                                    <label class="sidebarFilter__label materialLabel">
                                        <input type="checkbox" name="material[]" value="{{ $material['name'] }}"
                                            class="sidebarFilter__checkbox" />
                                        <span class="materialOverlay">{{ $material['name'] }}</span>
                                        @if ($material['image'])
                                            <img src="{{ asset('storage/' . $material['image']) }}"
                                                alt="{{ $material['name'] }}" class="materialImage" />
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <style>
                            .filterMaterials {
                                display: flex;
                                flex-direction: column;
                                /* gap: 10px; */
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
                                /* Фиксированные размеры */
                                height: 40px;
                                cursor: pointer;
                                border: 2px solid transparent;
                                /* Рамка для выделения */
                                border-radius: 5px;
                                transition: border-color 0.3s;
                            }

                            .filterMaterials .materialLabel input:checked+.materialOverlay+.materialImage {
                                border: 2px solid #007bff;
                                /* Цвет рамки для выбранного материала */
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
                                /* Картинки под размер контейнера */
                                height: 100%;
                                object-fit: cover;
                                border-radius: 5px;
                            }

                            .bigProdCard__meta {
                                margin-top: 6px;
                                font-size: 13px;
                                line-height: 1.35;
                                color: #666;
                            }
                        </style>


                    </aside>
                    <div class="popularsWithFilter__cardsWrap">
                        {{-- <div class="popularsWithFilter__topBar">
                        <div class="popularsWithFilter__controlsWrap">
                            <div class="popularsWithFilter__controls">
                                <div style="margin-left:10px" class="popularsWithFilter__counter">6 из 97 результатов
                                </div>
                            </div>
                        </div>
                        <div class="popularsWithFilter__sortPrice"> <select class="select-js" name="select">
                                <option value="1" selected="selected">По возрастанию</option>
                                <option value="2">Значение 2</option>
                                <option value="3">Значение 3</option>
                            </select></div>
                    </div> --}}



                        <div class="popularsWithFilter__cards" id="productsWrap">
                            @include('front.partials.products')
                        </div>

                        <div class="pagination" id="pagination">
                            {{ $filterProduts->onEachSide(1)->links() }}
                        </div>


                    </div>
                </div>
            </section>


            <section class="catCalculator wrapper">
                <h2 class="catCalculator__title title" style="margin-bottom:40px"> <span>Как заказать</span><svg
                        width="114" height="35" viewBox="0 0 114 35" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                            stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                    </svg></h2>
                <div class="prodForm">
                    <div class="prodForm__galleryWrapOuter">
                        <div class="prodForm__galleryWrap">
                            <div class="prodForm__imgWrap">

                                @if ($firstProduct)
                                    @php
                                        $firstMainImage = $firstProduct->image_thumb_path ?: $firstProduct->image_path;
                                        $firstFabricImage = $firstProduct->fabric_thumb_path ?: $firstProduct->fabric_photo;
                                    @endphp
                                    @if ($firstMainImage)
                                        <img src="{{ asset($firstMainImage) }}" alt="{{ $firstProduct->h1 }}" />
                                    @endif

                                    @if ($firstFabricImage)
                                        <img src="{{ asset($firstFabricImage) }}" alt="{{ $firstProduct->h1 }}" />
                                    @endif
                                @endif


                            </div>
                            <div class="prodForm__bar">

                                @foreach ($sameModelProducts as $product)
                                    @php
                                        $barMainImage = $product->image_thumb_path ?: $product->image_path;
                                        $barFabricImage = $product->fabric_thumb_path ?: $product->fabric_photo;
                                    @endphp
                                    @if ($barMainImage)
                                        <img src="{{ asset($barMainImage) }}" alt="{{ $product->h1 }}">
                                    @elseif ($barFabricImage)
                                        <img src="{{ asset($barFabricImage) }}" alt="{{ $product->h1 }}">
                                    @endif
                                @endforeach
                            </div>

                        </div>

                        {{-- <div class="sidebarFilter__statusWrap filterColors colorsForCalc">
                            <div class="sidebarFilter__labelText complectTitle">Цвет комплектации</div>

                            <div class="sidebarFilter__paramsWrap">

                                @isset($product)
                                    <div class="radio-buttons">
                                        <label class="radio-button active">
                                            <input class="controlColor" type="radio" name="choice{{ $product->id }}"
                                                value="#fff" checked />
                                            <img src="../img/fur.jpg" alt="Option 1">
                                        </label>
                                        <label class="radio-button">
                                            <input class="controlColor" type="radio" name="choice{{ $product->id }}"
                                                value="#000" />
                                            <img src="../img/fur.jpg" alt="Option 2">
                                        </label>
                                        <label class="radio-button">
                                            <input class="controlColor" type="radio" name="choice{{ $product->id }}"
                                                value="#eee" />
                                            <img src="../img/fur.jpg" alt="Option 3">
                                        </label>
                                    </div>
                                @endisset


                            </div>

                        </div> --}}

                    </div>


                    <div class="prodForm__calcFormWrap">
                        <div class="prodForm__formSubtitle">{{ $category->titleh1 }}</div>

                        <div class="prodForm__formTitle">{{ $firstProduct->h1 }}</div>
                        <x-front.read-more :text="$firstProduct->first_screenn_description"
                            :id="'subcategory-product-summary-'.$firstProduct->id" />
                        {{-- <select class="select-js modelSelect" name="model">
                        @foreach ($models as $model)
                            <option value="{{ $model->id }}">{{ $model->title }}</option>
                        @endforeach
                    </select> --}}


                        {{-- <select class="select-js modelSelect" name="model" id="model-select-{{ $firstProduct->id }}">
                        <option value="" disabled selected>Выберите модель</option>
                        @foreach ($models as $model)
                            <!-- Предполагаем, что $models содержит все модели -->
                            <option value="{{ $model->title }}" @if ($firstProduct->model_id == $model->id) selected @endif>
                                {{ $model->title }} <!-- Выводим название модели -->
                            </option>
                        @endforeach
                    </select> --}}

                        <input type="hidden" class="modelSelect" value="{{ $firstProduct->model_title }}">

                        <input type="hidden" name="cloth" class="cloth" value="{{ $firstProduct->cloth }}">
                        <input type="hidden" class="discount" value="{{ $firstProduct->discount }}">
                        {{-- <div class="prodForm__colorPickerWrap">
                        <div class="prodForm__colorPickerTitle">Выбирите цвет:</div>
                        <div class="prodForm__colorPicker">
                            <div class="prodForm__color red active"></div>
                            <div class="prodForm__color green"></div>
                            <div class="prodForm__color blue"></div>
                        </div>
                    </div> --}}

                        <div class="prodForm__sizeWrap">
                            <label class="prodForm__label">
                                <p>Ширина, мм</p>
                                @if ($firstProduct->min_width)
                                    <input class="prodForm__input width-input" type="number" name="width"
                                        value="{{ $firstProduct->min_width }}" required />
                                @else
                                    <input class="prodForm__input width-input" type="number" name="width"
                                        value="500" required />
                                @endif

                            </label>
                            <label class="prodForm__label">
                                <p>Высота, мм</p>
                                @if ($firstProduct->min_height)
                                    <input class="prodForm__input height-input" type="number" name="height"
                                        value="{{ $firstProduct->min_height }}" required />
                                @else
                                    <input class="prodForm__input height-input" type="number" name="height"
                                        value="500" required />
                                @endif

                            </label>
                        </div>
                        <div class="calcWidhType">
                            <div class="cartForm__optionsList">
                                <div class="cartForm__listTitle">Тип замера </div>
                                <ul>
                                    <li>
                                        <label>
                                            <input class="widthType" type="radio"
                                                name="widhType{{ $firstProduct->id }}" value="Ширина по ткани" checked>
                                            <span>Ширина по ткани</span>
                                        </label>
                                    </li>
                                    <li>
                                        <label>
                                            <input class="widthType" type="radio"
                                                name="widhType{{ $firstProduct->id }}" value="Ширина по габариту">
                                            <span>Ширина по габариту</span>

                                        </label>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <meta name="csrf-token" content="{{ csrf_token() }}">
                        <select class="select-js side" name="select">
                            {{-- <option value="Не выбрано" >Выберите управление</option> --}}
                            <option value="Левое" selected="selected">Левое управление</option>
                            <option value="Правое">Правое управление</option>
                        </select>

                        <div class="sidebarFilter" style="margin-bottom: 20px">
                            <div class="sidebarFilter__paramsWrap">

                                <label class="sidebarFilter__label" for="control{{ $firstProduct->id }}">
                                    <input class="control" type="checkbox" id="control{{ $firstProduct->id }}"
                                        name="control">
                                    <span class="checkmark"><i class="fas fa-check" aria-hidden="true"></i></span>
                                    <span class="sidebarFilter__labelText">Электропривод + пульт
                                        управления</span>
                                </label>


                            </div>
                        </div>

                        <div class="prodForm__howMany"> <button class="minus">-</button><input type="text"
                                class="quantity-input" placeholder="1" value="1" /><button
                                class="plus">+</button></div>
                        <div class="prodForm__priceAndAddToCart">
                            @php
                                $calcBasePrice = (int) ($firstProduct->min_price ?? 0);
                                $calcDiscount = (float) ($firstProduct->discount ?? 0);
                                $calcDisplayPrice = $calcBasePrice > 0 ? (int) floor($calcBasePrice * (1 - $calcDiscount / 100)) : null;
                            @endphp
                            <div class="prodForm__price" data-base-price="{{ $calcBasePrice }}">
                                {{ $calcDisplayPrice ? 'Цена: ' . number_format($calcDisplayPrice, 0, '', ' ') . '₽' : 'Цена по запросу' }}
                            </div><button data-id="{{ $firstProduct->id }}"
                                class="prodForm__addToCart"> Добавить в
                                корзину </button>
                        </div>
                    </div>


                </div>
            </section>
        @endisset
        <x-front.section.delivery :title="$homePageFields->section_delivery_title" :topText="$homePageFields->section_delivery_top_text" :bottomText="$homePageFields->section_delivery_bottom_text"
            :iconCards="$iconCards"></x-front.section.delivery>

        <x-front.section.revs :reviews="$reviews"></x-front.section.revs>

        @if (!empty($videoReviews) && $videoReviews->isNotEmpty())
            <x-front.section.subvideorevs :videoreviews="$videoReviews" :subcategory="$subcategory" title="" />
        @endif



        @if (!empty($workExamples) && $workExamples->isNotEmpty())
            <x-front.section.subgallery :gallerys="$workExamples" :category='$category' title=""></x-front.section.subgallery>
        @endif



        <x-front.section.seo :seoSection="$subcategory->seo"></x-front.section.seo>

        <x-front.section.faqcat title="Вопросы и ответы" :faqs="$faqs"></x-front.section.faqcat>



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

                                const popupPriceElement = prodWrap.querySelector('.prodForm__price');
                                const popupBasePrice = Number(product.min_price) || 0;
                                const popupDiscount = Number(product.discount) || 0;
                                if (popupPriceElement) {
                                    popupPriceElement.dataset.basePrice = popupBasePrice;
                                    if (popupBasePrice > 0) {
                                        const popupDisplayPrice = Math.floor(popupBasePrice * (1 - popupDiscount / 100));
                                        popupPriceElement.textContent = `Цена: ${popupDisplayPrice}₽`;
                                    } else {
                                        popupPriceElement.textContent = 'Цена по запросу';
                                    }
                                }

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
                    let currentBasePrice = parseFloat(priceElement.dataset.basePrice) || parseInt(priceElement.textContent.replace(/\D/g, ''), 10) || 0;

                    // Пересчет цены с учетом количества и скидки
                    function rebuildPrice(price, counterValue, discount = 0) {
                        if (price <= 0 || isNaN(price)) {
                            priceElement.textContent = 'Цена по запросу';
                            return;
                        }
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
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`Price request failed: ${response.status}`);
                                }

                                return response.json();
                            })
                            .then(data => {
                                const basePrice = Number(data.price);
                                if (!Number.isFinite(basePrice) || basePrice <= 0) {
                                    if (typeof data.price === 'string' && data.price.trim()) {
                                        priceElement.textContent = data.price;
                                    }
                                    return;
                                }

                                currentBasePrice = basePrice;
                                priceElement.dataset.basePrice = currentBasePrice;
                                rebuildPrice(currentBasePrice, quantity, discount);
                            })
                            .catch(error => {
                                console.error('Ошибка при получении цены:', error);
                                if (currentBasePrice > 0) {
                                    rebuildPrice(currentBasePrice, quantity, discount);
                                } else {
                                    priceElement.textContent = 'Цена по запросу';
                                }
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
                            rebuildPrice(currentBasePrice, parseInt(counterInput.value) || 1, parseFloat(discountInput?.value) || 0);
                        }
                    });

                    counterPlusBtn.addEventListener('click', () => {
                        let currentValue = parseInt(counterInput.value) || 1;
                        counterInput.value = currentValue + 1;
                        rebuildPrice(currentBasePrice, parseInt(counterInput.value) || 1, parseFloat(discountInput?.value) || 0);
                    });

                    // Для ввода вручную
                    counterInput.addEventListener('input', () => {
                        let value = parseInt(counterInput.value);
                        if (isNaN(value) || value < 1) {
                            counterInput.value = 1;
                        }
                        rebuildPrice(currentBasePrice, parseInt(counterInput.value) || 1, parseFloat(discountInput?.value) || 0);
                    });

                    // Изначальный расчет при загрузке
                    rebuildPrice(currentBasePrice, parseInt(counterInput.value) || 1, parseFloat(discountInput?.value) || 0);

                    // Обновление цены при изменении ширины, высоты или других параметров
                    widthInput.addEventListener('input', fetchPrice);
                    heightInput.addEventListener('input', fetchPrice);
                    if (controlInput && controlInput instanceof Element) {
                        controlInput.addEventListener('input', fetchPrice);
                    }
                });
            }

            getPrice(slides);
            getPrice(calcForm);




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

                const priceFilterPayload = getPriceFilterPayload();

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
                        price_filter_active: priceFilterPayload.active,
                        min_price: priceFilterPayload.min,
                        max_price: priceFilterPayload.max,
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        let productsContainer = document.querySelector('.popularsWithFilter__cards');
                        productsContainer.innerHTML = data.products.map(product => {
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

                            return `
            <div class="bigProdCard card" id="prod${product.id}" data-modelId="${product.modelid}" data-model="${product.model}" data-cloth="${product.cloth}" data-discount="" data-min-width="${product.min_width ?? ''}" data-min-height="${product.min_height ?? ''}">
                <div class="bigProdCard__wrap">
                    <div class="bigProdCard__img-wrap">
                        <div class="bigProdCard__imgCustomWrap">
                            ${mainImageSrc ? `<img src="${mainImageSrc}" alt="${product.h1}" />` : ''}
                            ${fabricImageSrc ? `<img src="${fabricImageSrc}" alt="${product.h1}" />` : ''}
                        </div>
                        <div class="bigProdCard__controls">
                            <div class="bigProdCard__cart control"><i class="fas fa-cart-arrow-down"></i>
                                <span class="bigProdCard__toolTip">В корзину</span>
                            </div>
                            <div class="bigProdCard__quckView control quickProd" data-modal="#popupProd" data-prod="${product.id}"><i class="fas fa-eye"></i>
                                <span class="bigProdCard__toolTip">Быстрый просмотр</span>
                            </div>
                            <button class="bigProdCard__favorites control" type="button" data-favorite-product="${product.id}" aria-label="Добавить в избранное"><i class="far fa-heart"></i>
                                <span class="bigProdCard__toolTip">Добавить в избранное</span>
                            </button>
                        </div>
                    </div>
                    <div class="bigProdCard__info">
                        <a class="bigProdCard__category" href="${product.category ? '/' + product.category.slug : '#'}">${product.category ? product.category.titleh1 : 'Без категории'}</a>
                        <a class="bigProdCard__title" href="${product.slug ? '/' + product.category.slug + '/' + product.subcategory.slug + '/' + product.slug : '#'}">${product.h1}</a>
                        ${buildCardMinDimensions(product)}
                        <div class="bigProdCard__priceWrap">
                            ${renderStaticCardPrice(product)}
                        </div>
                    </div>
                </div>
            </div>
        `;
                        }).join('');

                        // Обновляем пагинацию
                        document.querySelector('.pagination').innerHTML = data.pagination;
                        loadPopupsContent()
                    });
            }



            function getPriceFilterPayload() {
                const activeInput = document.getElementById('price-filter-active');
                const minInput = document.getElementById('min-price');
                const maxInput = document.getElementById('max-price');

                return {
                    active: activeInput ? activeInput.value === '1' : false,
                    min: minInput ? Number(minInput.value) || 0 : null,
                    max: maxInput ? Number(maxInput.value) || null : null,
                };
            }

            function initPricefilter() {
                const filter = document.querySelector('[data-price-filter]');
                if (!filter) {
                    return;
                }

                const slider = filter.querySelector('.custom-range-slider');
                if (!slider) {
                    return;
                }

                const range = slider.querySelector('.range');
                const leftThumb = slider.querySelector('.left-thumb');
                const rightThumb = slider.querySelector('.right-thumb');
                const minPriceDisplay = document.getElementById('min-price-display');
                const maxPriceDisplay = document.getElementById('max-price-display');
                const minPriceInput = document.getElementById('min-price');
                const maxPriceInput = document.getElementById('max-price');
                const activeInput = document.getElementById('price-filter-active');

                if (!range || !leftThumb || !rightThumb || !minPriceInput || !maxPriceInput) {
                    return;
                }

                const min = Number(filter.dataset.defaultMin) || 0;
                const max = Number(filter.dataset.defaultMax) || 0;
                if (max <= min) {
                    return;
                }

                let currentMin = Number(minPriceInput.value) || min;
                let currentMax = Number(maxPriceInput.value) || max;
                let requestTimer = null;

                function formatPrice(value) {
                    return Number(value).toLocaleString('ru-RU');
                }

                function updateThumbPosition(thumb, value) {
                    const percent = ((value - min) / (max - min)) * 100;
                    thumb.style.left = `${Math.min(Math.max(percent, 0), 100)}%`;
                }

                function updateRange() {
                    const minPercent = ((currentMin - min) / (max - min)) * 100;
                    const maxPercent = ((currentMax - min) / (max - min)) * 100;
                    range.style.left = `${Math.min(Math.max(minPercent, 0), 100)}%`;
                    range.style.width = `${Math.max(maxPercent - minPercent, 0)}%`;
                }

                function requestFilteredProducts() {
                    if (activeInput) {
                        activeInput.value = '1';
                    }

                    clearTimeout(requestTimer);
                    requestTimer = setTimeout(() => fetchFilteredProducts(1), 250);
                }

                function moveThumb(thumb, event) {
                    const rect = slider.getBoundingClientRect();
                    const pointer = event.touches ? event.touches[0] : event;
                    const offsetX = pointer.clientX - rect.left;
                    const percent = Math.min(Math.max((offsetX / rect.width) * 100, 0), 100);
                    const value = Math.round(min + ((max - min) * percent) / 100);

                    if (thumb === leftThumb && value < currentMax) {
                        currentMin = value;
                        minPriceInput.value = value;
                        if (minPriceDisplay) {
                            minPriceDisplay.textContent = formatPrice(value);
                        }
                    } else if (thumb === rightThumb && value > currentMin) {
                        currentMax = value;
                        maxPriceInput.value = value;
                        if (maxPriceDisplay) {
                            maxPriceDisplay.textContent = formatPrice(value);
                        }
                    }

                    updateThumbPosition(leftThumb, currentMin);
                    updateThumbPosition(rightThumb, currentMax);
                    updateRange();
                    requestFilteredProducts();
                }

                [leftThumb, rightThumb].forEach((thumb) => {
                    thumb.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        const moveHandler = (event) => moveThumb(thumb, event);
                        document.addEventListener('mousemove', moveHandler);
                        document.addEventListener('mouseup', () => {
                            document.removeEventListener('mousemove', moveHandler);
                        }, { once: true });
                    });

                    thumb.addEventListener('touchstart', (e) => {
                        const moveHandler = (event) => moveThumb(thumb, event);
                        document.addEventListener('touchmove', moveHandler);
                        document.addEventListener('touchend', () => {
                            document.removeEventListener('touchmove', moveHandler);
                        }, { once: true });
                    });
                });

                updateThumbPosition(leftThumb, currentMin);
                updateThumbPosition(rightThumb, currentMax);
                updateRange();
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
                    const controlCheck = formWrapper.querySelector('.control')?.checked ?? null
                    const prodsCouunter = formWrapper.querySelector('.quantity-input').value
                    const prodPriceText = formWrapper.querySelector('.prodForm__price').innerText;

                    // const prodsCouunter = formWrapper.querySelector('.quantity-input').value
                    // const prodsCouunter = formWrapper.querySelector('.quantity-input').value


                    const prodPrice = parseInt(prodPriceText.replace(/\D/g, ''), 10) || 0;
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
                                ...(window.Shop?.collectCartOptions(formWrapper, button) || {}),
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


<script></script>
