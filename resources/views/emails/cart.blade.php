<p><strong>Имя:</strong> {{ $name }}</p>
<p><strong>Фамилия:</strong> {{ $secondname }}</p>
<p><strong>Телефон:</strong> {{ $phone }}</p>
<p><strong>Адрес:</strong> {{ $addres }}</p>
<p><strong>Email:</strong> {{ $email }}</p>
<p><strong>Примечания к заказу:</strong> {{ $comment }}</p>
<p>
    <strong>
        Доставка:
    </strong>
    @if ($delivery == 0)
        Самовывоз
    @elseif ($delivery == 700)
        В пределах МКАД
    @elseif ($delivery == 'delivery')
        За пределы МКАД (предварительный расчет)
    @else
        Не выбрано
    @endif
</p>
<h2>Состав заказа:</h2>
<table border="1">
    <thead>
        <tr>
            <th>Название товара</th>
            <th>Ширина</th>
            <th>Высота</th>
            <th>Количество</th>
            <th>Цена</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach ($cart as $item)
            <tr>
                <td>{{ $item['productName'] }}</td>
                <td>{{ $item['width'] }}</td>
                <td>{{ $item['height'] }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ $item['price'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<p><strong>Общая стоимость заказа:</strong> {{ $totalPrice }}</p>