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
        $this->cacheWood25Sheet();

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

        $this->assertSame(10787, $base['price']);
        $this->assertSame(21574, $double['price']);
        $this->assertNull($double['error']);
    }

    public function test_does_not_apply_double_multiplier_when_price_not_found(): void
    {
        $calculator = new ProductMinPriceCalculator();
        $this->cacheWood25Sheet();

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

    public function test_wood_50_model_69_uses_the_301_series_tariff_with_minimum_area(): void
    {
        $calculator = new ProductMinPriceCalculator();
        $this->cacheWood50Sheet();

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

    public function test_wood_50_model_71_uses_the_31_series_tariff_with_minimum_area(): void
    {
        $calculator = new ProductMinPriceCalculator();
        $this->cacheWood50Sheet();

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 50 мм АБСОЛЮТ',
            'modelId' => 71,
            'prodTitle' => 'Бамбук 50 мм 50KT-31 ванильный',
            'width' => 500,
            'height' => 500,
        ]);

        $this->assertSame(10152, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_wood_50_model_69_uses_the_31_series_tariff_from_the_terminal_code(): void
    {
        $calculator = new ProductMinPriceCalculator();
        $this->cacheWood50Sheet();

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 50 мм АБСОЛЮТ',
            'modelId' => 69,
            'prodTitle' => 'Бамбук 50 мм 50KT-31 ванильный',
            'width' => 500,
            'height' => 500,
        ]);

        $this->assertSame(10152, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_wood_50_model_71_uses_the_301_series_tariff_from_the_terminal_code(): void
    {
        $calculator = new ProductMinPriceCalculator();
        $this->cacheWood50Sheet();

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 50 мм АБСОЛЮТ',
            'modelId' => 71,
            'prodTitle' => 'Дерево 50 мм 50KT-301 белый',
            'width' => 500,
            'height' => 500,
        ]);

        $this->assertSame(10995, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_wood_50_both_model_ids_use_the_201_series_tariff(): void
    {
        $calculator = new ProductMinPriceCalculator();
        $this->cacheWood50Sheet();

        foreach ([69, 71] as $modelId) {
            $result = $calculator->calculate([
                'model' => 'Дерево, бамбук 50 мм АБСОЛЮТ',
                'modelId' => $modelId,
                'prodTitle' => 'Бамбук 50 мм 50KT-201 натуральный',
                'width' => 500,
                'height' => 500,
            ]);

            $this->assertSame(10015, $result['price'], "Unexpected 201-series price for model {$modelId}");
            $this->assertNull($result['error']);
        }
    }

    public function test_wood_50_reads_the_unit_price_from_the_cached_excel_row(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Дерево, бамбук 50 мм АБСОЛЮТ', [
            12 => ['A' => 'Цвета', 'C' => 'Цена у.е./м²'],
            13 => [],
            14 => ['A' => 'Классик 50К-… 31, 32', 'C' => 20000.00],
        ]);

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 50 мм АБСОЛЮТ',
            'modelId' => 69,
            'prodTitle' => 'Дерево 50 мм 50KT-31 белый',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(20000, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_wood_50_treats_an_unusable_cached_sheet_as_unavailable(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Дерево, бамбук 50 мм АБСОЛЮТ', [['A' => 'stub']]);

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 50 мм АБСОЛЮТ',
            'modelId' => 69,
            'prodTitle' => 'Дерево 50 мм 50KT-31 белый',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND, $result['error']);
    }

    public function test_wood_50_does_not_match_a_longer_terminal_code_by_substring(): void
    {
        $calculator = new ProductMinPriceCalculator();
        $this->cacheWood50Sheet();

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 50 мм АБСОЛЮТ',
            'modelId' => 71,
            'prodTitle' => 'Дерево 50 мм 50KT-3010 белый',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_PRICE_NOT_FOUND, $result['error']);
    }

    public function test_wood_50_tariff_selection_does_not_depend_on_model_id(): void
    {
        $calculator = new ProductMinPriceCalculator();
        $this->cacheWood50Sheet();

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 50 мм АБСОЛЮТ',
            'modelId' => 0,
            'prodTitle' => 'Дерево 50 мм 50KT-31 белый',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(12690, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_wood_50_rejects_conflicting_cached_tariffs_as_an_unavailable_sheet(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Дерево, бамбук 50 мм АБСОЛЮТ', [
            12 => ['A' => 'Цвета', 'C' => 'Цена у.е./м²'],
            13 => [],
            14 => ['A' => 'Классик 50К-... 31, 32', 'C' => 12000],
            15 => ['A' => 'Модерн 50К-… 31, 51', 'C' => 13000],
        ]);

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 50 мм АБСОЛЮТ',
            'modelId' => 69,
            'prodTitle' => 'Дерево 50 мм 50KT-31 белый',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND, $result['error']);
    }

    public function test_wood_tariff_rejects_a_partially_malformed_row_inside_a_recognized_block(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Дерево, бамбук 50 мм АБСОЛЮТ', [
            12 => ['A' => 'Цвета', 'C' => 'Цена у.е./м²'],
            13 => [],
            14 => ['A' => 'Классик 50К-... 31, 32', 'C' => 12000],
            15 => ['A' => 'Модерн 50К-… 51, broken', 'C' => 13000],
        ]);

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 50 мм АБСОЛЮТ',
            'prodTitle' => 'Дерево 50 мм 50KT-31 белый',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND, $result['error']);
    }

    public function test_wood_tariff_rejects_a_non_array_cache_row(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Дерево, бамбук 50 мм АБСОЛЮТ', [
            12 => ['A' => 'Цвета', 'C' => 'Цена у.е./м²'],
            13 => 'corrupt row',
            14 => ['A' => 'Классик 50К-... 31, 32', 'C' => 12000],
        ]);

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 50 мм АБСОЛЮТ',
            'prodTitle' => 'Дерево 50 мм 50KT-31 белый',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND, $result['error']);
    }

    public function test_wood_25_reads_the_terminal_code_tariff_from_cache_independently_of_model_id(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Дерево, бамбук 25 мм', [
            13 => ['A' => 'Цвет', 'C' => 'Цена у.е./м²'],
            15 => ['A' => 'Дерево 25 мм NEW', 'B' => '25-… 31, 32', 'C' => 18000],
            22 => ['A' => 'Бамбук 25 мм', 'B' => '201, 202', 'C' => 22000],
        ]);

        foreach ([31 => 18000, 201 => 22000] as $code => $expectedPrice) {
            $result = $calculator->calculate([
                'model' => 'Дерево, бамбук 25 мм',
                'modelId' => 0,
                'prodTitle' => "Дерево 25 мм 25-{$code} проверка",
                'width' => 1000,
                'height' => 1000,
            ]);

            $this->assertSame($expectedPrice, $result['price']);
            $this->assertNull($result['error']);
        }
    }

    public function test_explicit_unknown_hyphen_code_does_not_fall_back_to_a_numeric_color_token(): void
    {
        $calculator = new ProductMinPriceCalculator();
        $this->cacheWood25Sheet();

        $result = $calculator->calculate([
            'model' => 'Дерево, бамбук 25 мм',
            'prodTitle' => 'Дерево 25 мм 25-999 цвет 31',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_PRICE_NOT_FOUND, $result['error']);
    }

    public function test_horizontal_code_1042_does_not_match_the_10_tariff(): void
    {
        $calculator = new ProductMinPriceCalculator();
        $this->cacheHorizontalSheet();

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
        $this->cacheWood25Sheet();

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

    public function test_vertical_blinds_prefer_the_longest_actual_excel_material_prefix(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Вертикальные', [
            20 => ['E' => 'Сиде', 'F' => 1624.26],
            21 => ['E' => 'Сиде блэкаут', 'F' => 2277.00],
        ]);

        $result = $calculator->calculate([
            'model' => 'Вертикальные',
            'prodTitle' => 'Тканевые Сиде блэкаут белый 3701',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(2277, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_vertical_blinds_use_a_unique_shorter_excel_prefix_for_line_blackout(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Вертикальные', [
            15 => ['C' => 'Лайн', 'D' => 1290.30],
        ]);

        $result = $calculator->calculate([
            'model' => 'Вертикальные',
            'prodTitle' => 'Тканевые Лайн блэкаут белый 3501',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(1290, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_vertical_blinds_match_the_actual_screen_percentage_label(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Вертикальные', [
            13 => ['G' => 'Скрин 5%', 'H' => 2990.46],
            14 => ['G' => 'Скрин 3%', 'H' => 3142.26],
            15 => ['G' => 'Скрин 1%', 'H' => 3294.06],
        ]);

        $result = $calculator->calculate([
            'model' => 'Вертикальные',
            'prodTitle' => 'Тканевые Скрин 3% Алю белый 5101',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(3142, $result['price']);
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

    public function test_vertical_aluminium_blinds_match_a_unique_price_from_the_title_remainder(): void
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

    public function test_vertical_aluminium_blinds_match_the_evidenced_perforated_metallic_alias(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Вертикальные', [
            33 => ['E' => 'Металлик глянец', 'F' => 3491.40],
            34 => ['E' => 'Металлик перфорированный', 'F' => 4071.00],
        ]);

        $result = $calculator->calculate([
            'model' => 'Вертикальные',
            'modelId' => 62,
            'prodTitle' => 'Алюминиевые металлик перфорация',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(4071, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_vertical_aluminium_blinds_map_the_plain_metallic_product_to_gloss(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Вертикальные', [
            33 => ['E' => 'Металлик глянец', 'F' => 3491.40],
            34 => ['E' => 'Металлик перфорированный', 'F' => 4071.00],
        ]);

        $result = $calculator->calculate([
            'model' => 'Вертикальные',
            'modelId' => 62,
            'prodTitle' => 'Алюминиевые металлик',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(3491, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_vertical_blinds_ignore_the_family_heading_when_a_color_tariff_matches(): void
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

        $this->assertSame(4071, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_horizontal_aluminium_blinds_charge_at_least_one_square_metre(): void
    {
        $calculator = new ProductMinPriceCalculator();
        $this->cacheHorizontalSheet();

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

    public function test_horizontal_tariff_uses_size_perforation_and_terminal_code_not_model_id(): void
    {
        $calculator = new ProductMinPriceCalculator();
        $this->cacheHorizontalSheet();

        $cases = [
            ['Алюминиевые 25 мм 25-100', 1739],
            ['Алюминиевые 25 мм 25-100 перф.', 2843],
            ['Алюминиевые 16 мм 16-100', 2939],
            ['Алюминиевые 25 мм 25-46_48', 1960],
        ];

        foreach ($cases as [$title, $expectedPrice]) {
            $result = $calculator->calculate([
                'model' => 'Горизонтальные алюминиевые',
                'modelId' => 0,
                'prodTitle' => $title,
                'width' => 1000,
                'height' => 1000,
            ]);

            $this->assertSame($expectedPrice, $result['price'], "Unexpected horizontal price for {$title}");
            $this->assertNull($result['error']);
        }
    }

    public function test_horizontal_tariff_reads_a_changed_unit_price_from_cache(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Горизонтальные алюминиевые', [
            13 => ['A' => 'Ширина ламели', 'B' => 'ЦВЕТ', 'D' => 'Цена у.е./1 м2'],
            14 => ['A' => '25 мм', 'B' => '100', 'D' => 2500],
        ]);

        $result = $calculator->calculate([
            'model' => 'Горизонтальные алюминиевые',
            'modelId' => 0,
            'prodTitle' => 'Алюминиевые 25 мм 25-100',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertSame(2500, $result['price']);
        $this->assertNull($result['error']);
    }

    public function test_horizontal_tariff_treats_an_unusable_cache_as_an_unavailable_sheet(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Горизонтальные алюминиевые', [['A' => 'stub']]);

        $result = $calculator->calculate([
            'model' => 'Горизонтальные алюминиевые',
            'modelId' => 66,
            'prodTitle' => 'Алюминиевые 25 мм 25-100',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND, $result['error']);
    }

    public function test_horizontal_tariff_rejects_conflicting_cached_keys(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Горизонтальные алюминиевые', [
            13 => ['A' => 'Ширина ламели', 'B' => 'ЦВЕТ', 'D' => 'Цена у.е./1 м2'],
            14 => ['A' => '25 мм', 'B' => '100', 'D' => 1700],
            15 => ['B' => '100, 130', 'D' => 1900],
        ]);

        $result = $calculator->calculate([
            'model' => 'Горизонтальные алюминиевые',
            'modelId' => 66,
            'prodTitle' => 'Алюминиевые 25 мм 25-100',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND, $result['error']);
    }

    public function test_horizontal_tariff_rejects_a_partial_row_inside_the_recognized_table(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Горизонтальные алюминиевые', [
            13 => ['A' => 'Ширина ламели', 'B' => 'ЦВЕТ', 'D' => 'Цена у.е./1 м2'],
            14 => ['A' => '25 мм', 'B' => '100', 'D' => 1700],
            15 => ['B' => '130, 23'],
        ]);

        $result = $calculator->calculate([
            'model' => 'Горизонтальные алюминиевые',
            'prodTitle' => 'Алюминиевые 25 мм 25-100',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND, $result['error']);
    }

    public function test_horizontal_tariff_rejects_a_first_row_without_a_size(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Горизонтальные алюминиевые', [
            13 => ['A' => 'Ширина ламели', 'B' => 'ЦВЕТ', 'D' => 'Цена у.е./1 м2'],
            14 => ['B' => '100', 'D' => 1738.8],
            19 => ['A' => '16 мм', 'B' => '21, 23', 'D' => 2939.4],
        ]);

        $result = $calculator->calculate([
            'model' => 'Горизонтальные алюминиевые',
            'prodTitle' => 'Алюминиевые 25 мм 25-100',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND, $result['error']);
    }

    public function test_horizontal_tariff_rejects_a_malformed_later_size_row(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Горизонтальные алюминиевые', [
            13 => ['A' => 'Ширина ламели', 'B' => 'ЦВЕТ', 'D' => 'Цена у.е./1 м2'],
            14 => ['A' => '25 мм', 'B' => '100', 'D' => 1738.8],
            19 => ['A' => '16mm', 'B' => '21, 23', 'D' => 2939.4],
        ]);

        $result = $calculator->calculate([
            'model' => 'Горизонтальные алюминиевые',
            'prodTitle' => 'Алюминиевые 25 мм 25-100',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND, $result['error']);
    }

    public function test_horizontal_tariff_rejects_a_non_array_cache_row(): void
    {
        $calculator = new ProductMinPriceCalculator();
        Cache::put('sheet_Горизонтальные алюминиевые', [
            13 => ['A' => 'Ширина ламели', 'B' => 'ЦВЕТ', 'D' => 'Цена у.е./1 м2'],
            14 => 'corrupt row',
            15 => ['A' => '25 мм', 'B' => '100', 'D' => 1700],
        ]);

        $result = $calculator->calculate([
            'model' => 'Горизонтальные алюминиевые',
            'prodTitle' => 'Алюминиевые 25 мм 25-100',
            'width' => 1000,
            'height' => 1000,
        ]);

        $this->assertNull($result['price']);
        $this->assertSame(ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND, $result['error']);
    }

    public function test_horizontal_valid_cache_keeps_absent_codes_as_price_not_found(): void
    {
        $calculator = new ProductMinPriceCalculator();
        $this->cacheHorizontalSheet();

        foreach (['16-18', '25-109', '25-2', '25-73', '25-901'] as $productCode) {
            $size = str_starts_with($productCode, '16-') ? 16 : 25;
            $result = $calculator->calculate([
                'model' => 'Горизонтальные алюминиевые',
                'modelId' => 0,
                'prodTitle' => "Алюминиевые {$size} мм {$productCode}",
                'width' => 1000,
                'height' => 1000,
            ]);

            $this->assertNull($result['price'], "Unexpected tariff for absent code {$productCode}");
            $this->assertSame(ProductMinPriceCalculator::ERROR_PRICE_NOT_FOUND, $result['error']);
        }
    }

    public function test_wood_blinds_charge_at_least_zero_point_eight_square_metres(): void
    {
        $calculator = new ProductMinPriceCalculator();
        $this->cacheWood50Sheet();

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

    private function cacheWood50Sheet(): void
    {
        Cache::put('sheet_Дерево, бамбук 50 мм АБСОЛЮТ', [
            12 => ['A' => 'Цвета', 'C' => 'Цена у.е./м²'],
            13 => [],
            14 => ['A' => 'Классик 50К-... 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44', 'C' => 12690.48],
            15 => ['A' => 'Модерн 50К-… 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63', 'C' => 12690.48],
            16 => ['A' => '10, 13, 15, 16, 20, 22, 23, 24', 'C' => 11241.48],
            17 => [],
            19 => ['A' => 'Цвета', 'C' => 'Цена у.е./м²'],
            20 => [],
            21 => [],
            22 => ['A' => 'Элегант 50К-… 301, 302, 303, 304, 305', 'C' => 13743.42],
            23 => ['A' => 'Элегант 50К-… 306, 307, 308, 309, 310', 'C' => 14378.22],
            24 => ['A' => '201, 202, 203, 204, 205, 206', 'C' => 12519.36],
            25 => [],
        ]);
    }

    private function cacheWood25Sheet(): void
    {
        Cache::put('sheet_Дерево, бамбук 25 мм', [
            12 => ['A' => 'Наименование', 'B' => 'Цвета', 'C' => 'Цена у.е./м²'],
            13 => ['C' => '25 мм'],
            14 => ['C' => 'веревочная лесенка'],
            15 => ['A' => 'Дерево 25 мм NEW', 'B' => '25-… 31, 32, 33, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 52, 53, 56, 58, 59, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71', 'C' => 10787.46],
            16 => ['A' => 'Дерево 25 мм', 'B' => '10, 13, 15, 16, 20, 22, 23, 24', 'C' => 9879.42],
            17 => [],
            19 => ['A' => 'Наименование', 'B' => 'Цвета', 'C' => 'Цена у.е./м²'],
            20 => ['C' => '25 мм'],
            21 => ['C' => 'веревочная лесенка'],
            22 => ['A' => 'Бамбук 25 мм', 'B' => '201, 202, 203, 204, 205, 206', 'C' => 11242.86],
            23 => [],
        ]);
    }

    private function cacheHorizontalSheet(): void
    {
        Cache::put('sheet_Горизонтальные алюминиевые', [
            13 => ['A' => 'Ширина ламели', 'B' => 'ЦВЕТ', 'D' => 'Цена у.е./1 м2'],
            14 => ['A' => '25 мм', 'B' => '100', 'D' => 1738.8],
            15 => ['B' => '10, 17, 159,19, 23, 27,292, 39, 40, 44, 46, 46/48, 48, 50, 56, 67, 84, 97, 104, 106, 130, 146, 163, 187, 188, 189, 330, 427, 497, 532, 608, 611, 7016, 730', 'D' => 1959.6],
            16 => ['B' => '1, 203, 207, 211, 1042', 'D' => 2842.8],
            17 => ['B' => 'перфорированные 100, 130, 23, 52, 56, 7016', 'D' => 2842.8],
            18 => ['B' => '772081,772082, 772083, 772085, 772091, 772093, 772095, 772098', 'D' => 3408.6],
            19 => ['A' => '16 мм', 'B' => '21, 23, 48, 56, 79, 90, 100, 187, 7016', 'D' => 2939.4],
            20 => ['B' => '772082, 772085, 772091, 772093, 772095, 772098', 'D' => 4650.6],
            21 => [],
            22 => ['A' => 'Типы крепления для мансардных окон ПВХ'],
        ]);
    }
}
