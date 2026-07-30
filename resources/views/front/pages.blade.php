{{-- @include('front.head') --}}
<x-front.head title="{{$page->title}}" description="{{ $page->description }}"></x-front.head>

<body class="p-index">

    <x-front.header :cart="$cart" :headerInfo="$headerInfo"></x-front.header>

    <main class="layout">

        <style>
            .pageContent{
                padding-top: 50px;
                padding-bottom: 50px;
            }

            .pageContent .content{
                max-width: 1180px;
                font-size: 18px;
                line-height: 1.65;
                color: #111923;
            }

            .pageContent img{
                max-width: 100%;
                display: block;
                margin-bottom: 20px;
            }
            .pageContent h2{
                margin: 28px 0 16px;
                font-size: 28px;
                line-height: 1.25;
                font-weight: 700;
            }
            .pageContent p, .pageContent ul, .pageContent ol{
                margin-bottom: 18px;
            }
            .pageContent ul,
            .pageContent ol{
                display: grid;
                gap: 10px;
                max-width: 980px;
                margin-top: 12px;
                padding: 22px 24px 22px 34px;
                background: #f6f8fb;
                border: 1px solid #e5ebf2;
                border-radius: 8px;
                list-style-position: outside;
            }
            .pageContent ul{
                list-style: none;
                padding-left: 24px;
            }
            .pageContent ol{
                list-style: decimal;
                padding-left: 44px;
            }
            .pageContent li{
                position: relative;
                padding-left: 4px;
            }
            .pageContent ul li{
                padding-left: 22px;
            }
            .pageContent ul li::before{
                content: "";
                position: absolute;
                left: 0;
                top: 12px;
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #0989ff;
                box-shadow: 0 0 0 4px rgba(9, 137, 255, .12);
            }
            .pageContent li a{
                color: #0989ff;
                font-weight: 700;
                text-decoration: none;
            }
            .pageContent li a:hover{
                text-decoration: underline;
            }
            @media (max-width: 768px){
                .pageContent{
                    padding-top: 34px;
                    padding-bottom: 34px;
                }
                .pageContent .content{
                    font-size: 16px;
                    line-height: 1.55;
                }
                .pageContent h2{
                    font-size: 23px;
                }
                .pageContent ul,
                .pageContent ol{
                    padding-top: 18px;
                    padding-bottom: 18px;
                }
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
            <x-front.section.site-portfolio
                :workExamples="$portfolioWorkExamples"
                :workExampleGroups="$portfolioWorkExampleGroups"
                :videoReviews="$portfolioVideos"
            />
        @endif
    </section>

    @if ($page->slug === 'kontakty')
        <x-front.section.map></x-front.section.map>
    @endif

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
