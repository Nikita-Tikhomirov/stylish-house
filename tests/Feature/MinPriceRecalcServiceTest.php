<?php

namespace Tests\Feature;

use App\Models\PriceRecalcRun;
use App\Models\Product;
use App\Services\MinPriceRecalcService;
use App\Services\ProductMinPriceCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class MinPriceRecalcServiceTest extends TestCase
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
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->json('model_ids')->nullable();
            $table->unsignedInteger('batch_size')->default(200);
            $table->unsignedBigInteger('last_product_id')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_processes_one_batch_and_updates_counters(): void
    {
        DB::table('categories')->insert(['id' => 1, 'title' => 'Cat', 'titleh1' => 'Cat', 'slug' => 'cat']);
        DB::table('subcategories')->insert(['id' => 10, 'category_id' => 1, 'title' => 'Sub', 'titleh1' => 'Sub', 'slug' => 'sub']);
        DB::table('prod_model')->insert([
            ['id' => 100, 'title' => 'Model A'],
            ['id' => 101, 'title' => 'Model B'],
        ]);

        Product::query()->insert([
            [
                'id' => 1,
                'category_id' => 1,
                'subcategory_id' => 10,
                'model_id' => 100,
                'title' => 'P1',
                'h1' => 'P1',
                'slug' => 'p1',
                'cloth' => '1 категория',
                'min_width' => 500,
                'min_height' => 500,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'category_id' => 1,
                'subcategory_id' => 10,
                'model_id' => 100,
                'title' => 'P2',
                'h1' => 'P2',
                'slug' => 'p2',
                'cloth' => '1 категория',
                'min_width' => null,
                'min_height' => 500,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'category_id' => 1,
                'subcategory_id' => 10,
                'model_id' => 101,
                'title' => 'P3',
                'h1' => 'P3',
                'slug' => 'p3',
                'cloth' => '1 категория',
                'min_width' => 500,
                'min_height' => 500,
                'created_at' => now(),
                'updated_at' => now(),
            ],
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
        ], 10);

        $result = $service->processNextBatch($run);
        $run->refresh();

        $this->assertTrue($result['done']);
        $this->assertSame(2, $run->processed);
        $this->assertSame(1, $run->updated);
        $this->assertSame(1, $run->skipped);
        $this->assertSame(PriceRecalcRun::STATUS_DONE, $run->status);

        $p1 = Product::query()->findOrFail(1);
        $p2 = Product::query()->findOrFail(2);
        $p3 = Product::query()->findOrFail(3);

        $this->assertSame(1234, $p1->min_price);
        $this->assertNull($p1->min_price_error);
        $this->assertSame(ProductMinPriceCalculator::ERROR_INVALID_DIMENSIONS, $p2->min_price_error);
        $this->assertNull($p3->min_price);
    }

    public function test_does_not_process_when_run_is_paused(): void
    {
        $this->app->instance(ProductMinPriceCalculator::class, new ProductMinPriceCalculator());
        /** @var MinPriceRecalcService $service */
        $service = $this->app->make(MinPriceRecalcService::class);

        $run = PriceRecalcRun::query()->create([
            'status' => PriceRecalcRun::STATUS_PAUSED,
            'batch_size' => 200,
            'last_product_id' => 0,
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
            'started_at' => now(),
        ]);

        $result = $service->processNextBatch($run);

        $this->assertFalse($result['done']);
        $this->assertSame(0, $result['processed']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(0, $result['skipped']);
    }
}
