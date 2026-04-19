<?php

namespace Tests\Unit;

use App\Services\ProductMinPriceCalculator;
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
}
