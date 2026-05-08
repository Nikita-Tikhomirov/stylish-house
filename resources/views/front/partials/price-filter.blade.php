@php
    $priceFilterMax = max(0, (int) ($maxFilterPrice ?? 0));
@endphp

@if ($priceFilterMax > 0)
    <style>
        .price-filter {
            width: 100%;
            max-width: 400px;
            margin: 20px auto;
            text-align: center;
        }

        .custom-range-slider {
            position: relative;
            width: 100%;
            height: 40px;
            margin: 20px 0;
        }

        .custom-range-slider .track {
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 6px;
            background-color: #ddd;
            border-radius: 3px;
            transform: translateY(-50%);
        }

        .custom-range-slider .range {
            position: absolute;
            top: 50%;
            height: 6px;
            background-color: #007bff;
            border-radius: 3px;
            transform: translateY(-50%);
            z-index: 1;
        }

        .custom-range-slider .thumb {
            position: absolute;
            top: 50%;
            width: 20px;
            height: 20px;
            background-color: #007bff;
            border: 2px solid #fff;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transform: translate(-50%, -50%);
            cursor: pointer;
            z-index: 2;
        }

        .price-values {
            font-size: 16px;
        }
    </style>

    <div class="price-filter" data-price-filter data-default-min="0" data-default-max="{{ $priceFilterMax }}">
        <div class="sidebarFilter__priceTitle">Цена</div>
        <div class="custom-range-slider">
            <div class="track"></div>
            <div class="range"></div>
            <div class="thumb left-thumb"></div>
            <div class="thumb right-thumb"></div>
        </div>
        <div class="price-values">
            <span>От </span>
            <span id="min-price-display">0</span> ₽ -
            <span> До</span>
            <span id="max-price-display">{{ number_format($priceFilterMax, 0, '', ' ') }}</span> ₽
        </div>
        <input id="min-price" type="hidden" value="0">
        <input id="max-price" type="hidden" value="{{ $priceFilterMax }}">
        <input id="price-filter-active" type="hidden" value="0">
    </div>
@endif
