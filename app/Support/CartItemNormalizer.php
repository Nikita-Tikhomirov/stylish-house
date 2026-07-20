<?php

namespace App\Support;

class CartItemNormalizer
{
    private const CONFIGURATION_KEYS = [
        'installation_type',
        'box_position',
        'control_type',
        'lock_type',
        'lock_device',
        'additional_options',
    ];

    public function normalize(array $input, string $productName): array
    {
        return [
            'productId' => max(0, (int) ($input['productId'] ?? 0)),
            'productName' => $this->cleanText($productName, 255),
            'width' => $this->nullableNumber($input['width'] ?? null),
            'height' => $this->nullableNumber($input['height'] ?? null),
            'control' => $this->nullableBoolean($input['control'] ?? null),
            'quantity' => max(1, min(99, (int) ($input['quantity'] ?? 1))),
            'price' => max(0, (int) round((float) ($input['price'] ?? 0))),
            'side' => $this->cleanNullable($input['side'] ?? null),
            'widthType' => $this->cleanNullable($input['widthType'] ?? null),
            'controlColor' => $this->cleanNullable($input['controlColor'] ?? null),
            'configuration' => $this->normalizeConfiguration(
                is_array($input['configuration'] ?? null) ? $input['configuration'] : []
            ),
        ];
    }

    public function key(array $item): string
    {
        return md5(json_encode([
            'productId' => $item['productId'] ?? null,
            'width' => $item['width'] ?? null,
            'height' => $item['height'] ?? null,
            'control' => $item['control'] ?? null,
            'side' => $item['side'] ?? null,
            'widthType' => $item['widthType'] ?? null,
            'controlColor' => $item['controlColor'] ?? null,
            'configuration' => $item['configuration'] ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function details(array $item): array
    {
        $details = [];

        if (($item['width'] ?? null) !== null) {
            $details['Ширина'] = $this->formatDimension($item['width']);
        }
        if (($item['height'] ?? null) !== null) {
            $details['Высота'] = $this->formatDimension($item['height']);
        }

        $legacy = [
            'side' => ['Сторона управления', ['left' => 'Слева', 'right' => 'Справа']],
            'widthType' => ['Тип ширины', ['fabric' => 'По ткани', 'overall' => 'По изделию']],
            'controlColor' => [
                'Цвет управления',
                [
                    'white' => 'Белый',
                    'black' => 'Черный',
                    'brown' => 'Коричневый',
                    'grey' => 'Серый',
                    'gray' => 'Серый',
                ],
            ],
        ];

        foreach ($legacy as $key => [$label, $values]) {
            $value = $item[$key] ?? null;
            if ($value !== null && $value !== '') {
                $details[$label] = $values[$value] ?? (string) $value;
            }
        }

        if (array_key_exists('control', $item) && $item['control'] !== null) {
            $details['Управление'] = $item['control'] ? 'Да' : 'Нет';
        }

        foreach (($item['configuration'] ?? []) as $key => $option) {
            if ($key === 'additional_options') {
                foreach ($option as $additionalOption) {
                    $details[$additionalOption['label']] = $this->optionDisplayValue($additionalOption);
                }
                continue;
            }

            $details[$option['label']] = $this->optionDisplayValue($option);
        }

        return $details;
    }

    private function normalizeConfiguration(array $configuration): array
    {
        $normalized = [];

        foreach (self::CONFIGURATION_KEYS as $key) {
            if (!array_key_exists($key, $configuration)) {
                continue;
            }

            if ($key === 'additional_options') {
                $options = is_array($configuration[$key]) ? $configuration[$key] : [];
                $normalized[$key] = array_values(array_filter(array_map(
                    fn ($option) => is_array($option) ? $this->normalizeOption($option) : null,
                    array_slice($options, 0, 20)
                )));
                continue;
            }

            if (is_array($configuration[$key])) {
                $normalized[$key] = $this->normalizeOption($configuration[$key]);
            }
        }

        return $normalized;
    }

    private function normalizeOption(array $option): array
    {
        return [
            'label' => $this->cleanText($option['label'] ?? 'Параметр', 100),
            'value' => $this->cleanText($option['value'] ?? '', 160),
            'code' => $this->cleanText($option['code'] ?? '', 80),
            'price' => max(0, (int) round((float) ($option['price'] ?? 0))),
        ];
    }

    private function optionDisplayValue(array $option): string
    {
        $value = $option['value'] ?? '';
        $price = (int) ($option['price'] ?? 0);

        return $price > 0 ? sprintf('%s (+%s ₽)', $value, number_format($price, 0, ',', ' ')) : $value;
    }

    private function nullableNumber(mixed $value): int|float|null
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return floor($number) === $number ? (int) $number : $number;
    }

    private function nullableBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    private function cleanNullable(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->cleanText($value, 160);
    }

    private function cleanText(mixed $value, int $limit): string
    {
        return mb_substr(trim(strip_tags((string) $value)), 0, $limit);
    }

    private function formatDimension(int|float $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') . ' мм';
    }
}
