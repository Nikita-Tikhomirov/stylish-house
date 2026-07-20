<?php

namespace Tests\Unit;

use App\Support\CartItemNormalizer;
use PHPUnit\Framework\TestCase;

class CartItemNormalizerTest extends TestCase
{
    public function test_it_preserves_a_complete_roller_shutter_configuration(): void
    {
        $normalizer = new CartItemNormalizer();

        $item = $normalizer->normalize([
            'productId' => 42,
            'width' => 900,
            'height' => 1200,
            'quantity' => 2,
            'price' => 25600,
            'configuration' => [
                'installation_type' => [
                    'label' => 'Тип монтажа',
                    'value' => 'Короб снаружи',
                    'code' => 'outside',
                    'price' => 0,
                ],
                'control_type' => [
                    'label' => 'Тип управления',
                    'value' => 'Автоматическое управление',
                    'code' => 'electric',
                    'price' => 7000,
                ],
                'lock_device' => [
                    'label' => 'Блокирующее устройство',
                    'value' => 'Ригельный замок',
                    'code' => 'rigel',
                    'price' => 1600,
                ],
                'additional_options' => [
                    [
                        'label' => 'Дополнительные опции',
                        'value' => 'Окраска в цвет RAL',
                        'code' => 'ral-paint',
                        'price' => 3500,
                    ],
                ],
            ],
        ], 'Рольворота RH77M антрацит');

        $this->assertSame(42, $item['productId']);
        $this->assertSame('Рольворота RH77M антрацит', $item['productName']);
        $this->assertSame(900, $item['width']);
        $this->assertSame(1200, $item['height']);
        $this->assertSame(2, $item['quantity']);
        $this->assertSame(25600, $item['price']);
        $this->assertSame('Автоматическое управление', $item['configuration']['control_type']['value']);
        $this->assertSame(7000, $item['configuration']['control_type']['price']);
        $this->assertSame('Окраска в цвет RAL', $item['configuration']['additional_options'][0]['value']);
    }

    public function test_configuration_changes_the_cart_key(): void
    {
        $normalizer = new CartItemNormalizer();
        $base = [
            'productId' => 42,
            'width' => 900,
            'height' => 1200,
            'quantity' => 1,
            'price' => 12800,
        ];

        $manual = $normalizer->normalize($base + [
            'configuration' => [
                'control_type' => ['label' => 'Тип управления', 'value' => 'ПИМ', 'code' => 'pim'],
            ],
        ], 'Рольворота');
        $electric = $normalizer->normalize($base + [
            'configuration' => [
                'control_type' => ['label' => 'Тип управления', 'value' => 'Электропривод', 'code' => 'electric'],
            ],
        ], 'Рольворота');

        $this->assertNotSame($normalizer->key($manual), $normalizer->key($electric));
    }

    public function test_it_converts_legacy_fields_to_readable_configuration(): void
    {
        $normalizer = new CartItemNormalizer();

        $item = $normalizer->normalize([
            'productId' => 7,
            'width' => '500',
            'height' => '700',
            'quantity' => '1',
            'price' => '9470',
            'side' => 'left',
            'widthType' => 'fabric',
            'controlColor' => 'white',
            'control' => true,
        ], 'Рулонная штора');

        $details = $normalizer->details($item);

        $this->assertSame('500 мм', $details['Ширина']);
        $this->assertSame('700 мм', $details['Высота']);
        $this->assertSame('Слева', $details['Сторона управления']);
        $this->assertSame('По ткани', $details['Тип ширины']);
        $this->assertSame('Белый', $details['Цвет управления']);
        $this->assertSame('Да', $details['Управление']);
    }

    public function test_it_limits_untrusted_configuration_values(): void
    {
        $normalizer = new CartItemNormalizer();

        $item = $normalizer->normalize([
            'productId' => 1,
            'configuration' => [
                'unexpected' => ['label' => str_repeat('x', 500), 'value' => str_repeat('y', 500)],
                'control_type' => [
                    'label' => '<script>alert(1)</script>Тип управления',
                    'value' => str_repeat('a', 500),
                    'code' => str_repeat('b', 500),
                    'price' => -100,
                ],
            ],
        ], 'Товар');

        $this->assertArrayNotHasKey('unexpected', $item['configuration']);
        $this->assertStringNotContainsString('<script>', $item['configuration']['control_type']['label']);
        $this->assertLessThanOrEqual(160, mb_strlen($item['configuration']['control_type']['value']));
        $this->assertSame(0, $item['configuration']['control_type']['price']);
    }
}
