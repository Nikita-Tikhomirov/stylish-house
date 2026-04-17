<div class="deliveryCard" data-id="{{ $id ?? '' }}">
    <div class="deliveryCard__iconWrap">
        <i class="{{ $icon_class ?? 'fas fa-truck' }}" aria-hidden="true"></i>
    </div>
    <div class="deliveryCard__info">
        <div class="deliveryCard__title">{{ $title ?? 'Title' }}</div>
        <div class="deliveryCard__text">{{ $text ?? 'Text' }}</div>
    </div>
    <button class="deliveryCards_delete btn btn-outline-secondary">Удалить</button>
</div>
