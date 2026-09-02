<?php

namespace Tests\Feature;

use App\Http\Controllers\SubcategoryController;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Tests\TestCase;

class CatalogPaginationTest extends TestCase
{
    private const TABLES = [
        'products',
        'prod_model',
        'subcategory_installation_types',
        'faqstable',
        'video_reviews',
        'work_examples',
        'fabrics',
        'throu_elements',
        'icon_cards',
        'home_pages',
        'reviews',
        'subcategories',
        'categories',
    ];

    private Category $category;

    private Subcategory $subcategory;

    private bool $ownsCatalogSchema = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite') {
            $this->markTestSkipped('Catalog pagination tests require the isolated SQLite test database.');
        }

        $this->withoutMiddleware();
        $this->createCatalogSchema();
        $this->seedCatalog();
    }

    protected function tearDown(): void
    {
        try {
            if ($this->ownsCatalogSchema) {
                Schema::disableForeignKeyConstraints();
                foreach (self::TABLES as $table) {
                    Schema::dropIfExists($table);
                }
                Schema::enableForeignKeyConstraints();
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_category_page_displays_thirty_products(): void
    {
        $response = $this->get('/'.$this->category->slug, [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();

        $this->assertSame(
            30,
            substr_count((string) $response->json('filterProduts'), 'class="bigProdCard card"')
        );
    }

    public function test_category_filter_returns_thirty_products(): void
    {
        $response = $this->postJson('/filter-cat-products/'.$this->category->id, [
            'subcategories' => [],
            'models' => [],
            'colors' => [],
            'materials' => [],
            'page' => 1,
        ]);

        $response->assertOk();
        $response->assertJsonCount(30, 'products');
        $this->assertStringContainsString('page=2', (string) $response->json('pagination'));

        $this->postJson('/filter-cat-products/'.$this->category->id, ['page' => 2])
            ->assertOk()
            ->assertJsonCount(1, 'products');
    }

    public function test_subcategory_page_displays_thirty_products(): void
    {
        $request = Request::create(
            '/'.$this->category->slug.'/'.$this->subcategory->slug.'/',
            'GET'
        );
        $session = app('session')->driver();
        $session->start();
        $request->setLaravelSession($session);
        app()->instance('request', $request);

        $view = app(SubcategoryController::class)->show(
            $request,
            $this->category->slug,
            $this->subcategory->slug
        );

        $this->assertInstanceOf(View::class, $view);

        $products = $view->getData()['filterProduts'];
        $this->assertInstanceOf(LengthAwarePaginator::class, $products);
        $this->assertSame(30, $products->perPage());
        $this->assertCount(30, $products->items());
    }

    public function test_subcategory_page_redirects_an_incorrect_category_slug(): void
    {
        $request = Request::create(
            '/wrong-category/'.$this->subcategory->slug.'?model=1%2C2',
            'GET'
        );

        $response = app(SubcategoryController::class)->show(
            $request,
            'wrong-category',
            $this->subcategory->slug
        );

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame(
            '/'.$this->category->slug.'/'.$this->subcategory->slug.'/?model=1%2C2',
            $response->headers->get('Location')
        );
    }

    public function test_subcategory_filter_returns_thirty_products(): void
    {
        $response = $this->postJson('/filter-subcat-products/'.$this->subcategory->id, [
            'models' => [],
            'colors' => [],
            'materials' => [],
            'page' => 1,
        ]);

        $response->assertOk();
        $response->assertJsonCount(30, 'products');
        $this->assertStringContainsString('page=2', (string) $response->json('pagination'));

        $this->postJson('/filter-subcat-products/'.$this->subcategory->id, ['page' => 2])
            ->assertOk()
            ->assertJsonCount(1, 'products');
    }

    private function createCatalogSchema(): void
    {
        $this->ownsCatalogSchema = true;

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('titleh1')->nullable();
            $table->text('description')->nullable();
            $table->json('related_items_ids')->nullable();
            $table->unsignedBigInteger('calc_prod')->nullable();
            $table->timestamps();
        });

        Schema::create('subcategories', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('titleh1')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('clone_subcategory_id')->nullable();
            $table->string('start_material')->nullable();
            $table->string('filter_color')->nullable();
            $table->json('model_id_to_filter')->nullable();
            $table->json('related_subcategory_ids')->nullable();
            $table->unsignedTinyInteger('template_variant')->nullable();
            $table->unsignedBigInteger('calc_prod')->nullable();
            $table->timestamps();
        });

        Schema::create('prod_model', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('h1')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('subcategory_id');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('color')->nullable();
            $table->string('material')->nullable();
            $table->string('cloth')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_thumb_path')->nullable();
            $table->string('fabric_photo')->nullable();
            $table->string('fabric_thumb_path')->nullable();
            $table->unsignedInteger('discount')->nullable();
            $table->unsignedInteger('min_price')->nullable();
            $table->unsignedInteger('min_width')->nullable();
            $table->unsignedInteger('min_height')->nullable();
            $table->unsignedInteger('price')->nullable();
            $table->unsignedInteger('old_price')->nullable();
            $table->boolean('show_in_catalog')->default(true);
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::create('home_pages', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::create('icon_cards', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });

        Schema::create('faqstable', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->timestamps();
        });

        Schema::create('video_reviews', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('work_examples', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->timestamps();
        });

        Schema::create('subcategory_installation_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('subcategory_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('fabrics', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('throu_elements', function (Blueprint $table): void {
            $table->id();
            $table->json('curtain_subcategories')->nullable();
            $table->json('blind_subcategories')->nullable();
            $table->timestamps();
        });
    }

    private function seedCatalog(): void
    {
        $now = now();
        $categoryId = DB::table('categories')->insertGetId([
            'title' => 'Test category',
            'slug' => 'test-category',
            'titleh1' => 'Test category',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $subcategoryId = DB::table('subcategories')->insertGetId([
            'title' => 'Test subcategory',
            'slug' => 'test-subcategory',
            'titleh1' => 'Test subcategory',
            'category_id' => $categoryId,
            'template_variant' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $modelId = DB::table('prod_model')->insertGetId([
            'title' => 'Test model',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('home_pages')->insert([
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('throu_elements')->insert([
            'curtain_subcategories' => '[]',
            'blind_subcategories' => '[]',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $products = [];
        foreach (range(1, 31) as $index) {
            $products[] = [
                'title' => 'Product '.$index,
                'slug' => 'product-'.$index,
                'h1' => 'Product '.$index,
                'category_id' => $categoryId,
                'subcategory_id' => $subcategoryId,
                'model_id' => $modelId,
                'color' => 'white',
                'material' => 'test material',
                'cloth' => 'test cloth',
                'min_price' => 1000 + $index,
                'show_in_catalog' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('products')->insert($products);

        $this->category = Category::findOrFail($categoryId);
        $this->subcategory = Subcategory::findOrFail($subcategoryId);
    }
}
