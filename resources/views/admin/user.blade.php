<x-front.head title="Личный кабинет" ></x-front.head>
@vite('resources/css/user.css')

<body class="p-cart">

    <x-front.header :categoriesInCatalogMenu="$categoriesInCatalogMenu" :categoriesInHeaderMenu="$categoriesInHeaderMenu" :headerInfo="$headerInfo" :cart="$cart"></x-front.header>

    <main class="layout">
        <section class="s-user wrapper">
            <h1 class="s-user__title title">Личный кабинет </h1>
            <div class="s-user__links"> <a href="#">Заказы</a><a href="#">Избранное</a><a
                    href="/cart">Корзина</a></div>
            <div class="loginFormWfap">
                <div class="loginFormWfap__bgImages">
                    <img src="img/login-shape-1.png" alt="">
                    <img src="img/login-shape-2.png" alt="">
                    <img src="img/login-shape-3.png" alt="">
                    <img src="img/login-shape-4.png" alt="">
                </div>
                <div class="loginFormWfap__tableWrap">
                    <div class="loginFormWfap__tableHeader">
                        <p>Id</p>
                        <p>Список товаров</p>
                        <p>Статус</p>
                    </div>

                    {{-- @foreach ($orders as $order)
                        <div class="loginFormWfap__tableItem">
                            <div class="loginFormWfap__orderId">{{ $order->id }}</div>
                            <div class="loginFormWfap__prods">
                                <div class="loginFormWfap__arrow">></div>
                                @foreach ($order->items as $item)
                                    <div class="loginFormWfap__prod">
                                        <span>{{ $item['name'] }}</span>
                                        <span>{{ $item['quantity'] }} шт.</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="loginFormWfap__orderStatus">{{ $order->status ?? 'На модерации' }}</div>
                        </div>
                    @endforeach --}}

                </div>
            </div>
        </section>


    </main>


    <x-front.footer :headerInfo="$headerInfo" :curtainSubcats="$curtainSubcats" :blindSubcats="$blindSubcats"></x-front.footer>
    <x-front.popups></x-front.popups>
    <style>
        .loginFormWfap__arrow{
            cursor: pointer;
        }
    </style>

    @vite('resources/js/main.js')
    {{-- @vite('resources/js/swiper.js') --}}
    <script src="https://kit.fontawesome.com/9d3fa3c0db.js" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const allArrows = document.querySelectorAll('.loginFormWfap__arrow')
            allArrows.forEach(element => {
                element.addEventListener('click', () => {
                    let parenttElement = element.parentElement
                    element.classList.toggle('active')
                    parenttElement.classList.toggle('active')
                })


            });
        })
    </script>
</body>

</html>
