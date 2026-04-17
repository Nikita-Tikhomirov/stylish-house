<div class="s-faq wrapper">
    <h2 class="s-faq__title title"> <span>{{ $title }}</span><svg width="114" height="35" viewBox="0 0 114 35"
            fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4"
                stroke-miterlimit="3.8637" stroke-linecap="round"></path>
        </svg></h2>

    @foreach ($faqs as $faq)
        <div class="accardionJs">
            <div class="accardion__title">{{ $faq->title }}</div>
            <div class="accardion__content">{{ $faq->text }}</div>
        </div>
    @endforeach





</div>
