<div class="s-delivery wrapper">
    <h2 class="s-delivery__title title"> <span>{{$title}}</span><svg width="114" height="35"
            viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4"
                stroke-miterlimit="3.8637" stroke-linecap="round"></path>
        </svg></h2>
    <div class="s-delivery__text">{{$topText}}</div>
    <div class="s-delivery__cards">
        @foreach ($iconCards as $iconCard)
            <div class="deliveryCard">
                <div class="deliveryCard__iconWrap">{!! $iconCard->icon_class !!}</div>
                <div class="deliveryCard__info">
                    <div class="deliveryCard__title">{{ $iconCard->title }}</div>
                    <div class="deliveryCard__text">{{ $iconCard->text }}</div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="s-delivery__text">{{$bottomText}}</div>
</div>
