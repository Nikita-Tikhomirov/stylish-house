<?php

namespace Tests\Support;

use App\Models\CatalogAttribute;
use App\Models\CatalogImportItem;
use App\Models\CatalogImportRun;
use App\Models\CatalogImportSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

abstract class CatalogImportPublicationTestCase extends TestCase
{
    /** @var array<int, Migration> */
    private array $catalogImportMigrations = [];

    protected string $validWebp;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        $this->validWebp = (string) file_get_contents(
            base_path('tests/fixtures/catalog-import/images/11889.webp')
        );

        $this->createPublicationDependencySchema();
        $migrationPaths = [
            '2026_08_25_000000_create_catalog_import_staging_tables.php',
            '2026_08_25_000100_create_catalog_attribute_tables.php',
            '2026_08_25_000200_add_catalog_import_fields_to_products_and_subcategories.php',
            '2026_08_26_000000_add_catalog_import_image_integrity_fields.php',
            '2026_08_26_000100_add_catalog_import_publication_control_fields.php',
        ];
        foreach ($migrationPaths as $migrationPath) {
            $absolutePath = database_path('migrations/'.$migrationPath);
            if (! is_file($absolutePath)) {
                continue;
            }
            $migration = require $absolutePath;
            $migration->up();
            $this->catalogImportMigrations[] = $migration;
        }
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        try {
            foreach (array_reverse($this->catalogImportMigrations) as $migration) {
                $migration->down();
            }
            Schema::dropIfExists('products');
            Schema::dropIfExists('subcategories');
            Schema::dropIfExists('categories');
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        parent::tearDown();
    }

    protected function seedReviewedRun(array $runOverrides = []): CatalogImportRun
    {
        $definitions = $this->canonicalSourceDefinitions();
        $collectorSources = array_map(
            static fn (array $source): array => [
                'label' => $source['label'],
                'sourceSlug' => $source['target_slug'],
                'sourceUrl' => $source['source_url'],
                'nextPageUrl' => $source['source_url'],
                'enabled' => true,
                'sortOrder' => $source['sort_order'],
                'pendingProducts' => [],
                'completed' => false,
                'pages' => 0,
            ],
            $definitions,
        );
        $run = CatalogImportRun::create(array_merge([
            'provider' => 'rimskie.com',
            'external_run_id' => 'full-run-001',
            'status' => CatalogImportRun::STATUS_REVIEWING,
            'config' => [
                'collector_config' => [
                    'schema_version' => 'stylish-house.catalog-import-run/v1',
                    'sources' => $collectorSources,
                    'limits' => [
                        'html_delay_ms' => [20000, 40000],
                        'image_delay_ms' => [10000, 20000],
                        'hourly_requests' => 120,
                        'backoff_ms' => [120000, 300000, 900000],
                        'concurrency' => 1,
                        'max_requests' => null,
                        'max_products' => null,
                    ],
                ],
            ],
            'source_count' => 46,
            'page_count' => 46,
            'unique_product_count' => 1,
            'image_count' => 1,
            'membership_count' => 46,
            'duplicate_count' => 0,
            'error_count' => 0,
            'error' => null,
            'completed_at' => now(),
        ], $runOverrides));

        $sources = [];
        foreach ($definitions as $definition) {
            $sources[] = $run->sources()->create([
                'label' => $definition['label'],
                'source_url' => $definition['source_url'],
                'target_slug' => $definition['target_slug'],
                'enabled' => true,
                'status' => CatalogImportSource::STATUS_COMPLETED,
                'sort_order' => $definition['sort_order'],
                'pages_count' => 1,
                'items_count' => 1,
                'rewritten_title' => 'Римские шторы — '.$definition['label'],
                'rewritten_h1' => 'Римские шторы — '.$definition['label'],
                'rewritten_intro' => 'Подборка римских штор по указанному признаку.',
                'rewritten_description' => 'В подборке представлены римские шторы с характеристиками из карточек товаров.',
                'rewritten_seo' => 'Римские шторы: характеристики и варианты исполнения.',
                'review_status' => CatalogImportSource::REVIEW_APPROVED,
                'warnings' => [],
                'error' => null,
            ]);
        }

        $privatePath = 'catalog-imports/full-run-001/images/11889.webp';
        Storage::disk('local')->put($privatePath, $this->validWebp);
        $item = $run->items()->create([
            'provider' => 'rimskie.com',
            'external_id' => '11889',
            'source_url' => 'https://rimskie.com/products/11889-example',
            'source_title' => 'Римская штора 11889',
            'source_description' => 'Тканевая римская штора белого цвета.',
            'source_price' => '2708.00',
            'source_image_path' => $privatePath,
            'source_image_sha256' => hash('sha256', $this->validWebp),
            'source_image_byte_length' => strlen($this->validWebp),
            'rewritten_title' => 'Белая римская штора',
            'rewritten_summary' => 'Тканевая римская штора белого цвета.',
            'rewritten_description' => 'Белая тканевая римская штора для оформления окна.',
            'rewritten_slug' => 'belaya-rimskaya-shtora-11889',
            'review_status' => CatalogImportItem::STATUS_APPROVED,
            'warnings' => [],
            'error' => null,
        ]);
        foreach ($sources as $source) {
            $item->sources()->attach($source->id);
        }

        $attribute = CatalogAttribute::create([
            'code' => 'color',
            'label' => 'Цвет',
            'type' => CatalogAttribute::TYPE_SELECT,
            'sort_order' => 1,
            'is_public' => true,
        ]);
        $value = $attribute->values()->create([
            'normalized_value' => 'white',
            'label' => 'Белый',
            'sort_order' => 1,
        ]);
        $item->attributeValues()->attach($value->id);

        return $run->fresh();
    }

    /** @return array<int, array<string, mixed>> */
    protected function canonicalSourceDefinitions(): array
    {
        return json_decode(
            (string) file_get_contents(base_path('config/rimskie-import-sources.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    protected function seedCatalogRoots(): array
    {
        $now = now();
        $categoryId = DB::table('categories')->insertGetId([
            'title' => 'Шторы',
            'slug' => 'story',
            'show_in_menu' => true,
            'show_in_catalog' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $subcategoryId = DB::table('subcategories')->insertGetId([
            'category_id' => $categoryId,
            'title' => 'Римские шторы',
            'slug' => 'rimskieshtory',
            'show_in_menu' => true,
            'show_in_catalog' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$categoryId, $subcategoryId];
    }

    private function createPublicationDependencySchema(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->string('titleh1')->nullable();
            $table->text('first_screen_text')->nullable();
            $table->string('img')->nullable();
            $table->boolean('show_in_menu')->default(false);
            $table->boolean('show_in_catalog')->default(false);
            $table->text('seo')->nullable();
            $table->timestamps();
        });

        Schema::create('subcategories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->string('titleh1')->nullable();
            $table->text('first_screen_text')->nullable();
            $table->string('img')->nullable();
            $table->boolean('show_in_menu')->default(false);
            $table->boolean('show_in_catalog')->default(false);
            $table->text('seo')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->string('h1')->nullable();
            $table->text('first_screenn_description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_thumb_path')->nullable();
            $table->string('show_in_menu')->nullable();
            $table->string('show_in_catalog')->nullable();
            $table->text('seo')->nullable();
            $table->unsignedInteger('min_price')->nullable();
            $table->timestamp('min_price_updated_at')->nullable();
            $table->string('min_price_error')->nullable();
            $table->timestamps();
        });
    }
}
