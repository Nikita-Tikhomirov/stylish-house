<?php

namespace Tests\Feature;

use App\Models\PriceRecalcRun;
use App\Models\PriceRecalcRunItem;
use App\Models\Product;
use App\Services\MinPriceRecalcService;
use App\Services\ProductMinPriceCalculator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MinPriceRecalcServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (extension_loaded('pdo_sqlite')) {
            config()->set('database.default', 'sqlite');
            config()->set('database.connections.sqlite.database', ':memory:');
            DB::purge('sqlite');
        } else {
            Schema::dropIfExists('price_recalc_run_items');
            Schema::dropIfExists('price_recalc_runs');
            Schema::dropIfExists('products');
            Schema::dropIfExists('prod_model');
            Schema::dropIfExists('subcategories');
            Schema::dropIfExists('categories');
        }

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
            $table->string('cloth')->nullable();
            $table->integer('min_width')->nullable();
            $table->integer('min_height')->nullable();
            $table->unsignedInteger('min_price')->nullable();
            $table->timestamp('min_price_updated_at')->nullable();
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
    }

    public function test_start_run_sets_total_candidates_and_cursor(): void
    {
        $this->seedBaseData();
        Product::query()->insert([
            $this->productRow(1001, 1, 10, 100, 'P1'),
            $this->productRow(1002, 1, 10, 100, 'P2'),
            $this->productRow(1003, 1, 10, 101, 'P3'),
        ]);

        /** @var MinPriceRecalcService $service */
        $service = $this->app->make(MinPriceRecalcService::class);
        $run = $service->startRun([
            'category_id' => 1,
            'subcategory_id' => 10,
            'model_ids' => [100],
            'mode' => 'manual',
            'start_id' => 1001,
            'end_id' => 1002,
        ], 100);

        $this->assertSame(2, $run->total_candidates);
        $this->assertSame(1000, $run->current_id);
    }

    public function test_process_batch_writes_run_items_and_skips_filled(): void
    {
        $this->seedBaseData();
        $filledTimestamp = now()->subDay()->startOfSecond();
        $filledProduct = $this->productRow(2, 1, 10, 100, 'P2', 999);
        $filledProduct['min_price_updated_at'] = $filledTimestamp;
        Product::query()->insert([
            $this->productRow(1, 1, 10, 100, 'P1', null),
            $filledProduct,
            $this->productRow(3, 1, 10, 100, 'P3', null),
        ]);

        $this->app->bind(ProductMinPriceCalculator::class, function () {
            return new class extends ProductMinPriceCalculator {
                public function calculate(array $payload): array
                {
                    if (($payload['prodTitle'] ?? '') === 'P1') {
                        return ['price' => 1234, 'error' => null];
                    }
                    return ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND];
                }
            };
        });

        /** @var MinPriceRecalcService $service */
        $service = $this->app->make(MinPriceRecalcService::class);
        $run = $service->startRun([
            'category_id' => 1,
            'subcategory_id' => 10,
            'model_ids' => [100],
            'skip_filled' => true,
            'overwrite_existing' => false,
        ], 10);

        $batch = $service->processNextBatch($run);
        $run->refresh();

        $this->assertTrue($batch['done']);
        $this->assertSame(3, $run->processed);
        $this->assertSame(1, $run->updated);
        $this->assertSame(2, $run->skipped);
        $this->assertSame(PriceRecalcRun::STATUS_DONE, $run->status);

        $items = PriceRecalcRunItem::query()->where('run_id', $run->id)->get();
        $this->assertCount(3, $items);
        $this->assertTrue($items->contains('status', PriceRecalcRunItem::STATUS_UPDATED));
        $this->assertTrue($items->contains('status', PriceRecalcRunItem::STATUS_SKIPPED));
        $this->assertTrue($items->contains('status', PriceRecalcRunItem::STATUS_ERROR));
        $filledProduct = Product::query()->findOrFail(2);
        $this->assertSame(999, $filledProduct->min_price);
        $this->assertTrue($filledProduct->min_price_updated_at->equalTo($filledTimestamp));
    }

    public function test_failed_overwrite_clears_the_existing_min_price_and_timestamp(): void
    {
        $this->seedBaseData();
        $staleTimestamp = now()->subDay()->startOfSecond();
        $product = $this->productRow(1, 1, 10, 100, 'P1', 999);
        $product['min_price_updated_at'] = $staleTimestamp;
        Product::query()->insert([$product]);

        $this->app->instance(ProductMinPriceCalculator::class, new class extends ProductMinPriceCalculator {
            public function calculate(array $payload): array
            {
                return ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND];
            }
        });

        /** @var MinPriceRecalcService $service */
        $service = $this->app->make(MinPriceRecalcService::class);
        $run = $service->startRun([
            'category_id' => 1,
            'subcategory_id' => 10,
            'model_ids' => [100],
            'mode' => PriceRecalcRun::MODE_MANUAL,
            'skip_filled' => false,
            'overwrite_existing' => true,
        ], 25);

        $service->processNextBatch($run);

        $product = Product::query()->findOrFail(1);
        $this->assertNull($product->min_price);
        $this->assertNull($product->min_price_updated_at);
        $this->assertSame(ProductMinPriceCalculator::ERROR_PRICE_NOT_FOUND, $product->min_price_error);

        $item = PriceRecalcRunItem::query()->where('run_id', $run->id)->sole();
        $this->assertSame(PriceRecalcRunItem::STATUS_ERROR, $item->status);
        $this->assertSame(999, $item->old_min_price);
        $this->assertNull($item->new_min_price);
    }

    public function test_failed_overwrite_preserves_existing_price_when_excel_sheet_is_unavailable(): void
    {
        $this->seedBaseData();
        $staleTimestamp = now()->subDay()->startOfSecond();
        $product = $this->productRow(1, 1, 10, 100, 'P1', 999);
        $product['min_price_updated_at'] = $staleTimestamp;
        Product::query()->insert([$product]);

        $this->app->instance(ProductMinPriceCalculator::class, new class extends ProductMinPriceCalculator {
            public function calculate(array $payload): array
            {
                return ['price' => null, 'error' => self::ERROR_SHEET_NOT_FOUND];
            }
        });

        /** @var MinPriceRecalcService $service */
        $service = $this->app->make(MinPriceRecalcService::class);
        $run = $service->startRun([
            'category_id' => 1,
            'subcategory_id' => 10,
            'model_ids' => [100],
            'mode' => PriceRecalcRun::MODE_MANUAL,
            'skip_filled' => false,
            'overwrite_existing' => true,
        ], 25);

        $service->processNextBatch($run);

        $product = Product::query()->findOrFail(1);
        $this->assertSame(999, $product->min_price);
        $this->assertTrue($product->min_price_updated_at->equalTo($staleTimestamp));
        $this->assertSame(ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND, $product->min_price_error);

        $item = PriceRecalcRunItem::query()->where('run_id', $run->id)->sole();
        $this->assertSame(PriceRecalcRunItem::STATUS_ERROR, $item->status);
        $this->assertSame(999, $item->old_min_price);
        $this->assertNull($item->new_min_price);
        $this->assertSame(ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND, $item->error_code);
    }

    public function test_stop_run_marks_status_and_preserves_cursor(): void
    {
        $this->seedBaseData();
        $products = [];
        for ($id = 1; $id <= 26; $id++) {
            $products[] = $this->productRow($id, 1, 10, 100, 'P' . $id, null);
        }
        Product::query()->insert($products);

        $this->app->instance(ProductMinPriceCalculator::class, new class extends ProductMinPriceCalculator {
            public function calculate(array $payload): array
            {
                return ['price' => 500, 'error' => null];
            }
        });

        /** @var MinPriceRecalcService $service */
        $service = $this->app->make(MinPriceRecalcService::class);
        $run = $service->startRun([
            'category_id' => 1,
            'subcategory_id' => 10,
            'model_ids' => [100],
        ], 25);

        $service->processNextBatch($run);
        $run->refresh();
        $cursor = $run->current_id;

        $run = $service->stopRun($run, 'operator_stop');
        $this->assertSame(PriceRecalcRun::STATUS_STOPPED, $run->status);
        $this->assertSame($cursor, $run->current_id);
        $this->assertSame('operator_stop', $run->stop_reason);
    }

    private function seedBaseData(): void
    {
        DB::table('categories')->insert(['id' => 1, 'title' => 'Cat', 'titleh1' => 'Cat', 'slug' => 'cat']);
        DB::table('subcategories')->insert(['id' => 10, 'category_id' => 1, 'title' => 'Sub', 'titleh1' => 'Sub', 'slug' => 'sub']);
        DB::table('prod_model')->insert([
            ['id' => 100, 'title' => 'Model A'],
            ['id' => 101, 'title' => 'Model B'],
        ]);
    }

    private function productRow(int $id, int $categoryId, int $subcategoryId, int $modelId, string $title, ?int $minPrice = null): array
    {
        return [
            'id' => $id,
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'model_id' => $modelId,
            'title' => $title,
            'h1' => $title,
            'slug' => strtolower($title) . '-' . $id,
            'cloth' => '1 категория',
            'min_width' => 500,
            'min_height' => 500,
            'min_price' => $minPrice,
            'min_price_updated_at' => null,
            'min_price_error' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
