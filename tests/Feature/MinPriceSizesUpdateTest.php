<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MinPriceSizesUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('titleh1')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });

        Schema::create('subcategories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title')->nullable();
            $table->string('titleh1')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });

        Schema::create('prod_model', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('title')->nullable();
            $table->string('h1')->nullable();
            $table->string('slug')->nullable();
            $table->integer('min_width')->nullable();
            $table->integer('min_height')->nullable();
            $table->unsignedInteger('min_price')->nullable();
            $table->string('min_price_error')->nullable();
            $table->timestamps();
        });

        Schema::create('price_recalc_runs', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('running');
            $table->string('mode')->default('manual');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->json('model_ids')->nullable();
            $table->unsignedInteger('batch_size')->default(200);
            $table->unsignedBigInteger('start_id')->nullable();
            $table->unsignedBigInteger('end_id')->nullable();
            $table->unsignedBigInteger('current_id')->default(0);
            $table->boolean('skip_filled')->default(true);
            $table->boolean('overwrite_existing')->default(false);
            $table->unsignedBigInteger('last_product_id')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedInteger('total_candidates')->nullable();
            $table->unsignedDecimal('progress_percent', 5, 2)->nullable();
            $table->unsignedInteger('eta_seconds')->nullable();
            $table->string('stop_reason')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('price_recalc_run_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('run_id');
            $table->unsignedBigInteger('product_id');
            $table->string('status');
            $table->unsignedInteger('old_min_price')->nullable();
            $table->unsignedInteger('new_min_price')->nullable();
            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('processed_at');
            $table->timestamps();
        });

        $this->seedFiltersData();
        $this->withoutMiddleware();
    }

    public function test_preview_returns_count_by_combined_filters(): void
    {
        Product::query()->insert([
            $this->productRow(101, 1, 10, 100, 400, 500),
            $this->productRow(102, 1, 10, 100, 400, 500),
            $this->productRow(103, 1, 10, 101, 400, 500),
            $this->productRow(104, 1, 11, 100, 400, 500),
            $this->productRow(105, 2, 20, 100, 400, 500),
        ]);

        $response = $this->getJson('/admin/prices/min/sizes/preview?category_id=1&subcategory_id=10&model_ids[]=100&start_id=100&end_id=102');

        $response->assertOk();
        $response->assertJson([
            'matched' => 2,
        ]);
    }

    public function test_apply_overwrite_updates_all_matched_and_keeps_min_price_fields(): void
    {
        Product::query()->insert([
            $this->productRow(201, 1, 10, 100, 300, 400, 1111, 'old_error'),
            $this->productRow(202, 1, 10, 100, 320, 420, 2222, null),
            $this->productRow(203, 1, 10, 101, 330, 430, 3333, null),
        ]);

        $response = $this->postJson('/admin/prices/min/sizes/update', [
            'category_id' => 1,
            'subcategory_id' => 10,
            'model_ids' => [100],
            'start_id' => 201,
            'end_id' => 202,
            'write_mode' => 'overwrite',
            'min_width' => 450,
            'min_height' => 600,
        ]);

        $response->assertOk();
        $response->assertJson([
            'matched' => 2,
            'updated' => 2,
            'skipped' => 0,
            'range' => [
                'from' => 201,
                'to' => 202,
            ],
        ]);

        $p201 = Product::query()->findOrFail(201);
        $p202 = Product::query()->findOrFail(202);
        $p203 = Product::query()->findOrFail(203);

        $this->assertSame(450, $p201->min_width);
        $this->assertSame(600, $p201->min_height);
        $this->assertSame(1111, $p201->min_price);
        $this->assertSame('old_error', $p201->min_price_error);

        $this->assertSame(450, $p202->min_width);
        $this->assertSame(600, $p202->min_height);
        $this->assertSame(2222, $p202->min_price);
        $this->assertNull($p202->min_price_error);

        $this->assertSame(330, $p203->min_width);
        $this->assertSame(430, $p203->min_height);
    }

    public function test_apply_skip_filled_updates_only_empty_and_normalizes_reversed_range(): void
    {
        Product::query()->insert([
            $this->productRow(301, 1, 10, 100, null, null),
            $this->productRow(302, 1, 10, 100, 0, 0),
            $this->productRow(303, 1, 10, 100, 700, 800),
            $this->productRow(304, 1, 10, 100, null, 900),
        ]);

        $response = $this->postJson('/admin/prices/min/sizes/update', [
            'category_id' => 1,
            'subcategory_id' => 10,
            'model_ids' => [100],
            'start_id' => 304,
            'end_id' => 301,
            'write_mode' => 'skip_filled',
            'min_width' => 500,
            'min_height' => 650,
        ]);

        $response->assertOk();
        $response->assertJson([
            'matched' => 4,
            'updated' => 3,
            'skipped' => 1,
            'range' => [
                'from' => 301,
                'to' => 304,
            ],
        ]);

        $p301 = Product::query()->findOrFail(301);
        $p302 = Product::query()->findOrFail(302);
        $p303 = Product::query()->findOrFail(303);
        $p304 = Product::query()->findOrFail(304);

        $this->assertSame(500, $p301->min_width);
        $this->assertSame(650, $p301->min_height);
        $this->assertSame(500, $p302->min_width);
        $this->assertSame(650, $p302->min_height);
        $this->assertSame(700, $p303->min_width);
        $this->assertSame(800, $p303->min_height);
        $this->assertSame(500, $p304->min_width);
        $this->assertSame(900, $p304->min_height);
    }

    private function seedFiltersData(): void
    {
        DB::table('categories')->insert([
            ['id' => 1, 'title' => 'Cat 1', 'titleh1' => 'Cat 1', 'slug' => 'cat-1'],
            ['id' => 2, 'title' => 'Cat 2', 'titleh1' => 'Cat 2', 'slug' => 'cat-2'],
        ]);

        DB::table('subcategories')->insert([
            ['id' => 10, 'category_id' => 1, 'title' => 'Sub 10', 'titleh1' => 'Sub 10', 'slug' => 'sub-10'],
            ['id' => 11, 'category_id' => 1, 'title' => 'Sub 11', 'titleh1' => 'Sub 11', 'slug' => 'sub-11'],
            ['id' => 20, 'category_id' => 2, 'title' => 'Sub 20', 'titleh1' => 'Sub 20', 'slug' => 'sub-20'],
        ]);

        DB::table('prod_model')->insert([
            ['id' => 100, 'title' => 'Model A'],
            ['id' => 101, 'title' => 'Model B'],
        ]);
    }

    private function productRow(
        int $id,
        int $categoryId,
        int $subcategoryId,
        int $modelId,
        ?int $minWidth,
        ?int $minHeight,
        ?int $minPrice = null,
        ?string $minPriceError = null
    ): array {
        return [
            'id' => $id,
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'model_id' => $modelId,
            'title' => 'Product ' . $id,
            'h1' => 'Product ' . $id,
            'slug' => 'product-' . $id,
            'min_width' => $minWidth,
            'min_height' => $minHeight,
            'min_price' => $minPrice,
            'min_price_error' => $minPriceError,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
