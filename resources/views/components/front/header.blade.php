<header class="header">
    <ul class="header__topMenu wrapper">
        <li> <a href="/shop-pages/uslugi">Услуги </a></li>
        <li> <a href="/shop-pages/rasschitat">Рассчитать </a></li>
        <li> <a href="/shop-pages/portfolio">Портфолио </a></li>
        <li> <a href="/shop-pages/oplata-i-dostavka">Оплата и Доставка </a></li>
        <li> <a href="/shop-pages/kontakty">Контакты</a></li>
    </ul>
    <div class="header__bottomMenu wrapper">
        <a class="header__logo" href="/">
            {{-- <img src="{{ asset('img/logo.svg') }}" alt="Logo"> --}}
            <svg xmlns="http://www.w3.org/2000/svg" id="logo" viewBox="0 0 600 280">
                <defs>
                    <style>
                        .cls-1,
                        .cls-4 {
                            fill: none;
                        }

                        .cls-1 {
                            stroke: {{$headerInfo->logo_color}} !important;
                            stroke-width: 5.67px;
                        }

                        .cls-1,
                        .cls-2 {
                            stroke-miterlimit: 10;
                        }

                        .cls-2 {
                            stroke: #000;
                            stroke-width: 0.85px;
                        }

                        .cls-3 {
                            fill: {{$headerInfo->logo_color}} !important;
                        }
                    </style>
                </defs>
                {{-- <title>Монтажная область 7</title> --}}
                <g id="_1" data-name="1">
                    <polyline class="cls-1"
                        points="86.25 178.58 101.25 178.58 101.25 109.71 139.25 80.27 177.25 109.71 177.25 178.58 192.25 178.58" />
                    <polygon
                        points="192.09 167.58 186.42 167.58 186.42 123.43 139.25 165.24 92.09 123.43 92.09 167.58 86.42 167.58 86.42 110.83 139.25 157.66 192.09 110.83 192.09 167.58" />
                    <path
                        d="M139.25,228.83a89.25,89.25,0,1,1,89.25-89.25A89.35,89.35,0,0,1,139.25,228.83Zm0-170A80.75,80.75,0,1,0,220,139.58,80.84,80.84,0,0,0,139.25,58.83Z" />
                </g>
                <g id="_2" data-name="2">
                    <path class="cls-2"
                        d="M267.18,82.54l-3.25,2a13.6,13.6,0,0,0-11.51-5.64,13.34,13.34,0,0,0-9.7,3.75,12.25,12.25,0,0,0-3.85,9.13,13,13,0,0,0,1.77,6.58,12.27,12.27,0,0,0,4.86,4.78,14.78,14.78,0,0,0,18.43-3.89l3.25,2.12a15.18,15.18,0,0,1-6.15,5.3,19.79,19.79,0,0,1-8.79,1.88,17.3,17.3,0,0,1-12.51-4.8,15.57,15.57,0,0,1-5-11.65,16.66,16.66,0,0,1,2.34-8.62,16.42,16.42,0,0,1,6.4-6.19,18.65,18.65,0,0,1,9.11-2.22,19.59,19.59,0,0,1,6.09,1,16.56,16.56,0,0,1,5,2.51A13.44,13.44,0,0,1,267.18,82.54Z" />
                    <path class="cls-2" d="M279.94,75.91h21v3.55h-8.4V107.8h-4.16V79.46h-8.4Z" />
                    <path class="cls-2"
                        d="M320.4,75.91V99.48l23.39-23.57h.32V107.8H340V84.43L316.76,107.8h-.49V75.91Z" />
                    <path class="cls-2" d="M390,107.8h-4.36L375,84.6,364.4,107.8H360L374.6,75.91h.73Z" />
                    <path class="cls-2"
                        d="M410.12,88.52H414a27.94,27.94,0,0,1,7.3.75,8.51,8.51,0,0,1,4.53,3.25A9.67,9.67,0,0,1,425,105c-1.82,1.86-4.6,2.8-8.34,2.8H406.05V75.91h4.07Zm.14,3.78v12H415q8.34,0,8.35-5.88a6.77,6.77,0,0,0-.89-3.27,4.8,4.8,0,0,0-2.62-2.25,19.85,19.85,0,0,0-6.06-.64Z" />
                    <path class="cls-2"
                        d="M466.74,92.45H448.22V107.8h-4.13V75.91h4.13V88.58h18.52V75.91h4.1V107.8h-4.1Z" />
                    <path class="cls-2"
                        d="M493.8,88.52h3.89a28,28,0,0,1,7.3.75,8.51,8.51,0,0,1,4.53,3.25,9.67,9.67,0,0,1-.84,12.48c-1.82,1.86-4.6,2.8-8.34,2.8H489.73V75.91h4.07Zm.14,3.78v12h4.71q8.34,0,8.34-5.88a6.75,6.75,0,0,0-.88-3.27,4.87,4.87,0,0,0-2.62-2.25,19.85,19.85,0,0,0-6.06-.64Zm23.17-16.39h4.13V107.8h-4.13Z" />
                    <path class="cls-2"
                        d="M543.88,75.91V99.48l23.39-23.57h.32V107.8h-4.12V84.43L540.24,107.8h-.49V75.91Zm-.59-12.79h3.9a5.94,5.94,0,0,0,2.63,2.27,9.68,9.68,0,0,0,4,.75,9.14,9.14,0,0,0,3.77-.66,7.59,7.59,0,0,0,2.74-2.36h3.72a7.2,7.2,0,0,1-3.27,4.4,12.48,12.48,0,0,1-7,1.76,12.75,12.75,0,0,1-7-1.74A7.76,7.76,0,0,1,543.29,63.12Z" />
                </g>
                <g id="_3" data-name="3">
                    <path
                        d="M305.16,211.58A56.06,56.06,0,0,1,330.1,165a51.89,51.89,0,0,1,62.3,0A56.06,56.06,0,0,1,414,230.66a58,58,0,1,0-105.49,0A55.87,55.87,0,0,1,305.16,211.58Z" />
                    <path class="cls-3"
                        d="M330.69,258.58a56.39,56.39,0,0,1-13.22-12H277.25V119.46L202.81,246.58H137.25c-58.72,0-106.5-48-106.5-107A107,107,0,0,1,226.48,79.76l.2-.14c.29-.57.6-1.14.93-1.7a27.26,27.26,0,0,1,6.81-7.75A119.07,119.07,0,1,0,53.34,223.71a117.47,117.47,0,0,0,83.91,34.87h118v-12H216.72l48.53-82.88v94.88Z" />
                    <path class="cls-3"
                        d="M567.25,246.58V119.85l-61,102.05-61-102.05V246.58H405a56.44,56.44,0,0,1-13.23,12h65.44V163.31l49,82,49-82v95.27h26v-12Z" />
                    <circle class="cls-4" cx="361.25" cy="211.58" r="47.58"
                        transform="matrix(0.57, -0.82, 0.82, 0.57, -18.42, 387.99)" />
                    <path class="cls-3"
                        d="M361.25,163.42a48,48,0,1,0,48,48A48.05,48.05,0,0,0,361.25,163.42Zm0,85a37,37,0,1,1,37-37A37,37,0,0,1,361.25,248.42Z" />
                </g>
                <script xmlns="" />
            </svg>
            <span style="display: block; font-size:16px; text-transform:uppercase">жалюзи, роллеты, ворота</span>
        </a>
        <div class="header__infoWrap">
            <div class="header__infoWrapItem">
                <div class="header__infoItemIcon"> <i class="fas fa-map-signs"></i></div><span>Адрес:{{$headerInfo->address}}</span>
            </div>
            <div class="header__infoWrapItem">
                <div class="header__infoItemIcon"> <i class="far fa-clock"></i></div><span>{{$headerInfo->working_hours}}</span>
            </div>
        </div>
        <div class="header__infoWrap">
            <div class="header__infoWrapItem">
                <div class="header__infoItemIcon"> <i class="fas fa-phone"></i></div><a
                    href="tel:{{$headerInfo->phone_number}}">{{$headerInfo->phone_number}}</a>
            </div>
            <div class="header__infoWrapItem">
                <div class="header__infoItemIcon"> <i class="fab fa-whatsapp"></i></div><a
                    href="https://wa.me/{{$headerInfo->phone_number}}">{{$headerInfo->phone_number}}</a>
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
        <div class="header__wrapTomobile"><a class="header__cartIconWrap" href="/cart">

                <span class="header__cartCounter">{{ collect($cart)->sum('quantity') ?: 0 }}</span>

                <svg width="21" height="22" viewBox="0 0 21 22" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M6.48626 20.5H14.8341C17.9004 20.5 20.2528 19.3924 19.5847 14.9348L18.8066 8.89359C18.3947 6.66934 16.976 5.81808 15.7311 5.81808H5.55262C4.28946 5.81808 2.95308 6.73341 2.4771 8.89359L1.69907 14.9348C1.13157 18.889 3.4199 20.5 6.48626 20.5Z"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    </path>
                    <path
                        d="M6.34902 5.5984C6.34902 3.21232 8.28331 1.27803 10.6694 1.27803V1.27803C11.8184 1.27316 12.922 1.72619 13.7362 2.53695C14.5504 3.3477 15.0081 4.44939 15.0081 5.5984V5.5984"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    </path>
                    <path d="M7.70365 10.1018H7.74942" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round"></path>
                    <path d="M13.5343 10.1018H13.5801" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                        stroke-linejoin="round"></path>
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


            <div class="hidenCatalog ">
                <nav class="main-nav">
                    <ul class="categories">
                        @foreach ($categoriesInCatalogMenu as $category)
                            @if ($category->subcategories->isNotEmpty())
                                <li class="category">
                                    <ul class="all-categories">
                                        <li class="category">
                                            <div class="hidenCatalog__cont">
                                                <div class="hidenCatalog__wrap">
                                                    <div class="hidenCatalog__infoWrap">
                                                        <div class="hidenCatalog__img-wrap">
                                                            <img src="{{ Storage::url($category->img) }}"
                                                                alt="{{ $category->titleh1 }}">
                                                        </div>
                                                        <a
                                                            href="{{ route('category.show', $category->slug) }}">{{ $category->titleh1 }}</a>
                                                    </div>
                                                    <div class="hidenCatalog__toggle">
                                                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <ul class="subcategories">
                                                @foreach ($category->subcategories as $subcategory)
                                                    @if ($subcategory->products->isNotEmpty())
                                                        <li class="subcategory">
                                                            <div class="hidenCatalog__cont">
                                                                <div class="hidenCatalog__wrap">
                                                                    <div class="hidenCatalog__infoWrap">
                                                                        <div class="hidenCatalog__img-wrap">
                                                                            <img src="{{ Storage::url($subcategory->img) }}"
                                                                                alt="{{ $subcategory->titleh1 }}">
                                                                        </div>
                                                                        <a
                                                                            href="{{ route('subcategory.show', ['category_slug' => $category->slug, 'subcategory_slug' => $subcategory->slug]) }}">
                                                                            {{ $subcategory->menu_title ?? $subcategory->titleh1 }}
                                                                        </a>
                                                                    </div>
                                                                    <div class="hidenCatalog__toggle">
                                                                        <i class="fas fa-chevron-right"
                                                                            aria-hidden="true"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <ul class="products">
                                                                @foreach ($subcategory->products as $product)
                                                                    <li class="product">
                                                                        <a
                                                                            href="{{ route('product.show', ['category_slug' => $category->slug, 'subcategory_slug' => $subcategory->slug, 'product_slug' => $product->slug]) }}/">{{ $product->h1 }}</a>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </nav>
            </div>

        </div>

        <ul class="header__mainMenuList">
            <li>
                <nav class="menu">
                    <ul class="menu__list">
                        @foreach ($categoriesInHeaderMenu as $category)
                            @if ($category->subcategories->isNotEmpty())
                                <li class="menu__item"><a class="menu__link"
                                        href="{{ route('category.show', $category->slug) }}">{{ $category->titleh1 }}</a><i
                                        class="fas fa-chevron-down"></i>
                                    <ul class="menu__sub-menu">
                                        @foreach ($category->subcategories as $subcategory)
                                            <li class="menu__item"><a class="menu__link"
                                                    href="{{ route('subcategory.show', ['category_slug' => $category->slug, 'subcategory_slug' => $subcategory->slug]) }}"> {{ $subcategory->menu_title ?? $subcategory->titleh1 }}</a><i
                                                    class="fas fa-chevron-down"></i>
                                                <ul class="menu__sub-menu">
                                                    @foreach ($subcategory->products as $product)
                                                        <li class="menu__item"><a class="menu__link"
                                                                href="{{ route('product.show', ['category_slug' => $category->slug, 'subcategory_slug' => $subcategory->slug, 'product_slug' => $product->slug]) }}/">{{ $product->h1 }}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </li>
                                        @endforeach


                                    </ul>
                                </li>
                            @endif
                        @endforeach


                    </ul>
                </nav>

            </li>
        </ul>
    </div>
</header>


<style>
    .filterSubcat {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
    }

    .filterSubcat::hover {
        color: #0989ff;
    }

    .filterSubcat.active {
        color: #0989ff;
    }

    .filterSubcat.active span {
        background: #0989ff;
        color: #fff;
    }


    /*  */

    .colorLabel {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        cursor: pointer;
    }

    .colorLabel .sidebarFilter__checkbox {
        display: none;
        /* Скрываем стандартный чекбокс */
    }

    .colorLabel .sidebarFilter__labelText {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 25px;
        height: 25px;
        /* border-radius: 50%; */
        margin-right: 10px;
        border: 2px solid #ccc;
        transition: border-color 0.3s ease;
    }

    .colorLabel .sidebarFilter__checkbox:checked+.sidebarFilter__labelText {
        border-color: #000;
        /* Изменяем стиль при активном чекбоксе */
    }

    .filterColors .sidebarFilter__paramsWrap {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-start;
    }

    .colorLabel__text {
        position: relative;

    }

    .colorArrow {
        font-size: 14px;
        font-weight: bold;
        /* filter: invert(1); */
    }

    .colorLabel.active .colorArrow {
        filter: invert(1);
    }

    .filterColors {
        margin-top: 35px;
    }

    /* .colorLabel__text:before{
        content: '✔';
        color: #fff;
        font-size: 10px;

    } */
    nav.main-nav ul.categories li.category ul.all-categories {
        position: relative;
    }

    .revCard__avatar img {
        max-width: 65px;
        height: 65px;
        width: 100%;
        object-fit: cover;
    }

    .bigProdCard__img-wrap img {
        height: 250px;
        object-fit: cover
    }

    .filterColors .sidebarFilter__paramsWrap, .filterMaterials .sidebarFilter__paramsWrap{

        height: 200px;
        overflow-y: scroll;
        overflow-x: hidden;
        /* width: 100%; */
        /* direction:rtl; */

    }
    .filterMaterials .sidebarFilter__paramsWrap{
                display: grid !important;
            grid-template-columns: repeat(5,1fr);
            grid-gap: 7px;
    }


    .filterMaterials .materialLabel{
        margin-bottom: 0 !important;
    }

    .pagination {
    display: -ms-flexbox;
    display: flex;
    padding-left: 0;
    list-style: none;
    border-radius: .25rem
}

.page-link {
    position: relative;
    display: block;
    padding: .5rem .75rem;
    margin-left: -1px;
    line-height: 1.25;
    color: #007bff;
    background-color: #fff;
    border: 1px solid #dee2e6
}

.page-link:hover {
    z-index: 2;
    color: #0056b3;
    text-decoration: none;
    background-color: #e9ecef;
    border-color: #dee2e6
}

.page-link:focus {
    z-index: 2;
    outline: 0;
    box-shadow: 0 0 0 .2rem #007bff40
}

.page-link:not(:disabled):not(.disabled) {
    cursor: pointer
}

.page-item:first-child .page-link {
    margin-left: 0;
    border-top-left-radius: .25rem;
    border-bottom-left-radius: .25rem
}

.page-item:last-child .page-link {
    border-top-right-radius: .25rem;
    border-bottom-right-radius: .25rem
}

.page-item.active .page-link {
    z-index: 1;
    color: #fff;
    background-color: #007bff;
    border-color: #007bff
}

.page-item.disabled .page-link {
    color: #6c757d;
    pointer-events: none;
    cursor: auto;
    background-color: #fff;
    border-color: #dee2e6
}

.pagination-lg .page-link {
    padding: .75rem 1.5rem;
    font-size: 1.25rem;
    line-height: 1.5
}

.pagination-lg .page-item:first-child .page-link {
    border-top-left-radius: .3rem;
    border-bottom-left-radius: .3rem
}

.pagination-lg .page-item:last-child .page-link {
    border-top-right-radius: .3rem;
    border-bottom-right-radius: .3rem
}

.pagination-sm .page-link {
    padding: .25rem .5rem;
    font-size: .875rem;
    line-height: 1.5
}

.pagination-sm .page-item:first-child .page-link {
    border-top-left-radius: .2rem;
    border-bottom-left-radius: .2rem
}

.pagination-sm .page-item:last-child .page-link {
    border-top-right-radius: .2rem;
    border-bottom-right-radius: .2rem
}
.bigProdCard__img-wrap{
    height: 250px;
}


    .bigProdCard__imgCustomWrap{
        position:relative;
        max-height: 250px;
        z-index: 1;
    }
    .bigProdCard__imgCustomWrap img:first-child{
        z-index:1;
        height: 100%;
        width: 100%;
        min-height: 250px;
        
        object-fit: cover;
    }
    .bigProdCard__imgCustomWrap img:nth-child(2){
        position:absolute;
        width:100%;
        height:100%;
        object-fit:cover;
        z-index:2;
        left: 0;
        opacity: 0;
        transition: 0.2s;
        min-height: 250px;
    }
    .bigProdCard__imgCustomWrap:hover img:nth-child(2){
        opacity: 1;
    }

    .bigProdCard__controls{
        z-index: 2;
    }

    /* для попапа фото ткани */
    .prodForm__imgWrap{
        position: relative;
    }
    .prodForm__imgWrap img:first-child{
        z-index:1;
        height: 100%;
        width: 100%;
        object-fit: cover; 
        /* 
        min-height: 250px;
        
        */
    }
    .prodForm__imgWrap img:nth-child(2){
        position:absolute;
        width:100%;
        height:100%;
        object-fit:cover;
        /* 
         */
        z-index:2;
        left: 0;
        opacity: 0;
        transition: 0.2s;
        /* min-height: 250px; */
    }
    .prodForm__imgWrap:hover img:nth-child(2){
        opacity: 1;
    }
    body {
        overflow-x: hidden
    }
    
/* .prodForm__bar a,.prodForm__bar img{display: none;} */
</style>
