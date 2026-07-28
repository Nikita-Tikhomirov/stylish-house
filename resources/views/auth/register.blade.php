{{-- @include('front.head') --}}
<x-front.head title="Зарегистрироваться"></x-front.head>

<body class="p-index">

    <x-front.header :categoriesInCatalogMenu="$categoriesInCatalogMenu" :categoriesInHeaderMenu="$categoriesInHeaderMenu" :headerInfo="$headerInfo" :cart="$cart"></x-front.header>
    @vite('resources/css/login.css')


    <main class="layout">
        <section class="s-login wrapper">
            <div class="s-login__title title">Зарегистрироваться </div>
            <div class="breadcrumbs">
                <ul class="breadcrumbs__list">
                    <li class="breadcrumbs__item"><a class="breadcrumbs__link" href="index.html">Главная</a></li>
                    <li class="breadcrumbs__item"><svg class="breadcrumbs__arrow" width="5" height="9"
                            viewBox="0 0 5 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#000"
                                d="M3.4575 4.5L2.97878 5.0625L5.28601e-05 8.2125L0.744734 9L5.00005 4.5L0.744733 3.72007e-07L5.2211e-05 0.7875L2.97878 3.9375L3.4575 4.5Z">
                            </path>
                        </svg><span class="breadcrumbs__active">Зарегистрироваться</span></li>
                </ul>
            </div>
            <div class="loginFormWfap">
                <div class="loginFormWfap__bgImages">
                    <img src="img/login-shape-1.png" alt="">
                    <img src="img/login-shape-2.png" alt="">
                    <img src="img/login-shape-3.png" alt="">
                    <img src="img/login-shape-4.png" alt="">
                </div>
                <div class="loginFormWfap__formWrap">
                    <div class="loginFormWfap__title">Зарегистрироваться</div>
                    <div class="loginFormWfap__description"> <span>Уже есть аккаунт? </span><a href="/login">Войти</a>
                    </div>
                    <div class="loginFormWfap__form">
                        <div class="formWrapper1" id="loginForm">

                            <form class="form" method="POST" action="{{ route('register') }}">
                                @csrf
                                <label class="form__label">
                                    <p>Имя</p>

                                    <input id="name" type="text"
                                        class="form__input @error('name') is-invalid @enderror" name="name"
                                        value="{{ old('name') }}" required autocomplete="name" autofocus>

                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </label>

                                <label class="form__label">
                                    <p>Email</p>

                                    <input id="email" type="email"
                                        class="form__input @error('email') is-invalid @enderror" name="email"
                                        value="{{ old('email') }}" required autocomplete="email">

                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </label>

                                <label class="form__label">
                                    <p>Пароль</p>
                                    <input id="password" type="password"
                                        class="form__input @error('password') is-invalid @enderror" name="password"
                                        required autocomplete="new-password">

                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </label>

                                <label class="form__label">
                                    <p>Повторите пароль</p>
                                    <input id="password-confirm" type="password" class="form__input"
                                        name="password_confirmation" required autocomplete="new-password">


                                </label>

                                <x-front.consent />

                                <button type="submit" class="contacts-btn btn">Зарегистрироваться</button>
                            </form>

                            <div class="ajaxMessage">
                                <div class="ajaxMessage__success">
                                    <div class="ajaxMessage__title">
                                        <p>Спасибо!</p>
                                        <p>Ваша заявка принята</p>
                                    </div>
                                    <div class="ajaxMessage__text">Мы свяжемся с вами в ближайшее время, чтобы обсудить
                                        детали и ответить на вопросы</div>
                                </div>
                                <div class="ajaxMessage__error">
                                    <div class="ajaxMessage__title">Ошибка при отправке!</div>
                                    <div class="ajaxMessage__text">Попробуйте позднее</div>
                                </div><button class="btn closeModal" type="button">Закрыть</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>


    <x-front.footer :headerInfo="$headerInfo" :curtainSubcats="$curtainSubcats" :blindSubcats="$blindSubcats"></x-front.footer>
    <x-front.popups></x-front.popups>

    @vite('resources/js/swiper.js')
    <script src="https://kit.fontawesome.com/9d3fa3c0db.js" crossorigin="anonymous"></script>




</body>

</html>


{{-- @extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Register') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="name" class="col-md-4 col-form-label text-md-end">{{ __('Name') }}</label>

                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }}</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Register') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection --}}
