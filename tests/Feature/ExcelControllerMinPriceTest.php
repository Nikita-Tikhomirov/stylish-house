<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ExcelControllerMinPriceTest extends TestCase
{
    public function test_sheet_names_endpoint_returns_double_price_for_double_titles(): void
    {
        Cache::put('sheet_Дерево, бамбук 25 мм', [['A' => 'stub']]);

        $baseResponse = $this->getJson('/sheet-names?model=' . urlencode('Дерево, бамбук 25 мм') . '&modelId=68&cloth=' . urlencode('1 категория') . '&width=1000&height=1000&prodTitle=' . urlencode('Мини 25'));
        $doubleResponse = $this->getJson('/sheet-names?model=' . urlencode('Дерево, бамбук 25 мм') . '&modelId=68&cloth=' . urlencode('1 категория') . '&width=1000&height=1000&prodTitle=' . urlencode('Дабл люкс 25'));

        $baseResponse->assertOk();
        $doubleResponse->assertOk();

        $basePrice = (int) $baseResponse->json('price');
        $doublePrice = (int) $doubleResponse->json('price');

        $this->assertSame(10788, $basePrice);
        $this->assertSame(21576, $doublePrice);
    }
}
