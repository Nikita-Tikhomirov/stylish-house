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
            'prodTitle' => 'Мини 31',
            'cloth' => '1 категория',
            'width' => 1000,
            'height' => 1000,
        ]);

        $double = $calculator->calculate([
            'model' => 'Дерево, бамбук 25 мм',
            'modelId' => 68,
            'prodTitle' => 'Дабл люкс 31',
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

    public function test_wood_50_model_69_uses_the_301_series_tariff(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Дерево, бамбук 50 мм АБСОЛЮТ', [['A' => 'stub']]);

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 50 мм АБСОЛЮТ',
            'modelId' => 69,
            'prodTitle' => 'Дерево 50 мм 50KT-301 белый',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(13743, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_wood_50_model_71_uses_the_31_series_tariff(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Дерево, бамбук 50 мм АБСОЛЮТ', [['A' => 'stub']]);

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 50 мм АБСОЛЮТ',
            'modelId' => 71,
            'prodTitle' => 'Бамбук 50 мм 50KT-31 ванильный',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(12691, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_horizontal_code_1042_does_not_match_the_10_tariff(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Горизонтальные алюминиевые', [['A' => 'stub']]);

        $result = $calculator->calculate([
            'model' => 'Горизонтальные алюминиевые',
            'modelId' => 66,
            'prodTitle' => 'Алюминиевые 25 мм 25-1042',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(2843, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_wood_25_code_10_does_not_match_the_25_mm_model_size(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Дерево, бамбук 25 мм', [['A' => 'stub']]);

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 25 мм',
            'modelId' => 68,
            'prodTitle' => 'Дерево 25 мм 25-10 белый',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(9879, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_vertical_blinds_charge_at_least_one_square_metre(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Вертикальные', [
            13 => ['A' => 'Аврора', 'B' => 1867.14],
        ]);

        $result = $calculator->calculate([
            'model' => 'Вертикальные',
            'prodTitle' => 'Тканевые Аврора бежевый 101',
            'width' => 700,
            'height' => 700,
        ]);

        $this->assertSame(1867, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_vertical_blinds_round_billable_height_up_to_one_metre(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Вертикальные', [
            13 => ['A' => 'Аврора', 'B' => 1000],
        ]);

        $result = $calculator->calculate([
            'model' => 'Вертикальные',
            'prodTitle' => 'Тканевые Аврора бежевый 101',
            'width' => 2000,
            'height' => 500,
        ]);

        $this->assertSame(2000, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_vertical_blinds_normalize_yo_to_ye_when_matching_excel_names(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Вертикальные', [
            13 => ['A' => 'Кельн', 'B' => 1624.26],
        ]);

        $result = $calculator->calculate([
            'model' => 'Вертикальные',
            'prodTitle' => 'Тканевые Кёльн бежевый 2106',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(1624, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_vertical_blinds_accept_a_unique_excel_name_prefix(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Вертикальные', [
            20 => ['C' => 'Олимпик блэкаут', 'D' => 2610.96],
        ]);

        $result = $calculator->calculate([
            'model' => 'Вертикальные',
            'prodTitle' => 'Тканевые Олимпик блэкаут белый 2801',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(2611, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_vertical_blinds_accept_repeated_prefixes_with_the_same_tariff(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Вертикальные', [
            13 => ['E' => 'Палома (3001-3035)', 'F' => 2125.20],
            14 => ['E' => 'Палома (3048-3051)', 'F' => 2125.20],
        ]);

        $result = $calculator->calculate([
            'model' => 'Вертикальные',
            'prodTitle' => 'Тканевые Палома белый 3001',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(2125, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_vertical_blinds_reject_prefixes_with_conflicting_tariffs(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Вертикальные', [
            13 => ['E' => 'Палома (3001-3035)', 'F' => 2125.20],
            14 => ['E' => 'Палома (3048-3051)', 'F' => 2299.10],
        ]);

        $result = $calculator->calculate([
            'model' => 'Вертикальные',
            'prodTitle' => 'Тканевые Палома белый 3001',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_PRICE_NOT_FOUND, $result['error']);
    }

    public function test_vertical_blinds_resolve_the_evidenced_traffic_spelling_alias(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Вертикальные', [
            13 => ['A' => 'Траффик', 'B' => 3000],
        ]);

        $result = $calculator->calculate([
            'model' => 'Вертикальные',
            'prodTitle' => 'Пластиковые Трафик 690',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(3000, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_vertical_aluminium_blinds_match_a_unique_price_from_the_first_two_title_tokens(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Вертикальные', [
            31 => ['A' => 'Алюминиевые 89 мм', 'B' => 3491.40],
            34 => ['C' => 'Белый глянец', 'D' => 3491.40],
        ]);

        $result = $calculator->calculate([
            'model' => 'Вертикальные',
            'modelId' => 62,
            'prodTitle' => 'Алюминиевые белый глянец',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(3491, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_vertical_blinds_reject_conflicting_prices_across_title_tokens(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Вертикальные', [
            31 => ['A' => 'Алюминиевые 89 мм', 'B' => 3491.40],
            34 => ['C' => 'Белый глянец', 'D' => 4071.00],
        ]);

        $result = $calculator->calculate([
            'model' => 'Вертикальные',
            'modelId' => 62,
            'prodTitle' => 'Алюминиевые белый глянец',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_PRICE_NOT_FOUND, $result['error']);
    }

    public function test_horizontal_aluminium_blinds_charge_at_least_one_square_metre(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Горизонтальные алюминиевые', [['A' => 'stub']]);

        $result = $calculator->calculate([
            'model' => 'Горизонтальные алюминиевые',
            'modelId' => 66,
            'prodTitle' => 'Алюминиевые 25 мм 25-100',
            'width' => 500,
            'height' => 500,
        ]);

        $this->assertSame(1739, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_wood_blinds_charge_at_least_zero_point_eight_square_metres(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Дерево, бамбук 50 мм АБСОЛЮТ', [['A' => 'stub']]);

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 50 мм АБСОЛЮТ',
            'modelId' => 69,
            'prodTitle' => 'Дерево 50 мм 50KT-301 белый',
            'width' => 500,
            'height' => 500,
        ]);

        $this->assertSame(10995, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_generic_matrix_rounds_width_up_to_the_next_tenth_of_a_metre(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Тестовая модель', [
            1 => ['A' => 'Ткань'],
            2 => ['A' => null, 'B' => null, 'C' => 0.4, 'D' => 0.5],
            3 => ['A' => null, 'B' => 1.0, 'C' => 400, 'D' => 500],
        ]);

        $result = $calculator->calculate([
            'model' => 'Тестовая модель',
            'cloth' => 'Ткань',
            'prodTitle' => 'Рулонная штора',
            'width' => 440,
            'height' => 1000,
        ]);

        $this->assertSame(500, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_generic_matrix_rounds_height_up_to_the_next_tenth_of_a_metre(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Тестовая модель', [
            1 => ['A' => 'Ткань'],
            2 => ['A' => null, 'B' => null, 'C' => 1.0],
            3 => ['A' => null, 'B' => 1.0, 'C' => 1000],
            4 => ['A' => null, 'B' => 1.1, 'C' => 1100],
        ]);

        $result = $calculator->calculate([
            'model' => 'Тестовая модель',
            'cloth' => 'Ткань',
            'prodTitle' => 'Рулонная штора',
            'width' => 1000,
            'height' => 1040,
        ]);

        $this->assertSame(1100, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_generic_matrix_supports_columns_beyond_z(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Тестовая модель', [
            1 => ['Y' => 'Ткань'],
            2 => ['AA' => 0.5],
            3 => ['Z' => 1.0, 'AA' => 1234],
        ]);

        $result = $calculator->calculate([
            'model' => 'Тестовая модель',
            'cloth' => 'Ткань',
            'prodTitle' => 'Рулонная штора',
            'width' => 500,
            'height' => 1000,
        ]);

        $this->assertSame(1234, $result['price']);
        $this->assertNull($result['error']);
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
