<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Цвета изображений</title>
    <style>
        .color-box {
            width: 100px;
            height: 100px;
            display: inline-block;
            margin: 5px;
            text-align: center;
            line-height: 100px;
            font-size: 14px;
            color: #fff;
        }
    </style>
</head>
<body>
    <h1>Цвета изображений</h1>
    @foreach ($colors as $hex => $name)
        <div class="color-box" style="background-color: {{ $hex }};">
            {{ $name }}
        </div>
    @endforeach
</body>
</html>
