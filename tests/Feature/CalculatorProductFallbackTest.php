<?php

namespace Tests\Feature;

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubcategoryController;
use App\Models\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CalculatorProductFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('products');
        Schema::dropIfExists('prod_model');

        Schema::create('prod_model', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('subcategory_id');
            $table->unsignedBigInteger('model_id');
            $table->string('title');
            $table->string('show_in_catalog')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('prod_model');

        parent::tearDown();
    }

    public function test_subcategory_fallback_selects_first_visible_product_with_model_contract(): void
    {
        $this->seedCalculatorProducts();

        $product = (new TestableSubcategoryController())->calculatorProduct(null, 7);

        $this->assertSame(20, $product?->id);
        $this->assertSame(2, $product?->model_id);
        $this->assertSame('Visible first', $product?->model_title);
    }

    public function test_category_fallback_selects_first_visible_product_with_model_contract(): void
    {
        $this->seedCalculatorProducts();

        $product = (new TestableCategoryController())->calculatorProduct(null, 3);

        $this->assertSame(20, $product?->id);
        $this->assertSame(2, $product?->model_id);
        $this->assertSame('Visible first', $product?->model_title);
    }

    public function test_configured_orphan_falls_back_to_scoped_product_with_model_contract(): void
    {
        $this->seedCalculatorProducts();

        $subcategoryProduct = (new TestableSubcategoryController())->calculatorProduct(15, 7);
        $categoryProduct = (new TestableCategoryController())->calculatorProduct(15, 3);

        $this->assertSame(20, $subcategoryProduct?->id);
        $this->assertSame('Visible first', $subcategoryProduct?->model_title);
        $this->assertSame(20, $categoryProduct?->id);
        $this->assertSame('Visible first', $categoryProduct?->model_title);
    }

    private function seedCalculatorProducts(): void
    {
        $timestamp = now();

        DB::table('prod_model')->insert([
            ['id' => 1, 'title' => 'Hidden', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 2, 'title' => 'Visible first', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 3, 'title' => 'Visible second', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 4, 'title' => 'Other scope', 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ]);

        DB::table('products')->insert([
            ['id' => 5, 'category_id' => 99, 'subcategory_id' => 99, 'model_id' => 4, 'title' => 'Other scope', 'show_in_catalog' => '1', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 10, 'category_id' => 3, 'subcategory_id' => 7, 'model_id' => 1, 'title' => 'Hidden', 'show_in_catalog' => '0', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 15, 'category_id' => 3, 'subcategory_id' => 7, 'model_id' => 999, 'title' => 'Orphan model', 'show_in_catalog' => '1', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 20, 'category_id' => 3, 'subcategory_id' => 7, 'model_id' => 2, 'title' => 'Visible first', 'show_in_catalog' => '1', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 30, 'category_id' => 3, 'subcategory_id' => 7, 'model_id' => 3, 'title' => 'Visible second', 'show_in_catalog' => '1', 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ]);
    }
}

class TestableSubcategoryController extends SubcategoryController
{
    public function calculatorProduct(?int $configuredProductId, int $subcategoryId): ?Product
    {
        return $this->resolveCalculatorProduct($configuredProductId, $subcategoryId);
    }
}

class TestableCategoryController extends CategoryController
{
    public function calculatorProduct(?int $configuredProductId, int $categoryId): ?Product
    {
        return $this->resolveCalculatorProduct($configuredProductId, $categoryId);
    }
}
