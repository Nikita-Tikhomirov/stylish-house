{{-- @include('front.head') --}}
<x-front.head title="Shop home"></x-front.head>

<body class="p-index">

    <header class="header">
        <ul class="header__topMenu wrapper">
            <li> <a href="#">Услуги </a></li>
            <li> <a href="#">Рассчитать </a></li>
            <li> <a href="#">Портфолио </a></li>
            <li> <a href="#">Оплата и Доставка </a></li>
            <li> <a href="#">Контакты</a></li>
        </ul>
        <div class="header__bottomMenu wrapper"><a class="header__logo" href="/"><img src="{{ asset('img/logo.svg') }}" alt="Logo"></a>
            <div class="header__infoWrap">
                <div class="header__infoWrapItem">
                    <div class="header__infoItemIcon"> <i class="fas fa-map-signs"></i></div><span>Адрес:
                        магазина</span>
                </div>
                <div class="header__infoWrapItem">
                    <div class="header__infoItemIcon"> <i class="far fa-clock"></i></div><span>Пн-Вс: 09-00 -
                        20-00</span>
                </div>
            </div>
            <div class="header__infoWrap">
                <div class="header__infoWrapItem">
                    <div class="header__infoItemIcon"> <i class="fas fa-phone"></i></div><a
                        href="tel:+79201134877">+79201134877 </a>
                </div>
                <div class="header__infoWrapItem">
                    <div class="header__infoItemIcon"> <i class="fab fa-whatsapp"></i></div><a
                        href="tel:+79201134877">+79201134877 </a>
                </div>
            </div>
            <div class="header__infoWrap infoWrapButtons">
                <div class="header__infoWrapItem">
                    <div class="header__btn btn" data-modal="#measure">Заказать Замер</div>
                </div>
                <div class="header__infoWrapItem">
                    <div class="header__btn btn" data-modal="#call">Заказать Звонок</div>
                </div>
            </div>
            <div class="header__wrapTomobile"><a class="header__cartIconWrap" href="/cart.html"><span
                        class="header__cartCounter">0 </span><svg width="21" height="22" viewBox="0 0 21 22"
                        fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M6.48626 20.5H14.8341C17.9004 20.5 20.2528 19.3924 19.5847 14.9348L18.8066 8.89359C18.3947 6.66934 16.976 5.81808 15.7311 5.81808H5.55262C4.28946 5.81808 2.95308 6.73341 2.4771 8.89359L1.69907 14.9348C1.13157 18.889 3.4199 20.5 6.48626 20.5Z"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        </path>
                        <path
                            d="M6.34902 5.5984C6.34902 3.21232 8.28331 1.27803 10.6694 1.27803V1.27803C11.8184 1.27316 12.922 1.72619 13.7362 2.53695C14.5504 3.3477 15.0081 4.44939 15.0081 5.5984V5.5984"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        </path>
                        <path d="M7.70365 10.1018H7.74942" stroke="currentColor" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M13.5343 10.1018H13.5801" stroke="currentColor" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg></a>
                <div class="header__mobileBurger"> <svg data-v-3b9ee29c="data-v-3b9ee29c" width="18" height="14"
                        viewBox="0 0 18 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M0 1C0 0.447715 0.447715 0 1 0H15C15.5523 0 16 0.447715 16 1C16 1.55228 15.5523 2 15 2H1C0.447715 2 0 1.55228 0 1ZM0 7C0 6.44772 0.447715 6 1 6H17C17.5523 6 18 6.44772 18 7C18 7.55228 17.5523 8 17 8H1C0.447715 8 0 7.55228 0 7ZM1 12C0.447715 12 0 12.4477 0 13C0 13.5523 0.447715 14 1 14H11C11.5523 14 12 13.5523 12 13C12 12.4477 11.5523 12 11 12H1Z"
                            fill="currentColor"></path>
                    </svg></div>
            </div>
        </div>
        <div class="header__mainMenu wrapper mobileMenu">
            <div class="header__catalogWrap">
                <div class="header__catalogOpenBtn"> <svg data-v-3b9ee29c="data-v-3b9ee29c" width="18"
                        height="14" viewBox="0 0 18 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M0 1C0 0.447715 0.447715 0 1 0H15C15.5523 0 16 0.447715 16 1C16 1.55228 15.5523 2 15 2H1C0.447715 2 0 1.55228 0 1ZM0 7C0 6.44772 0.447715 6 1 6H17C17.5523 6 18 6.44772 18 7C18 7.55228 17.5523 8 17 8H1C0.447715 8 0 7.55228 0 7ZM1 12C0.447715 12 0 12.4477 0 13C0 13.5523 0.447715 14 1 14H11C11.5523 14 12 13.5523 12 13C12 12.4477 11.5523 12 11 12H1Z"
                            fill="currentColor"></path>
                    </svg><span>Кталог</span></div>
                <div class="hidenCatalog">
                    <nav class="main-nav">
                        <ul class="categories">
                            <li class="category">
                                <ul class="all-categories">
                                    <li class="category">
                                        <div class="hidenCatalog__cont">
                                            <div class="hidenCatalog__wrap">
                                                <div class="hidenCatalog__infoWrap">
                                                    <div class="hidenCatalog__img-wrap"><img src="img/prod.jpg"
                                                            alt="" /></div><a href="#">Категория 1</a>
                                                </div>
                                                <div class="hidenCatalog__toggle"> <i
                                                        class="fas fa-chevron-right"></i></div>
                                            </div>
                                        </div>
                                        <ul class="subcategories">
                                            <li class="subcategory">
                                                <div class="hidenCatalog__cont">
                                                    <div class="hidenCatalog__wrap">
                                                        <div class="hidenCatalog__infoWrap">
                                                            <div class="hidenCatalog__img-wrap"><img
                                                                    src="img/prod.jpg" alt="" /></div><a
                                                                href="#">Подкатегория 1</a>
                                                        </div>
                                                        <div class="hidenCatalog__toggle"> <i
                                                                class="fas fa-chevron-right"></i></div>
                                                    </div>
                                                </div>
                                                <ul class="products">
                                                    <li class="product"><a href="#">Headphones1</a></li>
                                                    <li class="product"><a href="#">Mobile Tablets1</a></li>
                                                </ul>
                                            </li>
                                            <li class="subcategory">
                                                <div class="hidenCatalog__cont">
                                                    <div class="hidenCatalog__wrap">
                                                        <div class="hidenCatalog__infoWrap">
                                                            <div class="hidenCatalog__img-wrap"><img
                                                                    src="img/prod.jpg" alt="" /></div><a
                                                                href="#">Подкатегория 2</a>
                                                        </div>
                                                        <div class="hidenCatalog__toggle"> <i
                                                                class="fas fa-chevron-right"></i></div>
                                                    </div>
                                                </div>
                                                <ul class="products">
                                                    <li class="product"><a href="#">Headphones2</a></li>
                                                    <li class="product"><a href="#">Mobile Tablets2</a></li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="category">
                                        <div class="hidenCatalog__cont">
                                            <div class="hidenCatalog__wrap">
                                                <div class="hidenCatalog__infoWrap">
                                                    <div class="hidenCatalog__img-wrap"><img src="img/prod.jpg"
                                                            alt="" /></div><a href="#">Категория 1</a>
                                                </div>
                                                <div class="hidenCatalog__toggle"> <i
                                                        class="fas fa-chevron-right"></i></div>
                                            </div>
                                        </div>
                                        <ul class="subcategories">
                                            <li class="subcategory">
                                                <div class="hidenCatalog__cont">
                                                    <div class="hidenCatalog__wrap">
                                                        <div class="hidenCatalog__infoWrap">
                                                            <div class="hidenCatalog__img-wrap"><img
                                                                    src="img/prod.jpg" alt="" /></div><a
                                                                href="#">Подкатегория 1</a>
                                                        </div>
                                                        <div class="hidenCatalog__toggle"> <i
                                                                class="fas fa-chevron-right"></i></div>
                                                    </div>
                                                </div>
                                                <ul class="products">
                                                    <li class="product"><a href="#">Headphones1</a></li>
                                                    <li class="product"><a href="#">Mobile Tablets1</a></li>
                                                </ul>
                                            </li>
                                            <li class="subcategory">
                                                <div class="hidenCatalog__cont">
                                                    <div class="hidenCatalog__wrap">
                                                        <div class="hidenCatalog__infoWrap">
                                                            <div class="hidenCatalog__img-wrap"><img
                                                                    src="img/prod.jpg" alt="" /></div><a
                                                                href="#">Подкатегория 2</a>
                                                        </div>
                                                        <div class="hidenCatalog__toggle"> <i
                                                                class="fas fa-chevron-right"></i></div>
                                                    </div>
                                                </div>
                                                <ul class="products">
                                                    <li class="product"><a href="#">Headphones2</a></li>
                                                    <li class="product"><a href="#">Mobile Tablets2</a></li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="category">
                                        <div class="hidenCatalog__cont">
                                            <div class="hidenCatalog__wrap">
                                                <div class="hidenCatalog__infoWrap">
                                                    <div class="hidenCatalog__img-wrap"><img src="img/prod.jpg"
                                                            alt="" /></div><a href="#">Категория 1</a>
                                                </div>
                                                <div class="hidenCatalog__toggle"> <i
                                                        class="fas fa-chevron-right"></i></div>
                                            </div>
                                        </div>
                                        <ul class="subcategories">
                                            <li class="subcategory">
                                                <div class="hidenCatalog__cont">
                                                    <div class="hidenCatalog__wrap">
                                                        <div class="hidenCatalog__infoWrap">
                                                            <div class="hidenCatalog__img-wrap"><img
                                                                    src="img/prod.jpg" alt="" /></div><a
                                                                href="#">Подкатегория 1</a>
                                                        </div>
                                                        <div class="hidenCatalog__toggle"> <i
                                                                class="fas fa-chevron-right"></i></div>
                                                    </div>
                                                </div>
                                                <ul class="products">
                                                    <li class="product"><a href="#">Headphones1</a></li>
                                                    <li class="product"><a href="#">Mobile Tablets1</a></li>
                                                </ul>
                                            </li>
                                            <li class="subcategory">
                                                <div class="hidenCatalog__cont">
                                                    <div class="hidenCatalog__wrap">
                                                        <div class="hidenCatalog__infoWrap">
                                                            <div class="hidenCatalog__img-wrap"><img
                                                                    src="img/prod.jpg" alt="" /></div><a
                                                                href="#">Подкатегория 2</a>
                                                        </div>
                                                        <div class="hidenCatalog__toggle"> <i
                                                                class="fas fa-chevron-right"></i></div>
                                                    </div>
                                                </div>
                                                <ul class="products">
                                                    <li class="product"><a href="#">Headphones2</a></li>
                                                    <li class="product"><a href="#">Mobile Tablets2</a></li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            <ul class="header__mainMenuList">
                <li>
                    <nav class="menu">
                        <ul class="menu__list">
                            <li class="menu__item"><a class="menu__link" href="#">Шторы</a><i
                                    class="fas fa-chevron-down"></i>
                                <ul class="menu__sub-menu">
                                    <li class="menu__item"><a class="menu__link" href="#">Подкатегория</a><i
                                            class="fas fa-chevron-down"></i>
                                        <ul class="menu__sub-menu">
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="menu__item"><a class="menu__link" href="#">Подкатегория</a><i
                                            class="fas fa-chevron-down"></i>
                                        <ul class="menu__sub-menu">
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu__item"><a class="menu__link" href="#">Карнизы</a><i
                                    class="fas fa-chevron-down"></i>
                                <ul class="menu__sub-menu">
                                    <li class="menu__item"><a class="menu__link" href="#">Подкатегория</a><i
                                            class="fas fa-chevron-down"></i>
                                        <ul class="menu__sub-menu">
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="menu__item"><a class="menu__link" href="#">Подкатегория</a><i
                                            class="fas fa-chevron-down"></i>
                                        <ul class="menu__sub-menu">
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu__item"><a class="menu__link" href="#">Жалюзи</a><i
                                    class="fas fa-chevron-down"></i>
                                <ul class="menu__sub-menu">
                                    <li class="menu__item"><a class="menu__link" href="#">Подкатегория</a><i
                                            class="fas fa-chevron-down"></i>
                                        <ul class="menu__sub-menu">
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="menu__item"><a class="menu__link" href="#">Подкатегория</a><i
                                            class="fas fa-chevron-down"></i>
                                        <ul class="menu__sub-menu">
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu__item"><a class="menu__link" href="#">Рольставни</a><i
                                    class="fas fa-chevron-down"></i>
                                <ul class="menu__sub-menu">
                                    <li class="menu__item"><a class="menu__link" href="#">Подкатегория</a><i
                                            class="fas fa-chevron-down"></i>
                                        <ul class="menu__sub-menu">
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="menu__item"><a class="menu__link" href="#">Подкатегория</a><i
                                            class="fas fa-chevron-down"></i>
                                        <ul class="menu__sub-menu">
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu__item"><a class="menu__link" href="#">Секционные Ворота</a><i
                                    class="fas fa-chevron-down"></i>
                                <ul class="menu__sub-menu">
                                    <li class="menu__item"><a class="menu__link" href="#">Подкатегория</a><i
                                            class="fas fa-chevron-down"></i>
                                        <ul class="menu__sub-menu">
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                        </ul>
                                    </li>
                                    <li class="menu__item"><a class="menu__link" href="#">Подкатегория</a><i
                                            class="fas fa-chevron-down"></i>
                                        <ul class="menu__sub-menu">
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                            <li class="menu__item"><a class="menu__link" href="#">Товар</a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </nav>
                </li>
            </ul>
        </div>
    </header>
    <main class="layout">
        <section class="s-main">
            <div class="s-main__shape"> <img src="img/slider-shape-1.png" alt="" /></div>
            <div class="s-main__shape"> <img src="img/slider-shape-4.png" alt="" /></div>
            <div class="s-main__swiper swiper">
                <div class="swiper-wrapper">
                    <div class="s-main__slide swiper-slide wrapper">
                        <div class="s-main__info">
                            <div class="s-main__subtitle"> <span>Starting at </span><span>274₽</span></div>
                            <div class="s-main__title">The best tablet Collection 2023</div>
                            <div class="s-main__discount"> <span>Exclusive offer</span><span> -35% <svg width="94"
                                        height="20" viewBox="0 0 94 20" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M74.8576 4.63367L78.6048 5.11367C80.9097 5.35155 82.8309 5.75148 84.4483 5.97993L86.6581 6.31091L88.4262 6.63948C89.4684 6.81761 90.2699 6.9312 90.8805 6.99186C93.3213 7.24888 92.7011 6.63674 92.8183 6.12534C92.9355 5.61394 93.7175 5.37081 91.3267 4.45886C90.73 4.24001 89.9345 3.97481 88.8826 3.65818L87.1034 3.12577L84.8643 2.63282C83.236 2.28025 81.2402 1.82307 78.8684 1.52138L75.0177 0.981633C73.6188 0.823014 72.1417 0.730003 70.5389 0.582533C63.0297 0.0282543 55.4847 0.193022 48.0068 1.07459C39.9065 2.04304 31.9328 3.87384 24.2213 6.53586C18.0824 8.61764 12.1674 11.3089 6.56479 14.5692C4.88189 15.5255 3.25403 16.5756 1.68892 17.7145C0.568976 18.5077 -0.00964231 18.9932 0.0547097 19.0858C0.388606 19.6584 10.6194 13.1924 25.151 8.99361C32.789 6.72748 40.6283 5.20536 48.5593 4.44848C55.8569 3.76455 63.1992 3.69678 70.5082 4.24591L74.8223 4.62335"
                                            fill="currentColor"></path>
                                    </svg></span><span>off this week</span></div><a class="s-main__button"
                                href="#"> <span>Подробнее</span><i class="fas fa-arrow-right"></i></a>
                        </div>
                        <div class="prodForm calculator">
                            <div class="prodForm__galleryWrap">
                                <div class="prodForm__bar"> <img class="active" src="img/prod.jpg"
                                        alt="" /><img src="img/prod.jpg" alt="" /><img
                                        src="img/prod.jpg" alt="" /></div>
                                <div class="prodForm__imgWrap"> <img src="img/prod.jpg" alt="" /></div>
                            </div>
                            <div class="prodForm__calcFormWrap">
                                <div class="prodForm__formSubtitle">Подкатегория</div>
                                <div class="prodForm__formTitle">Товар</div><select class="select-js" name="select">
                                    <option value="1" selected="selected">Подкатегория</option>
                                    <option value="2">Значение 2</option>
                                    <option value="3">Значение 3</option>
                                </select><select class="select-js" name="select">
                                    <option value="1" selected="selected">Модель</option>
                                    <option value="2">Значение 2</option>
                                    <option value="3">Значение 3</option>
                                </select>
                                <div class="prodForm__sizeWrap"><label class="prodForm__label">
                                        <p>Ширина, мм</p><input class="prodForm__input" type="text" name="name"
                                            placeholder="300" required="required" />
                                    </label><label class="prodForm__label">
                                        <p>Высота, мм</p><input class="prodForm__input" type="text" name="name"
                                            placeholder="500" required="required" />
                                    </label></div><select class="select-js" name="select">
                                    <option value="1" selected="selected">Выбирите управление</option>
                                    <option value="2">Левое</option>
                                    <option value="3">Правое</option>
                                </select>
                                <div class="prodForm__howMany"> <button class="minus">-</button><input
                                        type="text" placeholder="1" value="1" /><button
                                        class="plus">+</button></div>
                                <div class="prodForm__priceAndAddToCart">
                                    <div class="prodForm__price">Цена: 1200₽</div><button class="prodForm__addToCart">
                                        Добавить в корзину </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="s-main__slide swiper-slide wrapper">
                        <div class="s-main__info">
                            <div class="s-main__subtitle"> <span>Starting at </span><span>274₽</span></div>
                            <div class="s-main__title">The best tablet Collection 2023</div>
                            <div class="s-main__discount"> <span>Exclusive offer</span><span> -35% <svg width="94"
                                        height="20" viewBox="0 0 94 20" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M74.8576 4.63367L78.6048 5.11367C80.9097 5.35155 82.8309 5.75148 84.4483 5.97993L86.6581 6.31091L88.4262 6.63948C89.4684 6.81761 90.2699 6.9312 90.8805 6.99186C93.3213 7.24888 92.7011 6.63674 92.8183 6.12534C92.9355 5.61394 93.7175 5.37081 91.3267 4.45886C90.73 4.24001 89.9345 3.97481 88.8826 3.65818L87.1034 3.12577L84.8643 2.63282C83.236 2.28025 81.2402 1.82307 78.8684 1.52138L75.0177 0.981633C73.6188 0.823014 72.1417 0.730003 70.5389 0.582533C63.0297 0.0282543 55.4847 0.193022 48.0068 1.07459C39.9065 2.04304 31.9328 3.87384 24.2213 6.53586C18.0824 8.61764 12.1674 11.3089 6.56479 14.5692C4.88189 15.5255 3.25403 16.5756 1.68892 17.7145C0.568976 18.5077 -0.00964231 18.9932 0.0547097 19.0858C0.388606 19.6584 10.6194 13.1924 25.151 8.99361C32.789 6.72748 40.6283 5.20536 48.5593 4.44848C55.8569 3.76455 63.1992 3.69678 70.5082 4.24591L74.8223 4.62335"
                                            fill="currentColor"></path>
                                    </svg></span><span>off this week</span></div><a class="s-main__button"
                                href="#"> <span>Подробнее</span><i class="fas fa-arrow-right"></i></a>
                        </div>
                        <div class="prodForm calculator">
                            <div class="prodForm__galleryWrap">
                                <div class="prodForm__bar"> <img class="active" src="img/prod.jpg"
                                        alt="" /><img src="img/prod.jpg" alt="" /><img
                                        src="img/prod.jpg" alt="" /></div>
                                <div class="prodForm__imgWrap"> <img src="img/prod.jpg" alt="" /></div>
                            </div>
                            <div class="prodForm__calcFormWrap">
                                <div class="prodForm__formSubtitle">Подкатегория</div>
                                <div class="prodForm__formTitle">Товар</div><select class="select-js" name="select">
                                    <option value="1" selected="selected">Подкатегория</option>
                                    <option value="2">Значение 2</option>
                                    <option value="3">Значение 3</option>
                                </select><select class="select-js" name="select">
                                    <option value="1" selected="selected">Модель</option>
                                    <option value="2">Значение 2</option>
                                    <option value="3">Значение 3</option>
                                </select>
                                <div class="prodForm__sizeWrap"><label class="prodForm__label">
                                        <p>Ширина, мм</p><input class="prodForm__input" type="text" name="name"
                                            placeholder="300" required="required" />
                                    </label><label class="prodForm__label">
                                        <p>Высота, мм</p><input class="prodForm__input" type="text" name="name"
                                            placeholder="500" required="required" />
                                    </label></div><select class="select-js" name="select">
                                    <option value="1" selected="selected">Выбирите управление</option>
                                    <option value="2">Левое</option>
                                    <option value="3">Правое</option>
                                </select>
                                <div class="prodForm__howMany"> <button class="minus">-</button><input
                                        type="text" placeholder="1" value="1" /><button
                                        class="plus">+</button></div>
                                <div class="prodForm__priceAndAddToCart">
                                    <div class="prodForm__price">Цена: 1200₽</div><button class="prodForm__addToCart">
                                        Добавить в корзину </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="s-main__swiper-pagination swiper-pagination"></div>
                <div class="s-main__swiper-button-prev swiper-button-prev"></div>
                <div class="s-main__swiper-button-next swiper-button-next"></div>
            </div>
        </section>
        <div class="s-actions wrapper blueControls">
            <div class="s-actions__title-wrap">
                <h2 class="s-actions__title title"> <span>Акции </span><svg width="114" height="35"
                        viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                            stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                    </svg></h2><a class="s-actions__more btn" href=""> <span>Подробнее</span><i
                        class="fas fa-arrow-right"></i></a>
            </div>
            <div class="s-actions__cards">
                <div class="s-actions__swiper swiper">
                    <div class="swiper-wrapper">
                        <div class="s-actions__slide swiper-slide card">
                            <div class="bigProdCard">
                                <div class="bigProdCard__wrap">
                                    <div class="bigProdCard__img-wrap"><img src="img/prod.jpg" alt="" />
                                        <div class="bigProdCard__controls">
                                            <div class="bigProdCard__cart control"><i
                                                    class="fas fa-cart-arrow-down"></i>
                                                <div class="bigProdCard__toolTip">В корзину</div>
                                            </div>
                                            <div class="bigProdCard__quckView control" data-modal="#popupProd"><i
                                                    class="fas fa-eye"></i>
                                                <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                            </div>
                                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                                <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bigProdCard__info"> <a class="bigProdCard__category"
                                            href="">Категория</a><a class="bigProdCard__title"
                                            href="">РУЛОННЫЕ ЖАЛЮЗИ МИНИ </a>
                                        <div class="bigProdCard__priceWrap"> <span>1000₽</span><span>500₽</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="s-actions__slide swiper-slide card">
                            <div class="bigProdCard">
                                <div class="bigProdCard__wrap">
                                    <div class="bigProdCard__img-wrap"><img src="img/prod.jpg" alt="" />
                                        <div class="bigProdCard__controls">
                                            <div class="bigProdCard__cart control"><i
                                                    class="fas fa-cart-arrow-down"></i>
                                                <div class="bigProdCard__toolTip">В корзину</div>
                                            </div>
                                            <div class="bigProdCard__quckView control" data-modal="#popupProd"><i
                                                    class="fas fa-eye"></i>
                                                <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                            </div>
                                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                                <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bigProdCard__info"> <a class="bigProdCard__category"
                                            href="">Категория</a><a class="bigProdCard__title"
                                            href="">РУЛОННЫЕ ЖАЛЮЗИ МИНИ </a>
                                        <div class="bigProdCard__priceWrap"> <span>1000₽</span><span>500₽</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="s-actions__slide swiper-slide card">
                            <div class="bigProdCard">
                                <div class="bigProdCard__wrap">
                                    <div class="bigProdCard__img-wrap"><img src="img/prod.jpg" alt="" />
                                        <div class="bigProdCard__controls">
                                            <div class="bigProdCard__cart control"><i
                                                    class="fas fa-cart-arrow-down"></i>
                                                <div class="bigProdCard__toolTip">В корзину</div>
                                            </div>
                                            <div class="bigProdCard__quckView control" data-modal="#popupProd"><i
                                                    class="fas fa-eye"></i>
                                                <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                            </div>
                                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                                <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bigProdCard__info"> <a class="bigProdCard__category"
                                            href="">Категория</a><a class="bigProdCard__title"
                                            href="">РУЛОННЫЕ ЖАЛЮЗИ МИНИ </a>
                                        <div class="bigProdCard__priceWrap"> <span>1000₽</span><span>500₽</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="s-actions__slide swiper-slide card">
                            <div class="bigProdCard">
                                <div class="bigProdCard__wrap">
                                    <div class="bigProdCard__img-wrap"><img src="img/prod.jpg" alt="" />
                                        <div class="bigProdCard__controls">
                                            <div class="bigProdCard__cart control"><i
                                                    class="fas fa-cart-arrow-down"></i>
                                                <div class="bigProdCard__toolTip">В корзину</div>
                                            </div>
                                            <div class="bigProdCard__quckView control" data-modal="#popupProd"><i
                                                    class="fas fa-eye"></i>
                                                <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                            </div>
                                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                                <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bigProdCard__info"> <a class="bigProdCard__category"
                                            href="">Категория</a><a class="bigProdCard__title"
                                            href="">РУЛОННЫЕ ЖАЛЮЗИ МИНИ </a>
                                        <div class="bigProdCard__priceWrap"> <span>1000₽</span><span>500₽</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="s-actions__slide swiper-slide card">
                            <div class="bigProdCard">
                                <div class="bigProdCard__wrap">
                                    <div class="bigProdCard__img-wrap"><img src="img/prod.jpg" alt="" />
                                        <div class="bigProdCard__controls">
                                            <div class="bigProdCard__cart control"><i
                                                    class="fas fa-cart-arrow-down"></i>
                                                <div class="bigProdCard__toolTip">В корзину</div>
                                            </div>
                                            <div class="bigProdCard__quckView control" data-modal="#popupProd"><i
                                                    class="fas fa-eye"></i>
                                                <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                            </div>
                                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                                <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bigProdCard__info"> <a class="bigProdCard__category"
                                            href="">Категория</a><a class="bigProdCard__title"
                                            href="">РУЛОННЫЕ ЖАЛЮЗИ МИНИ </a>
                                        <div class="bigProdCard__priceWrap"> <span>1000₽</span><span>500₽</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="s-actions__slide swiper-slide card">
                            <div class="bigProdCard">
                                <div class="bigProdCard__wrap">
                                    <div class="bigProdCard__img-wrap"><img src="img/prod.jpg" alt="" />
                                        <div class="bigProdCard__controls">
                                            <div class="bigProdCard__cart control"><i
                                                    class="fas fa-cart-arrow-down"></i>
                                                <div class="bigProdCard__toolTip">В корзину</div>
                                            </div>
                                            <div class="bigProdCard__quckView control" data-modal="#popupProd"><i
                                                    class="fas fa-eye"></i>
                                                <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                            </div>
                                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                                <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bigProdCard__info"> <a class="bigProdCard__category"
                                            href="">Категория</a><a class="bigProdCard__title"
                                            href="">РУЛОННЫЕ ЖАЛЮЗИ МИНИ </a>
                                        <div class="bigProdCard__priceWrap"> <span>1000₽</span><span>500₽</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </div>
        <div class="s-populars wrapper blueControls homePopulars">
            <div class="s-populars__title-wrap">
                <h2 class="s-populars__title title"> <span>Популярные товары </span><svg width="114"
                        height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                            stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                    </svg></h2>
                <ul class="s-populars__tabsNav">
                    <li class="active"><span>Шторы</span><svg width="52" height="13" viewBox="0 0 52 13"
                            fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 8.97127C11.6061 -5.48521 33 3.99996 51 11.4635" stroke="currentColor"
                                stroke-width="2" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                        </svg></li>
                    <li> <span>Жалюзи</span><svg width="52" height="13" viewBox="0 0 52 13" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 8.97127C11.6061 -5.48521 33 3.99996 51 11.4635" stroke="currentColor"
                                stroke-width="2" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                        </svg></li>
                    <li> <span>Карнизы</span><svg width="52" height="13" viewBox="0 0 52 13" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 8.97127C11.6061 -5.48521 33 3.99996 51 11.4635" stroke="currentColor"
                                stroke-width="2" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                        </svg></li>
                    <li> <span>Рольставни</span><svg width="52" height="13" viewBox="0 0 52 13"
                            fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 8.97127C11.6061 -5.48521 33 3.99996 51 11.4635" stroke="currentColor"
                                stroke-width="2" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                        </svg></li>
                    <li> <span>Секционные ворота</span><svg width="52" height="13" viewBox="0 0 52 13"
                            fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 8.97127C11.6061 -5.48521 33 3.99996 51 11.4635" stroke="currentColor"
                                stroke-width="2" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                        </svg></li>
                </ul>
            </div>
            <div class="s-populars__cards">
                <div class="s-populars__swiper swiper">
                    <div class="swiper-wrapper">
                        <div class="s-populars__slide swiper-slide card">
                            <div class="bigProdCard">
                                <div class="bigProdCard__wrap">
                                    <div class="bigProdCard__img-wrap"><img src="img/prod.jpg" alt="" />
                                        <div class="bigProdCard__controls">
                                            <div class="bigProdCard__cart control"><i
                                                    class="fas fa-cart-arrow-down"></i>
                                                <div class="bigProdCard__toolTip">В корзину</div>
                                            </div>
                                            <div class="bigProdCard__quckView control" data-modal="#popupProd"><i
                                                    class="fas fa-eye"></i>
                                                <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                            </div>
                                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                                <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bigProdCard__info"> <a class="bigProdCard__category"
                                            href="">Категория</a><a class="bigProdCard__title"
                                            href="">РУЛОННЫЕ ЖАЛЮЗИ МИНИ </a>
                                        <div class="bigProdCard__priceWrap"> <span>1000₽</span><span>500₽</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="s-populars__slide swiper-slide card">
                            <div class="bigProdCard">
                                <div class="bigProdCard__wrap">
                                    <div class="bigProdCard__img-wrap"><img src="img/prod.jpg" alt="" />
                                        <div class="bigProdCard__controls">
                                            <div class="bigProdCard__cart control"><i
                                                    class="fas fa-cart-arrow-down"></i>
                                                <div class="bigProdCard__toolTip">В корзину</div>
                                            </div>
                                            <div class="bigProdCard__quckView control" data-modal="#popupProd"><i
                                                    class="fas fa-eye"></i>
                                                <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                            </div>
                                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                                <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bigProdCard__info"> <a class="bigProdCard__category"
                                            href="">Категория</a><a class="bigProdCard__title"
                                            href="">РУЛОННЫЕ ЖАЛЮЗИ МИНИ </a>
                                        <div class="bigProdCard__priceWrap"> <span>1000₽</span><span>500₽</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="s-populars__slide swiper-slide card">
                            <div class="bigProdCard">
                                <div class="bigProdCard__wrap">
                                    <div class="bigProdCard__img-wrap"><img src="img/prod.jpg" alt="" />
                                        <div class="bigProdCard__controls">
                                            <div class="bigProdCard__cart control"><i
                                                    class="fas fa-cart-arrow-down"></i>
                                                <div class="bigProdCard__toolTip">В корзину</div>
                                            </div>
                                            <div class="bigProdCard__quckView control" data-modal="#popupProd"><i
                                                    class="fas fa-eye"></i>
                                                <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                            </div>
                                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                                <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bigProdCard__info"> <a class="bigProdCard__category"
                                            href="">Категория</a><a class="bigProdCard__title"
                                            href="">РУЛОННЫЕ ЖАЛЮЗИ МИНИ </a>
                                        <div class="bigProdCard__priceWrap"> <span>1000₽</span><span>500₽</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="s-populars__slide swiper-slide card">
                            <div class="bigProdCard">
                                <div class="bigProdCard__wrap">
                                    <div class="bigProdCard__img-wrap"><img src="img/prod.jpg" alt="" />
                                        <div class="bigProdCard__controls">
                                            <div class="bigProdCard__cart control"><i
                                                    class="fas fa-cart-arrow-down"></i>
                                                <div class="bigProdCard__toolTip">В корзину</div>
                                            </div>
                                            <div class="bigProdCard__quckView control" data-modal="#popupProd"><i
                                                    class="fas fa-eye"></i>
                                                <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                            </div>
                                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                                <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bigProdCard__info"> <a class="bigProdCard__category"
                                            href="">Категория</a><a class="bigProdCard__title"
                                            href="">РУЛОННЫЕ ЖАЛЮЗИ МИНИ </a>
                                        <div class="bigProdCard__priceWrap"> <span>1000₽</span><span>500₽</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="s-populars__slide swiper-slide card">
                            <div class="bigProdCard">
                                <div class="bigProdCard__wrap">
                                    <div class="bigProdCard__img-wrap"><img src="img/prod.jpg" alt="" />
                                        <div class="bigProdCard__controls">
                                            <div class="bigProdCard__cart control"><i
                                                    class="fas fa-cart-arrow-down"></i>
                                                <div class="bigProdCard__toolTip">В корзину</div>
                                            </div>
                                            <div class="bigProdCard__quckView control" data-modal="#popupProd"><i
                                                    class="fas fa-eye"></i>
                                                <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                            </div>
                                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                                <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bigProdCard__info"> <a class="bigProdCard__category"
                                            href="">Категория</a><a class="bigProdCard__title"
                                            href="">РУЛОННЫЕ ЖАЛЮЗИ МИНИ </a>
                                        <div class="bigProdCard__priceWrap"> <span>1000₽</span><span>500₽</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="s-populars__slide swiper-slide card">
                            <div class="bigProdCard">
                                <div class="bigProdCard__wrap">
                                    <div class="bigProdCard__img-wrap"><img src="img/prod.jpg" alt="" />
                                        <div class="bigProdCard__controls">
                                            <div class="bigProdCard__cart control"><i
                                                    class="fas fa-cart-arrow-down"></i>
                                                <div class="bigProdCard__toolTip">В корзину</div>
                                            </div>
                                            <div class="bigProdCard__quckView control" data-modal="#popupProd"><i
                                                    class="fas fa-eye"></i>
                                                <div class="bigProdCard__toolTip">Быстрый просмотр</div>
                                            </div>
                                            <div class="bigProdCard__favorites control"><i class="far fa-heart"></i>
                                                <div class="bigProdCard__toolTip">Добавить в избранное</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bigProdCard__info"> <a class="bigProdCard__category"
                                            href="">Категория</a><a class="bigProdCard__title"
                                            href="">РУЛОННЫЕ ЖАЛЮЗИ МИНИ </a>
                                        <div class="bigProdCard__priceWrap"> <span>1000₽</span><span>500₽</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"> </div>
        </div>
        <div class="s-how wrapper">
            <div class="s-how__bgWrap"> <img src="img/subscribe-shape-1.png" alt="" /></div>
            <div class="s-how__bgWrapBottom"><img src="img/subscribe-shape-1.png" alt="" /></div>
            <div class="s-how__textWrap">
                <div class="s-how__subtitle">Остались вопросы?</div>
                <h2 class="s-how__title">Как заказать</h2>
                <div class="s-how__text">Заполните форму, и наш менеджер ответит на все ваши вопросы. </div>
            </div>
            <div class="s-how__formWrap">
                <div class="s-how__formTitle">Задать вопрос </div>
                <div class="s-how__inputsWrap"><label class="s-how__label">
                        <p>Имя</p><input class="s-how__input" type="text" name="name" placeholder="Андрей"
                            required="required" />
                    </label><label class="s-how__label">
                        <p>Номер</p><input class="s-how__input" type="text" name="name"
                            placeholder="+79204456542" required="required" />
                    </label></div><label class="s-how__label">
                    <p>Вопрос</p>
                    <textarea class="s-how__input textarea" type="text" name="text" placeholder="Ваш вопрос"></textarea>
                </label>
                <div class="s-how__button btn">Отправить</div>
            </div>
        </div>
        <div class="s-delivery wrapper">
            <h2 class="s-delivery__title title"> <span>Оплата и доставка</span><svg width="114" height="35"
                    viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                        stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                </svg></h2>
            <div class="s-delivery__text"> Lorem ipsum dolor sit amet consectetur adipisicing elit. Fugit corrupti a
                doloremque accusamus qui tenetur dolorem reiciendis sequi vero, illum quo repellat cum vel rerum
                deleniti, sunt officiis, vitae ad.</div>
            <div class="s-delivery__cards">
                <div class="deliveryCard">
                    <div class="deliveryCard__iconWrap"> <i class="fas fa-truck"></i></div>
                    <div class="deliveryCard__info">
                        <div class="deliveryCard__title">Free Delivary</div>
                        <div class="deliveryCard__text">Orders from all item</div>
                    </div>
                </div>
                <div class="deliveryCard">
                    <div class="deliveryCard__iconWrap"> <i class="fas fa-truck"></i></div>
                    <div class="deliveryCard__info">
                        <div class="deliveryCard__title">Free Delivary</div>
                        <div class="deliveryCard__text">Orders from all item</div>
                    </div>
                </div>
                <div class="deliveryCard">
                    <div class="deliveryCard__iconWrap"> <i class="fas fa-truck"></i></div>
                    <div class="deliveryCard__info">
                        <div class="deliveryCard__title">Free Delivary</div>
                        <div class="deliveryCard__text">Orders from all item</div>
                    </div>
                </div>
                <div class="deliveryCard">
                    <div class="deliveryCard__iconWrap"> <i class="fas fa-truck"></i></div>
                    <div class="deliveryCard__info">
                        <div class="deliveryCard__title">Free Delivary</div>
                        <div class="deliveryCard__text">Orders from all item</div>
                    </div>
                </div>
                <div class="deliveryCard">
                    <div class="deliveryCard__iconWrap"> <i class="fas fa-truck"></i></div>
                    <div class="deliveryCard__info">
                        <div class="deliveryCard__title">Free Delivary</div>
                        <div class="deliveryCard__text">Orders from all item</div>
                    </div>
                </div>
                <div class="deliveryCard">
                    <div class="deliveryCard__iconWrap"> <i class="fas fa-truck"></i></div>
                    <div class="deliveryCard__info">
                        <div class="deliveryCard__title">Free Delivary</div>
                        <div class="deliveryCard__text">Orders from all item</div>
                    </div>
                </div>
                <div class="deliveryCard">
                    <div class="deliveryCard__iconWrap"> <i class="fas fa-truck"></i></div>
                    <div class="deliveryCard__info">
                        <div class="deliveryCard__title">Free Delivary</div>
                        <div class="deliveryCard__text">Orders from all item</div>
                    </div>
                </div>
                <div class="deliveryCard">
                    <div class="deliveryCard__iconWrap"> <i class="fas fa-truck"></i></div>
                    <div class="deliveryCard__info">
                        <div class="deliveryCard__title">Free Delivary</div>
                        <div class="deliveryCard__text">Orders from all item</div>
                    </div>
                </div>
            </div>
            <div class="s-delivery__text"> Lorem ipsum dolor sit amet consectetur adipisicing elit. Fugit corrupti a
                doloremque accusamus qui tenetur dolorem reiciendis sequi vero, illum quo repellat cum vel rerum
                deleniti, sunt officiis, vitae ad.</div>
        </div>
        <div class="s-faq wrapper">
            <h2 class="s-faq__title title"> <span>Вопросы и ответы </span><svg width="114" height="35"
                    viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                        stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                </svg></h2>
            <div class="accardionJs">
                <div class="accardion__title">Заголовок</div>
                <div class="accardion__content">Lorem ipsum dolor sit amet consectetur adipisicing elit. Neque,
                    temporibus a, iste tenetur assumenda atque obcaecati, corporis voluptas vel cumque modi odio dolorem
                    dolorum cum. Culpa harum porro adipisci architecto?</div>
            </div>
            <div class="accardionJs">
                <div class="accardion__title">Заголовок</div>
                <div class="accardion__content">Lorem ipsum dolor sit amet consectetur adipisicing elit. Neque,
                    temporibus a, iste tenetur assumenda atque obcaecati, corporis voluptas vel cumque modi odio dolorem
                    dolorum cum. Culpa harum porro adipisci architecto?</div>
            </div>
        </div>
        <div class="s-revs wrapper blueControls">
            <h2 class="s-revs__title title"> <span>Отзывы</span><svg width="114" height="35"
                    viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                        stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                </svg></h2>
            <div class="s-revs__cards">
                <div class="s-revs__swiper swiper">
                    <div class="swiper-wrapper">
                        <div class="revCard swiper-slide">
                            <div class="revCard__wrap">
                                <div class="revCard__avatar"> <img src="img/rev.jpg" alt="" /></div>
                                <div class="revCard__name">Наталья Городецкая</div>
                            </div><i class="far fa-comments"></i>
                            <div class="revCard__text">Lorem ipsum dolor, sit amet consectetur adipisicing elit. Quas
                                nulla fugiat labore ullam, saepe quis optio pariatur cum facilis eveniet recusandae
                                dolorem veritatis quisquam error maiores facere officiis sit vitae.</div>
                        </div>
                        <div class="revCard swiper-slide">
                            <div class="revCard__wrap">
                                <div class="revCard__avatar"> <img src="img/rev.jpg" alt="" /></div>
                                <div class="revCard__name">Наталья Городецкая</div>
                            </div><i class="far fa-comments"></i>
                            <div class="revCard__text">Lorem ipsum dolor, sit amet consectetur adipisicing elit. Quas
                                nulla fugiat labore ullam, saepe quis optio pariatur cum facilis eveniet recusandae
                                dolorem veritatis quisquam error maiores facere officiis sit vitae.</div>
                        </div>
                        <div class="revCard swiper-slide">
                            <div class="revCard__wrap">
                                <div class="revCard__avatar"> <img src="img/rev.jpg" alt="" /></div>
                                <div class="revCard__name">Наталья Городецкая</div>
                            </div><i class="far fa-comments"></i>
                            <div class="revCard__text">Lorem ipsum dolor, sit amet consectetur adipisicing elit. Quas
                                nulla fugiat labore ullam, saepe quis optio pariatur cum facilis eveniet recusandae
                                dolorem veritatis quisquam error maiores facere officiis sit vitae.</div>
                        </div>
                        <div class="revCard swiper-slide">
                            <div class="revCard__wrap">
                                <div class="revCard__avatar"> <img src="img/rev.jpg" alt="" /></div>
                                <div class="revCard__name">Наталья Городецкая</div>
                            </div><i class="far fa-comments"></i>
                            <div class="revCard__text">Lorem ipsum dolor, sit amet consectetur adipisicing elit. Quas
                                nulla fugiat labore ullam, saepe quis optio pariatur cum facilis eveniet recusandae
                                dolorem veritatis quisquam error maiores facere officiis sit vitae.</div>
                        </div>
                        <div class="revCard swiper-slide">
                            <div class="revCard__wrap">
                                <div class="revCard__avatar"> <img src="img/rev.jpg" alt="" /></div>
                                <div class="revCard__name">Наталья Городецкая</div>
                            </div><i class="far fa-comments"></i>
                            <div class="revCard__text">Lorem ipsum dolor, sit amet consectetur adipisicing elit. Quas
                                nulla fugiat labore ullam, saepe quis optio pariatur cum facilis eveniet recusandae
                                dolorem veritatis quisquam error maiores facere officiis sit vitae.</div>
                        </div>
                        <div class="revCard swiper-slide">
                            <div class="revCard__wrap">
                                <div class="revCard__avatar"> <img src="img/rev.jpg" alt="" /></div>
                                <div class="revCard__name">Наталья Городецкая</div>
                            </div><i class="far fa-comments"></i>
                            <div class="revCard__text">Lorem ipsum dolor, sit amet consectetur adipisicing elit. Quas
                                nulla fugiat labore ullam, saepe quis optio pariatur cum facilis eveniet recusandae
                                dolorem veritatis quisquam error maiores facere officiis sit vitae.</div>
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"> </div>
            </div>
        </div>
        <div class="s-seo wrapper">
            <h2>Title h2 </h2>
            <p>Lorem ipsum dolor sit amet consectetur, adipisicing elit. Libero ipsum modi veritatis accusantium, at
                incidunt veniam officiis! Excepturi explicabo, beatae id sequi dignissimos non eaque error voluptatum
                commodi ullam eius.</p>
            <h3>Title h3</h3>
            <p>Lorem ipsum dolor sit amet consectetur, adipisicing elit. Libero ipsum modi veritatis accusantium, at
                incidunt veniam officiis! Excepturi explicabo, beatae id sequi dignissimos non eaque error voluptatum
                commodi ullam eius.</p>
            <h4>Title h4</h4>
            <p>Lorem ipsum dolor sit amet consectetur, adipisicing elit. Libero ipsum modi veritatis accusantium, at
                incidunt veniam officiis! Excepturi explicabo, beatae id sequi dignissimos non eaque error voluptatum
                commodi ullam eius.</p>
            <h5>Title h5</h5>
            <p>Lorem, ipsum dolor sit amet consectetur adipisicing elit. Iure magni possimus eligendi illo explicabo
                assumenda est commodi, quo, reiciendis hic sapiente harum dolorum, numquam quas. Ullam ipsa voluptatum
                consequatur excepturi?</p>
            <p>Lorem ipsum dolor sit amet consectetur, adipisicing elit. Libero ipsum modi veritatis accusantium, at
                incidunt veniam officiis! Excepturi explicabo, beatae id sequi dignissimos non eaque error voluptatum
                commodi ullam eius.</p>
            <ul>
                <li> Lorem ipsum dolor sit amet consectetur adipisicing elit. </li>
                <li> Lorem ipsum dolor sit amet consectetur adipisicing elit. </li>
                <li> Lorem ipsum dolor sit amet consectetur adipisicing elit. </li>
                <li> Lorem ipsum dolor sit amet consectetur adipisicing elit. </li>
                <li> Lorem ipsum dolor sit amet consectetur adipisicing elit. </li>
            </ul>
            <ol>
                <li> Lorem ipsum dolor sit amet consectetur adipisicing elit. </li>
                <li> Lorem ipsum dolor sit amet consectetur adipisicing elit. </li>
                <li> Lorem ipsum dolor sit amet consectetur adipisicing elit. </li>
                <li> Lorem ipsum dolor sit amet consectetur adipisicing elit. </li>
                <li> Lorem ipsum dolor sit amet consectetur adipisicing elit. </li>
            </ol><a href="#">Link to some text </a>
        </div>
        <div class="s-map"><iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2245.6495178790883!2d37.53659187769875!3d55.747218392420145!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x46b54bdd017303b9%3A0xd1f63f945a2450c2!2z0JzQvtGB0LrQstCwINCh0LjRgtC4!5e0!3m2!1sru!2sru!4v1722851717459!5m2!1sru!2sru"
                width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"></iframe></div>
    </main>
    <footer class="footer wrapper">
        <div class="footer__col"> <a class="footer__logowrap" href="/"><img src="img/logo.svg"
                    alt="" /></a>
            <div class="footer__requzits"> Lorem ipsum dolor sit amet consectetur adipisicing elit. Odio ipsam ad
                nihil quia voluptatem totam ratione dolores repellendus</div>
            <div class="footer__buttons">
                <div class="footer__btn btn" data-modal="#measure">Заказать Замер</div>
                <div class="footer__btn btn" data-modal="#call">Заказать Звонок</div>
            </div>
            <div class="footer__socials"> <a class="footer__social" href="#"> <i
                        class="fab fa-vk"></i></a><a class="footer__social" href="#"> <i
                        class="fab fa-vk"></i></a><a class="footer__social" href="#"> <i
                        class="fab fa-vk"></i></a></div>
        </div>
        <div class="footer__col">
            <div class="footer__linkList">
                <div class="footer__listTitle">Information </div>
                <ul>
                    <li>Калькулятор</li>
                    <li>Our Story</li>
                    <li>Our Story</li>
                    <li>Our Story</li>
                    <li>Our Story</li>
                </ul>
            </div>
            <div class="footer__linkList">
                <div class="footer__listTitle">Information </div>
                <ul>
                    <li>Our Story</li>
                    <li>Our Story</li>
                    <li>Our Story</li>
                    <li>Our Story</li>
                    <li>Our Story</li>
                </ul>
            </div>
        </div>
        <div class="footer__col">
            <div class="footer__listTitle">Контакты</div>
            <div class="footer__phoneWrap">
                <div class="footer__phoneText">Got Questions? Call us</div><a
                    href="tel:+79201134877">+79201134877</a>
            </div>
            <div class="footer__info">
                <div class="footer__infoIcon"> <i class="far fa-clock"></i></div><span>Пн-Вс: 09-00 - 20-00</span>
            </div>
            <div class="footer__info">
                <div class="footer__infoIcon"> <i class="far fa-clock"></i></div><span>г. Москва, ул Гоголя д.
                    3</span>
            </div>
        </div>
    </footer>
    <div class="formWrapper" id="call">
        <form class="form popupForm">
            <div class="form__title">Заказать звонок</div><label class="form__label">
                <p>Имя</p><input class="form__input" type="text" name="name" placeholder="Александр"
                    required="required" />
            </label><label class="form__label">
                <p>Телефон</p><input class="form__input" type="text" name="email"
                    placeholder="+7(920)-113-46-44" required="required" />
            </label><label class="form__label">
                <p>Коментарий</p>
                <textarea class="form__input textarea" type="text" name="text" placeholder="Tell us about your project"></textarea>
            </label><button class="form__btn btn">Send Massage</button>
        </form>
        <div class="ajaxMessage">
            <div class="ajaxMessage__success">
                <div class="ajaxMessage__title">
                    <p>Спасибо!</p>
                    <p>Ваша заявка принята</p>
                </div>
                <div class="ajaxMessage__text">Мы свяжемся с вами в ближайшее время, что бы обсудить детали и ответить
                    на вопросы</div>
            </div>
            <div class="ajaxMessage__error">
                <div class="ajaxMessage__title">Ошибка при отправке!</div>
                <div class="ajaxMessage__text">Попробуйте позднее</div>
            </div><button class="btn closeModal">[object Object]</button>
        </div>
    </div>
    <div class="formWrapper" id="measure">
        <form class="form popupForm">
            <div class="form__title">Заказать замер</div><label class="form__label">
                <p>Имя</p><input class="form__input" type="text" name="name" placeholder="Александр"
                    required="required" />
            </label><label class="form__label">
                <p>Телефон</p><input class="form__input" type="text" name="email"
                    placeholder="+7(920)-113-46-44" required="required" />
            </label><label class="form__label">
                <p>Коментарий</p>
                <textarea class="form__input textarea" type="text" name="text" placeholder="Tell us about your project"></textarea>
            </label><button class="form__btn btn">Send Massage</button>
        </form>
        <div class="ajaxMessage">
            <div class="ajaxMessage__success">
                <div class="ajaxMessage__title">
                    <p>Спасибо!</p>
                    <p>Ваша заявка принята</p>
                </div>
                <div class="ajaxMessage__text">Мы свяжемся с вами в ближайшее время, что бы обсудить детали и ответить
                    на вопросы</div>
            </div>
            <div class="ajaxMessage__error">
                <div class="ajaxMessage__title">Ошибка при отправке!</div>
                <div class="ajaxMessage__text">Попробуйте позднее</div>
            </div><button class="btn closeModal">[object Object]</button>
        </div>
    </div>
    <div class="formWrapper prodPopup" id="popupProd">
        <div class="prodForm">
            <div class="prodForm__galleryWrap">
                <div class="prodForm__bar"> <img class="active" src="img/prod.jpg" alt="" /><img
                        src="img/prod.jpg" alt="" /><img src="img/prod.jpg" alt="" /></div>
                <div class="prodForm__imgWrap"> <img src="img/prod.jpg" alt="" /></div>
            </div>
            <div class="prodForm__calcFormWrap">
                <div class="prodForm__formSubtitle">Товар 1</div>
                <div class="prodForm__formTitle">Заказать товар</div>
                <div class="prodForm__description">
                    <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Minus incidunt sit quisquam illum
                        pariatur vitae est cumque ea similique corporis deleniti, veritatis culpa! Saepe fugiat
                        quibusdam voluptas ratione quaerat voluptatibus...</p><span class="more">Подробнее</span>
                </div>
                <div class="prodForm__colorPickerWrap">
                    <div class="prodForm__colorPickerTitle">Выбирите цвет:</div>
                    <div class="prodForm__colorPicker">
                        <div class="prodForm__color red active"></div>
                        <div class="prodForm__color green"></div>
                        <div class="prodForm__color blue"></div>
                    </div>
                </div>
                <div class="prodForm__sizeWrap"><label class="prodForm__label">
                        <p>Ширина, мм</p><input class="prodForm__input" type="text" name="name"
                            placeholder="300" required="required" />
                    </label><label class="prodForm__label">
                        <p>Высота, мм</p><input class="prodForm__input" type="text" name="name"
                            placeholder="500" required="required" />
                    </label></div><select class="select-js" name="select">
                    <option value="1" selected="selected">Выбирите управление</option>
                    <option value="2">Левое</option>
                    <option value="3">Правое</option>
                </select>
                <div class="prodForm__howMany"> <button class="minus">-</button><input type="text"
                        placeholder="1" value="1" /><button class="plus">+</button></div>
                <div class="prodForm__priceAndAddToCart">
                    <div class="prodForm__price">Цена: 1200₽</div><button class="prodForm__addToCart"> Добавить в
                        корзину </button>
                </div>
            </div>
        </div>
    </div>
    @vite('resources/js/main.js')
    @vite('resources/js/swiper.js')

    {{-- <script src="js/main.js"></script> --}}
    {{-- <script src="js/swiper.js"></script> --}}
    <script src="https://kit.fontawesome.com/9d3fa3c0db.js" crossorigin="anonymous"></script>
</body>

</html>
