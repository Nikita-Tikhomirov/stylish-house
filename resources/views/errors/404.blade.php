<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Страница не найдена</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            color: #101828;
            background: #f5f8fb;
            font-family: Arial, sans-serif;
        }
        .errorPage {
            width: min(720px, 100%);
            padding: clamp(32px, 7vw, 72px);
            text-align: center;
            background: #fff;
            border: 1px solid #dce4ec;
            border-radius: 8px;
        }
        .errorPage__code {
            margin: 0 0 12px;
            color: #1089f5;
            font-size: clamp(56px, 12vw, 112px);
            font-weight: 700;
            line-height: 1;
        }
        h1 {
            margin: 0 0 18px;
            font-size: clamp(28px, 5vw, 44px);
            line-height: 1.15;
        }
        .errorPage__text {
            max-width: 520px;
            margin: 0 auto 28px;
            color: #52606d;
            font-size: 18px;
            line-height: 1.55;
        }
        .errorPage__link {
            display: inline-flex;
            min-height: 48px;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            color: #fff;
            background: #1089f5;
            border-radius: 4px;
            font-weight: 700;
            text-decoration: none;
        }
        .errorPage__link:focus-visible {
            outline: 3px solid #101828;
            outline-offset: 3px;
        }
    </style>
</head>
<body>
    <main class="errorPage">
        <p class="errorPage__code" aria-hidden="true">404</p>
        <h1>Страница не найдена</h1>
        <p class="errorPage__text">Возможно, адрес изменился или в ссылке допущена ошибка. Вернитесь на главную страницу и продолжите выбор.</p>
        <a class="errorPage__link" href="{{ \App\Support\CanonicalUrl::route('front.home') }}">Вернуться на главную</a>
    </main>
</body>
</html>
