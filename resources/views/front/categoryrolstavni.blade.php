{{-- @include('front.head') --}}
<x-front.head title="{{ $category->title }}" description="{{ $category->description }}"></x-front.head>

<body class="p-index">

    <x-front.header :categoriesInCatalogMenu="$categoriesInCatalogMenu" :categoriesInHeaderMenu="$categoriesInHeaderMenu" :cart="$cart" :headerInfo="$headerInfo"></x-front.header>

    <main class="layout">


        <section class="s-catMain wrapper">
            <div class="s-catMain__img"><img src="{{ Storage::url($category->img) }}" alt="" /></div>
            <div class="breadcrumbs">
                <ul class="breadcrumbs__list">
                    <li class="breadcrumbs__item"><a class="breadcrumbs__link"
                            href="{{ route('front.home') }}">Главная</a></li>
                    <li class="breadcrumbs__item"><svg class="breadcrumbs__arrow" width="5" height="9"
                            viewBox="0 0 5 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#000"
                                d="M3.4575 4.5L2.97878 5.0625L5.28601e-05 8.2125L0.744734 9L5.00005 4.5L0.744733 3.72007e-07L5.2211e-05 0.7875L2.97878 3.9375L3.4575 4.5Z">
                            </path>
                        </svg><span class="breadcrumbs__active">{{ $category->titleh1 }}</span></li>
                </ul>
            </div>
            <h1 class="s-catMain__title title"> <span>{{ $category->titleh1 }}</span><svg width="114" height="35"
                    viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                        stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"> </path>
                </svg></h1>
            <div class="s-catMain__text">
                {{ $category->first_screen_text }}
            </div>
        </section>



        <x-front.section.subcats :category="$category" :subcats="$subcatsForSlider"></x-front.section.subcats>

       <x-subcat-sections
    :category="$category"
    :subcategoriesWithProducts="$subcategoriesWithProducts"
    :headerInfo="$headerInfo"/>

       <x-front.section.rollets-calculator />

       <x-front.section.rollets-installation :installationTypes="$installationTypes" />

       <x-front.section.rollets-systems />

       <x-front.section.rollets-faq :category="$category" />

        <x-front.section.delivery :title="$homePageFields->section_delivery_title" :topText="$homePageFields->section_delivery_top_text" :bottomText="$homePageFields->section_delivery_bottom_text"
            :iconCards="$iconCards"></x-front.section.delivery>


        <x-front.section.revs :reviews="$reviews"></x-front.section.revs>

 

        <x-front.section.seo :seoSection="$category->seo"></x-front.section.seo>



    </main>

    <input type="hidden" id="category-id" value="{{ $category->id }}">

    <x-front.footer :headerInfo="$headerInfo" :curtainSubcats="$curtainSubcats" :blindSubcats="$blindSubcats"></x-front.footer>
    <x-front.popups></x-front.popups>

    @vite('resources/js/main.js')
    @vite('resources/js/swiper.js')
    <script src="https://kit.fontawesome.com/9d3fa3c0db.js" crossorigin="anonymous"></script>


    <script></script>



    {{-- Табы --}}
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
                        const quantity = parseInt(counterInput.value) || 1;

                        let model = modelFromRequest || modelSelect.value;
                        let cloth = clothRequest || clothInput.value;
                        const control = controlInput.checked;
                        const discount = parseFloat(discountInput?.value) || 0;

                        if (!width || !height) {
                            rebuildPrice(0, quantity, discount); // Вызовет fallback
                            return;
                        }

                        // Индикатор загрузки
                        priceElement.textContent = 'Расчёт...';

                        fetch(
                                `/sheet-names?width=${width}&height=${height}&model=${model}&control=${control}&cloth=${cloth}`
                            )
                            .then(response => response.json())
                            .then(data => {
                                let basePrice = data.price;
                                if (typeof basePrice === 'string') {
                                    // Если backend вернул строку fallback
                                    priceElement.textContent = basePrice;
                                } else {
                                    basePrice = basePrice || 0;
                                    currentBasePrice = Number(basePrice) || 0;
                                priceElement.dataset.basePrice = currentBasePrice;
                                rebuildPrice(currentBasePrice, quantity, discount);
                                }
                            })
                            .catch(error => {
                                console.error('Ошибка при получении цены:', error);
                                priceElement.textContent = 'Цена по запросу'; // Fallback на ошибку
                            });
                    }

                    // Инициализация количества
                    counterInput.value = counterInput.value || 1;

                    // Инициализация UI
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


function fetchProducts(url) {
                let categoryId = document.querySelector('[data-category-id]').dataset.categoryId;
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
            function fetchFilteredProducts(page) {
                let selectedModels = Array.from(document.querySelectorAll(
                        '.modelLabel input[type="checkbox"]:checked'))
                    .map(el => el.id.replace('modelid', ''));
                let selectedColors = Array.from(document.querySelectorAll('input[name="color[]"]:checked'))
                    .map(el => el.value);
                let selectedMaterials = Array.from(document.querySelectorAll('input[name="material[]"]:checked'))
                    .map(el => el.value);

                const priceFilterPayload = getPriceFilterPayload();

                fetch('/filter-cat-products/{{ $category->id }}', {
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
                            let subcategoryPath = product.subcategory ? '/' + product.subcategory.slug :
                                '';
                            let productUrl = (product?.slug && product?.category?.slug && product
                                    ?.subcategory?.slug) ?
                                '/' + product.category.slug + '/' + product.subcategory.slug + '/' +
                                product.slug :
                                '#';

                            return `
            <div class="bigProdCard card" id="prod${product.id}" data-modelId="${product.modelid}" data-model="${product.model}" data-cloth="${product.cloth}" data-discount="${product.discount}">
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
                        <a class="bigProdCard__title" href="${productUrl}">${product.h1}</a>
                        ${buildCardMinDimensions(product)}
                        <div class="bigProdCard__priceWrap">
                            ${renderStaticCardPrice(product)}
                        </div>
                    </div>
                </div>
            </div>
        `;
                        }).join('');
                        loadPopupsContent()
                        // Обновляем пагинацию
                        document.querySelector('.pagination').innerHTML = data.pagination;
                    });
            }




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
                    const controlCheck = formWrapper.querySelector('.control').checked
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
                                // Здесь можно обновить количество товаров в корзине, если нужно
                                cardCounter.innerHTML = data.cart_count
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

    <script>
        // Инициализация слайдеров для новых секций с подкатегориями
        document.addEventListener('DOMContentLoaded', function() {
            const subcatSectionSwipers = document.querySelectorAll('.s-subcatSections__swiper');
            
            subcatSectionSwipers.forEach(function(swiperElement) {
                new Swiper(swiperElement, {
                    slidesPerView: 1,
                    spaceBetween: 20,
                    loop: false,
                    pagination: {
                        el: swiperElement.parentElement.querySelector('.swiper-pagination'),
                        clickable: true,
                    },
                    navigation: {
                        nextEl: swiperElement.parentElement.querySelector('.swiper-button-next'),
                        prevEl: swiperElement.parentElement.querySelector('.swiper-button-prev'),
                    },
                    breakpoints: {
                        640: {
                            slidesPerView: 2,
                            spaceBetween: 20,
                        },
                        768: {
                            slidesPerView: 3,
                            spaceBetween: 30,
                        },
                        1024: {
                            slidesPerView: 4,
                            spaceBetween: 30,
                        },
                    },
                });
            });
            
            // Инициализируем попапы для новых товаров
            if (typeof loadPopupsContent === 'function') {
                loadPopupsContent();
            }
        });
    </script>

</body>

</html>
