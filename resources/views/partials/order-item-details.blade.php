@php
    $orderItemDetails = app(\App\Support\CartItemNormalizer::class)->details($item);
@endphp

<div class="order-item-details">
    <strong class="order-item-details__title">{{ $item['productName'] ?? 'Товар' }}</strong>
    <dl>
        @foreach ($orderItemDetails as $label => $value)
            <div>
                <dt>{{ $label }}</dt>
                <dd>{{ $value }}</dd>
            </div>
        @endforeach
        <div>
            <dt>Количество</dt>
            <dd>{{ $item['quantity'] ?? 1 }}</dd>
        </div>
        <div>
            <dt>Стоимость позиции</dt>
            <dd>{{ number_format($item['price'] ?? 0, 0, ',', ' ') }} ₽</dd>
        </div>
    </dl>
</div>
