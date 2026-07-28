<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="shop-authenticated" content="{{ auth()->check() ? '1' : '0' }}" />
    <title>{{ $title ?? 'Default Title' }}</title>
    <meta name="description" content="{{ $description ?? 'Default Title' }}" />

    @vite('resources/css/main.css')
    @vite('resources/css/front-components.css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    @vite('resources/css/index.css')
    @vite('resources/js/shop.js')


    <meta name="yandex-verification" content="a9f75680dfa64b76" />


    <!-- Yandex.Metrika counter -->
    <script>
       (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
       m[i].l=1*new Date();
       for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
       k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
       (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

       ym(100111431, "init", {
            clickmap:true,
            trackLinks:true,
            accurateTrackBounce:true,
            webvisor:true
       });
    </script>
    <!-- /Yandex.Metrika counter -->




    <meta name="google-site-verification" content="R8K0VCRa8jmJvEFbf47dGcxdWXau9dqD84x1yizvMX4" />



    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-L9J8HNEVXC"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-L9J8HNEVXC');
    </script>

  <link rel="icon" href="{{ Storage::url('logoKV3.png') }}?v=2" type="image/png">


</head>

