@if($category->faq)
<section class="s-faq wrapper">
    <div class="s-faq__container">
        <div class="s-faq__title-wrap">
            <h2 class="s-faq__title title"> <span>Вопросы и ответы</span><svg width="114" height="35"
                    viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor"
                        stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
                </svg></h2>
        </div>
        <div class="customAccardeonWrap">
            {!! $category->faq !!}
        </div>
    </div>
</section>
@endif



<style>
    .customAccardeonWrap {
       
    }
     .customAccardeonWrap .faq-block .faq-item {
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

    .customAccardeonWrap .faq-block .faq-item .faq-question {
        position: relative;
        z-index: 3;
        padding-right: 10px;
        list-style: none;
        cursor: pointer;
    }

    .customAccardeonWrap .faq-block .faq-item .faq-answer {
        padding-top: 10px;
    }

    .customAccardeonWrap .faq-block .faq-item .faq-arrow {
        display: none;
    }

    .customAccardeonWrap .faq-block .faq-item:before {

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

    .customAccardeonWrap .faq-block .faq-item:after {
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

    .customAccardeonWrap .faq-block .faq-item .faq-question::marker {
        display: none;
    }
</style>