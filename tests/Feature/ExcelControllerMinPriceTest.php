<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ExcelControllerMinPriceTest extends TestCase
{
    public function test_sheet_names_endpoint_returns_double_price_for_double_titles(): void
    {
        Cache::put('sheet_Дерево, бамбук 25 мм', [
            12 => [
                'A' => 'Наименование',
                'B' => 'Цвета',
                'C' => 'Цена у.е./м²',
            ],
            13 => ['C' => '25 мм'],
            14 => ['C' => 'веревочная лесенка'],
            15 => [
                'A' => 'Дерево 25 мм NEW',
                'B' => '25-… 31, 32, 33, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 52, 53, 56, 58, 59, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71',
                'C' => 10787.46,
            ],
        ]);

        $baseResponse = $this->getJson('/sheet-names?model=' . urlencode('Дерево, бамбук 25 мм') . '&modelId=68&cloth=' . urlencode('1 категория') . '&width=1000&height=1000&prodTitle=' . urlencode('Мини 31'));
        $doubleResponse = $this->getJson('/sheet-names?model=' . urlencode('Дерево, бамбук 25 мм') . '&modelId=68&cloth=' . urlencode('1 категория') . '&width=1000&height=1000&prodTitle=' . urlencode('Дабл люкс 31'));

        $baseResponse->assertOk();
        $doubleResponse->assertOk();

        $basePrice = (int) $baseResponse->json('price');
        $doublePrice = (int) $doubleResponse->json('price');

        $this->assertSame(10787, $basePrice);
        $this->assertSame(21574, $doublePrice);
    }
}
