<?php

namespace Tests\Feature;

use App\Models\CatalogAttribute;
use App\Models\CatalogImportItem;
use App\Models\CatalogImportRun;
use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogImportSchemaTest extends TestCase
{
    /** @var array<int, Migration> */
    private array $migrations = [];

    private bool $migrationsAreUp = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createDependencySchema();

        $this->migrations = [
            require database_path('migrations/2026_08_25_000000_create_catalog_import_staging_tables.php'),
            require database_path('migrations/2026_08_25_000100_create_catalog_attribute_tables.php'),
            require database_path('migrations/2026_08_25_000200_add_catalog_import_fields_to_products_and_subcategories.php'),
        ];

        foreach ($this->migrations as $migration) {
            $migration->up();
        }

        $this->migrationsAreUp = true;
    }

    protected function tearDown(): void
    {
        if ($this->migrationsAreUp) {
            $this->migrateDown();
        }

        Schema::dropIfExists('products');
        Schema::dropIfExists('subcategories');
        Schema::dropIfExists('categories');

        parent::tearDown();
    }

    public function test_one_staging_item_can_belong_to_multiple_sources_without_duplication(): void
    {
        $run = CatalogImportRun::create([
            'provider' => 'rimskie.com',
            'external_run_id' => 'run-001',
            'status' => CatalogImportRun::STATUS_STAGED,
        ]);
        $item = $run->items()->create([
            'provider' => 'rimskie.com',
            'external_id' => '11889',
            'source_url' => 'https://rimskie.com/products/11889-example',
            'review_status' => CatalogImportItem::STATUS_NEEDS_REVIEW,
        ]);
        $white = $run->sources()->create([
            'label' => 'Белые',
            'source_url' => 'https://rimskie.com/catalog/white',
            'target_slug' => 'white',
            'status' => 'completed',
            'sort_order' => 1,
        ]);
        $office = $run->sources()->create([
            'label' => 'Для офиса',
            'source_url' => 'https://rimskie.com/catalog/office',
            'target_slug' => 'office',
            'status' => 'completed',
            'sort_order' => 2,
        ]);

        $item->sources()->syncWithoutDetaching([$white->id, $office->id, $white->id]);

        $this->assertSame(2, $item->sources()->count());
        $this->assertSame(1, CatalogImportItem::where('external_id', '11889')->count());
    }

    public function test_staging_item_identity_is_unique_within_each_run_only(): void
    {
        $firstRun = CatalogImportRun::create([
            'provider' => 'rimskie.com',
            'external_run_id' => 'run-001',
            'status' => CatalogImportRun::STATUS_STAGED,
        ]);
        $secondRun = CatalogImportRun::create([
            'provider' => 'rimskie.com',
            'external_run_id' => 'run-002',
            'status' => CatalogImportRun::STATUS_STAGED,
        ]);

        foreach ([$firstRun, $secondRun] as $run) {
            $run->items()->create([
                'provider' => 'rimskie.com',
                'external_id' => '11889',
                'source_url' => 'https://rimskie.com/products/11889-example',
                'review_status' => CatalogImportItem::STATUS_NEEDS_REVIEW,
            ]);
        }

        $this->assertSame(2, CatalogImportItem::where('external_id', '11889')->count());

        $this->expectException(QueryException::class);
        $firstRun->items()->create([
            'provider' => 'rimskie.com',
            'external_id' => '11889',
            'source_url' => 'https://rimskie.com/products/11889-duplicate',
            'review_status' => CatalogImportItem::STATUS_NEEDS_REVIEW,
        ]);
    }

    public function test_attributes_publication_metadata_and_production_relations_are_persisted(): void
    {
        $run = CatalogImportRun::create([
            'provider' => 'rimskie.com',
            'external_run_id' => 'run-001',
            'status' => CatalogImportRun::STATUS_REVIEWING,
            'config' => ['hourly_limit' => 120],
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'title' => 'Шторы',
            'slug' => 'story',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subcategory = Subcategory::create([
            'category_id' => $categoryId,
            'title' => 'Белые',
            'slug' => 'white',
            'is_import_collection' => true,
            'import_run_id' => $run->id,
        ]);
        $product = Product::create([
            'category_id' => $categoryId,
            'subcategory_id' => $subcategory->id,
            'title' => 'Римская штора',
            'slug' => 'rimskaya-shtora-11889',
            'source_provider' => 'rimskie.com',
            'source_external_id' => '11889',
            'source_url' => 'https://rimskie.com/products/11889-example',
            'source_price' => '2708.00',
            'calculator_enabled' => false,
            'import_run_id' => $run->id,
        ]);
        $legacyProduct = Product::create([
            'category_id' => $categoryId,
            'subcategory_id' => $subcategory->id,
            'title' => 'Обычная штора',
            'slug' => 'legacy-product',
        ]);
        $source = $run->sources()->create([
            'label' => 'Белые',
            'source_url' => 'https://rimskie.com/catalog/white',
            'target_slug' => 'white',
            'status' => 'completed',
            'sort_order' => 1,
            'review_status' => 'approved',
            'warnings' => ['title_was_short'],
            'published_subcategory_id' => $subcategory->id,
            'created_subcategory' => true,
            'publication_snapshot' => ['title' => 'До публикации'],
        ]);
        $item = $run->items()->create([
            'provider' => 'rimskie.com',
            'external_id' => '11889',
            'source_url' => 'https://rimskie.com/products/11889-example',
            'source_price' => '2708.00',
            'review_status' => CatalogImportItem::STATUS_APPROVED,
            'warnings' => ['removed_branding'],
            'published_product_id' => $product->id,
            'created_product' => true,
            'publication_snapshot' => ['title' => 'До публикации'],
        ]);
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

        $item->sources()->attach($source);
        $item->attributeValues()->attach($value);
        $product->catalogCollections()->attach($subcategory, ['catalog_import_run_id' => $run->id]);
        $product->attributeValues()->attach($value, ['catalog_import_run_id' => $run->id]);

        $this->assertTrue($legacyProduct->fresh()->calculator_enabled);
        $this->assertFalse($product->fresh()->calculator_enabled);
        $this->assertSame('2708.00', $product->fresh()->source_price);
        $this->assertTrue($subcategory->fresh()->is_import_collection);
        $this->assertTrue($source->fresh()->created_subcategory);
        $this->assertSame(['title' => 'До публикации'], $source->fresh()->publication_snapshot);
        $this->assertTrue($item->fresh()->created_product);
        $this->assertSame(['title' => 'До публикации'], $item->fresh()->publication_snapshot);
        $this->assertTrue($item->fresh()->product->is($product));
        $this->assertTrue($source->fresh()->publishedSubcategory->is($subcategory));
        $this->assertTrue($product->fresh()->importRun->is($run));
        $this->assertTrue($subcategory->fresh()->importRun->is($run));
        $this->assertSame([$subcategory->id], $product->catalogCollections()->pluck('subcategories.id')->all());
        $this->assertSame([$product->id], $subcategory->collectionProducts()->pluck('products.id')->all());
        $this->assertSame([$value->id], $item->attributeValues()->pluck('catalog_attribute_values.id')->all());
        $this->assertSame([$value->id], $product->attributeValues()->pluck('catalog_attribute_values.id')->all());
    }

    public function test_pivot_constraints_use_short_explicit_names(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('SQLite exposes index names portably for this focused assertion.');
        }

        $expectedIndexes = [
            'catalog_import_item_source' => 'ciis_item_source_uq',
            'catalog_import_item_attribute_value' => 'ciiav_item_value_uq',
            'catalog_product_attribute_value' => 'cpav_product_value_uq',
            'catalog_collection_product' => 'ccp_subcategory_product_uq',
        ];

        foreach ($expectedIndexes as $table => $expectedName) {
            $names = array_map(
                static fn (object $index): string => $index->name,
                DB::select("PRAGMA index_list('$table')")
            );

            $this->assertContains($expectedName, $names, "Missing named unique index on $table.");
            $this->assertLessThanOrEqual(64, strlen($expectedName));
        }
    }

    public function test_migrations_roll_back_without_removing_legacy_rows(): void
    {
        $categoryId = DB::table('categories')->insertGetId([
            'title' => 'Шторы',
            'slug' => 'story',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subcategoryId = DB::table('subcategories')->insertGetId([
            'category_id' => $categoryId,
            'title' => 'Римские шторы',
            'slug' => 'rimskieshtory',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'title' => 'Legacy product',
            'slug' => 'legacy-product',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->migrateDown();

        $this->assertTrue(Schema::hasTable('products'));
        $this->assertTrue(Schema::hasTable('subcategories'));
        $this->assertSame(1, DB::table('products')->where('id', $productId)->count());
        $this->assertSame(1, DB::table('subcategories')->where('id', $subcategoryId)->count());
        $this->assertFalse(Schema::hasColumn('products', 'source_provider'));
        $this->assertFalse(Schema::hasColumn('products', 'calculator_enabled'));
        $this->assertFalse(Schema::hasColumn('subcategories', 'is_import_collection'));
        $this->assertFalse(Schema::hasTable('catalog_import_runs'));
        $this->assertFalse(Schema::hasTable('catalog_collection_product'));
    }

    private function createDependencySchema(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('subcategories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    private function migrateDown(): void
    {
        foreach (array_reverse($this->migrations) as $migration) {
            $migration->down();
        }

        $this->migrationsAreUp = false;
    }
}
