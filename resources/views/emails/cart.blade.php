@php
    $customer = $order->customer_details ?: [
        'name' => $order->user->name ?? '',
        'secondname' => $order->user->secondname ?? '',
        'phone' => $order->user->phone ?? '',
        'addres' => $order->user->addres ?? '',
        'email' => $order->user->email ?? '',
    ];
    $normalizer = app(\App\Support\CartItemNormalizer::class);
@endphp

<h1 style="font-size: 22px;">{{ $adminCopy ? 'Новый заказ' : 'Заказ принят' }} №{{ $order->id }}</h1>
<p><strong>Имя:</strong> {{ trim(($customer['name'] ?? '') . ' ' . ($customer['secondname'] ?? '')) }}</p>
<p><strong>Телефон:</strong> {{ $customer['phone'] ?? 'Не указан' }}</p>
<p><strong>Адрес:</strong> {{ $customer['addres'] ?? 'Не указан' }}</p>
<p><strong>Email:</strong> {{ $customer['email'] ?? 'Не указан' }}</p>
<p><strong>Доставка:</strong> {{ $order->delivery_label }}</p>
@if ((float) $order->delivery_cost > 0)
    <p><strong>Стоимость доставки:</strong> {{ number_format($order->delivery_cost, 0, ',', ' ') }} ₽</p>
@endif
<p><strong>Комментарий:</strong> {{ $order->comment ?: 'Нет' }}</p>

<h2 style="font-size: 18px;">Состав заказа</h2>
@foreach ($order->normalized_items as $item)
    <table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; margin-bottom: 16px;">
        <tr>
            <th colspan="2" align="left">{{ $item['productName'] ?? 'Товар' }}</th>
        </tr>
        @foreach ($normalizer->details($item) as $label => $value)
            <tr>
                <td><strong>{{ $label }}</strong></td>
                <td>{{ $value }}</td>
            </tr>
        @endforeach
        <tr>
            <td><strong>Количество</strong></td>
            <td>{{ $item['quantity'] ?? 1 }}</td>
        </tr>
        <tr>
            <td><strong>Стоимость позиции</strong></td>
            <td>{{ number_format($item['price'] ?? 0, 0, ',', ' ') }} ₽</td>
        </tr>
    </table>
@endforeach

<p style="font-size: 18px;"><strong>Итого: {{ number_format($order->total_price, 0, ',', ' ') }} ₽</strong></p>
