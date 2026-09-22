<div class="s-seo wrapper">
    {!!$seoSection!!}
</div>

<style>
    .s-seo img {
        max-width: 100%;
        height: auto;
    }

    .s-seo p {
        margin: 0 0 14px;
    }

    .s-seo h2,
    .s-seo h3,
    .s-seo h4 {
        font-weight: 700;
        line-height: 1.25;
        margin: 24px 0 14px;
    }

    .s-seo h2 {
        font-size: 28px;
    }

    .s-seo h3 {
        font-size: 22px;
    }

    .s-seo h4 {
        font-size: 18px;
    }

    .s-seo ul,
    .s-seo ol {
        margin: 0 0 14px;
        padding-left: 24px;
    }

    .s-seo ul {
        list-style: disc;
    }

    .s-seo ol {
        list-style: decimal;
    }

    .s-seo li {
        margin-bottom: 6px;
    }

    .s-seo a {
        color: #0989ff;
        text-decoration: underline;
    }

    /* Таблицы: оформление в стиле сайта */
    .s-seo table {
        width: 100%;
        border-collapse: collapse;
        margin: 18px 0 24px;
        font-size: 15px;
        background: #fff;
    }

    .s-seo table th,
    .s-seo table td {
        border: 1px solid #e0e2e3;
        padding: 10px 14px;
        text-align: left;
        vertical-align: top;
        line-height: 1.4;
    }

    .s-seo table th {
        background: #f4f7f9;
        font-weight: 600;
    }

    .s-seo table tr:nth-child(even) td {
        background: #fafbfc;
    }

    /* Адаптив: на мобильных таблица прокручивается горизонтально, страница не ломается */
    @media only screen and (max-width: 767px) {
        .s-seo table {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 14px 0 20px;
        }

        .s-seo table th,
        .s-seo table td {
            white-space: nowrap;
            min-width: 130px;
        }
    }

    /* FAQ-аккордеон, вставленный прямо в SEO-текст (те же классы, что в блоке «Вопросы и ответы») */
    .s-seo .faq-block .faq-item {
        position: relative;
        z-index: 2;
        margin-bottom: 26px;
        transition: .2s;
        border: 1px solid #0989ff;
        border-radius: 5px;
        box-shadow: 5px 10px 6px #2c5b811a;
        background: #fff;
        padding: 8px 8px 8px 18px;
    }

    .s-seo .faq-block .faq-item .faq-question {
        position: relative;
        z-index: 3;
        padding-right: 10px;
        list-style: none;
        cursor: pointer;
    }

    .s-seo .faq-block .faq-item .faq-answer {
        padding-top: 10px;
    }

    .s-seo .faq-block .faq-item .faq-arrow {
        display: none;
    }

    .s-seo .faq-block .faq-item:before {
        content: "";
        display: block;
        width: 17px;
        height: 2px;
        position: absolute;
        top: 21px;
        right: 20px;
        background: #0989ff;
        z-index: -1;
    }

    .s-seo .faq-block .faq-item:after {
        content: "";
        display: block;
        width: 2px;
        height: 17px;
        position: absolute;
        top: 14px;
        right: 27px;
        background: #0989ff;
        -webkit-transition: .2s;
        -o-transition: .2s;
        transition: .2s;
        z-index: -1;
    }

    .s-seo .faq-block .faq-item .faq-question::marker {
        display: none;
    }
</style>
