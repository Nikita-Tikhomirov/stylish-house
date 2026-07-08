{{-- @include('front.head') --}}
<x-front.head title="{{$page->title}}" description="{{ $page->description }}"></x-front.head>

<body class="p-index">

    <x-front.header :categoriesInCatalogMenu="$categoriesInCatalogMenu" :categoriesInHeaderMenu="$categoriesInHeaderMenu" :cart="$cart" :headerInfo="$headerInfo"></x-front.header>

    <main class="layout">

        <style>
            .pageContent{
                padding-top: 50px;
                padding-bottom: 50px;
            }
            
            .pageContent h1{ 

            }
            .pageContent img{
                max-width: 100%;
                display: block;
                margin-bottom: 20px;
            }
            .pageContent h2{
                margin-bottom: 20px;
            }
            .pageContent p, .pageContent ul, .pageContent ol{
                margin-bottom: 10px;
            }
        </style>

    <section class="pageContent wrapper">
        {{-- <h1>{{$page->h1}}</h1> --}}

        <h1 class="s-delivery__title title"> <span>{{$page->h1}}</span><svg width="114" height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
        </svg></h1>
        <div class="content">
            {!! $page->content !!}
        </div>
        @if ($page->slug === 'rasschitat')
            <x-front.section.site-calculator :categories="$calculatorCategories" />
        @endif
        @if ($page->slug === 'portfolio')
            <x-front.section.site-portfolio :workExamples="$portfolioWorkExamples" :videoReviews="$portfolioVideos" />
        @endif
    </section>

    <x-front.section.how :title="$homePageFields->section_request_title"
        :subtitle="$homePageFields->section_request_subtitle"
        :text="$homePageFields->section_request_text"></x-front.section.how>

    </main>



     <x-front.footer :headerInfo="$headerInfo" :curtainSubcats="$curtainSubcats" :blindSubcats="$blindSubcats"></x-front.footer>
    <x-front.popups></x-front.popups>

    @vite('resources/js/main.js')
    @vite('resources/js/swiper.js')
    <script src="https://kit.fontawesome.com/9d3fa3c0db.js" crossorigin="anonymous"></script>




</body>

</html>
