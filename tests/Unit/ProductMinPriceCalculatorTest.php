<?php

namespace Tests\Unit;

use App\Services\ProductMinPriceCalculator;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductMinPriceCalculatorTest extends TestCase
{
    public function test_returns_invalid_dimensions_error_when_dimensions_missing(): void
    {
        $calculator = new ProductMinPriceCalculator();

        $result = $calculator->calculate([
            'model' => 'Any Model',
            'cloth' => '1 категория',
            'width' => 0,
            'height' => 500,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_INVALID_DIMENSIONS, $result['error']);
    }

    public function test_returns_sheet_not_found_when_cache_sheet_absent(): void
    {
        $calculator = new ProductMinPriceCalculator();

        $result = $calculator->calculate([
            'model' => 'Unknown sheet',
            'cloth' => '1 категория',
            'width' => 500,
            'height' => 500,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND, $result['error']);
    }

    public function test_applies_double_multiplier_when_product_title_contains_double_keyword(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Дерево, бамбук 25 мм', [['A' => 'stub']]);

        $base = $calculator->calculate([
            'model' => 'Дерево, бамбук 25 мм',
            'modelId' => 68,
            'prodTitle' => 'Мини 25',
            'cloth' => '1 категория',
            'width' => 1000,
            'height' => 1000,
        ]);

        $double = $calculator->calculate([
            'model' => 'Дерево, бамбук 25 мм',
            'modelId' => 68,
            'prodTitle' => 'Дабл люкс 25',
            'cloth' => '1 категория',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(10788, $base['price']);
        $this->assertSame(21576, $double['price']);
        $this->assertNull($double['error']);
    }

    public function test_does_not_apply_double_multiplier_when_price_not_found(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Дерево, бамбук 25 мм', [['A' => 'stub']]);

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 25 мм',
            'modelId' => 68,
            'prodTitle' => 'Дабл люкс',
            'cloth' => '1 категория',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_PRICE_NOT_FOUND, $result['error']);
    }

    public function test_rollets_matrix_uses_next_available_price_when_nearest_cell_is_empty(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put(ProductMinPriceCalculator::ROLLETS_OPENING_CACHE_KEY, [
            'RH58N' => [
                'widths' => [500, 1000],
                'heights' => [500],
                'prices' => [
                    500 => [
                        500 => null,
                        1000 => 14963,
                    ],
                ],
            ],
        ]);

        $result = $calculator->calculate([
            'model' => 'RH58N',
            'prodTitle' => 'Рольворота RH58N антрацит',
            'width' => 400,
            'height' => 500,
        ]);

        $this->assertSame(14963, $result['price']);
        $this->assertNull($result['error']);
    }
}
