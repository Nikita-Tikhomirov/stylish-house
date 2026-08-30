<?php

namespace Tests\Feature;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CatalogRouteHierarchyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'app.url' => 'https://stylish-house.net',
        ]);

        $this->createCatalogSchema();
        $this->seedRomanShadeProduct();
    }

    public function test_subcategory_under_a_foreign_category_redirects_to_its_physical_category(): void
    {
        $response = $this->get('/jaluzi/rimskieshtory/');

        $response->assertStatus(Response::HTTP_MOVED_PERMANENTLY);
        $this->assertSame(
            '/story/rimskieshtory/',
            parse_url((string) $response->headers->get('Location'), PHP_URL_PATH)
        );
    }

    public function test_product_under_a_foreign_hierarchy_redirects_to_its_physical_hierarchy(): void
    {
        $response = $this->get('/jaluzi/rimskieshtory/roman-product/');

        $response->assertStatus(Response::HTTP_MOVED_PERMANENTLY);
        $this->assertSame(
            '/story/rimskieshtory/roman-product/',
            parse_url((string) $response->headers->get('Location'), PHP_URL_PATH)
        );
    }

    public function test_product_under_a_foreign_subcategory_redirects_to_its_physical_hierarchy(): void
    {
        $response = $this->get('/story/other-subcategory/roman-product/');

        $response->assertStatus(Response::HTTP_MOVED_PERMANENTLY);
        $this->assertSame(
            '/story/rimskieshtory/roman-product/',
            parse_url((string) $response->headers->get('Location'), PHP_URL_PATH)
        );
    }

    public function test_hierarchy_redirect_preserves_the_original_query_string(): void
    {
        $response = $this->get('/jaluzi/rimskieshtory/?page=9&model=42');

        $response->assertStatus(Response::HTTP_MOVED_PERMANENTLY);
        $location = (string) $response->headers->get('Location');
        $this->assertSame('/story/rimskieshtory/', parse_url($location, PHP_URL_PATH));
        $this->assertSame('page=9&model=42', parse_url($location, PHP_URL_QUERY));
    }

    public function test_slashless_foreign_subcategory_redirects_directly_to_the_final_path(): void
    {
        $response = $this->get('/jaluzi/rimskieshtory?page=9');

        $response->assertStatus(Response::HTTP_MOVED_PERMANENTLY);
        $this->assertSame(
            '/story/rimskieshtory/?page=9',
            $this->pathAndQuery((string) $response->headers->get('Location'))
        );
    }

    public function test_slashless_foreign_product_redirects_directly_to_the_final_path(): void
    {
        $response = $this->get('/jaluzi/rimskieshtory/roman-product?model=42');

        $response->assertStatus(Response::HTTP_MOVED_PERMANENTLY);
        $this->assertSame(
            '/story/rimskieshtory/roman-product/?model=42',
            $this->pathAndQuery((string) $response->headers->get('Location'))
        );
    }

    public function test_slashless_physical_subcategory_redirects_directly_to_its_trailing_slash_path(): void
    {
        $response = $this->get('/story/rimskieshtory?page=9');

        $response->assertStatus(Response::HTTP_MOVED_PERMANENTLY);
        $this->assertSame(
            '/story/rimskieshtory/?page=9',
            $this->pathAndQuery((string) $response->headers->get('Location'))
        );
    }

    public function test_slashless_physical_product_redirects_directly_to_its_trailing_slash_path(): void
    {
        $response = $this->get('/story/rimskieshtory/roman-product?model=42');

        $response->assertStatus(Response::HTTP_MOVED_PERMANENTLY);
        $this->assertSame(
            '/story/rimskieshtory/roman-product/?model=42',
            $this->pathAndQuery((string) $response->headers->get('Location'))
        );
    }

    public function test_product_redirect_preserves_the_raw_query_string_byte_for_byte(): void
    {
        $response = $this->getThroughKernel(
            '/jaluzi/rimskieshtory/roman-product/?tag=a&tag=b&encoded=a%2Fb&space=a+b'
        );

        $response->assertStatus(Response::HTTP_MOVED_PERMANENTLY);
        $this->assertSame(
            '/story/rimskieshtory/roman-product/?tag=a&tag=b&encoded=a%2Fb&space=a+b',
            $this->pathAndQuery((string) $response->headers->get('Location'))
        );
    }

    public function test_product_with_inconsistent_physical_relations_returns_not_found(): void
    {
        $now = now();
        DB::table('subcategories')->insert([
            'id' => 20,
            'title' => 'Подкатегория жалюзи',
            'slug' => 'blind-subcategory',
            'category_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('products')->insert([
            'id' => 9360,
            'title' => 'Товар с нарушенной связью',
            'slug' => 'inconsistent-product',
            'category_id' => 14,
            'subcategory_id' => 20,
            'model_id' => null,
            'related_product_ids' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->get('/story/blind-subcategory/inconsistent-product/')
            ->assertNotFound();
    }

    private function createCatalogSchema(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('subcategories', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignId('category_id');
            $table->unsignedBigInteger('clone_subcategory_id')->nullable();
            $table->unsignedTinyInteger('template_variant')->nullable();
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
            $table->foreignId('category_id');
            $table->foreignId('subcategory_id');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('related_product_ids')->nullable();
            $table->timestamps();
        });
    }

    private function seedRomanShadeProduct(): void
    {
        $now = now();

        DB::table('categories')->insert([
            ['id' => 1, 'title' => 'Жалюзи', 'slug' => 'jaluzi', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 14, 'title' => 'Шторы', 'slug' => 'story', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('subcategories')->insert([
            [
                'id' => 18,
                'title' => 'Римские шторы',
                'slug' => 'rimskieshtory',
                'category_id' => 14,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 19,
                'title' => 'Другая подкатегория',
                'slug' => 'other-subcategory',
                'category_id' => 14,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('products')->insert([
            'id' => 9359,
            'title' => 'Римская штора',
            'slug' => 'roman-product',
            'category_id' => 14,
            'subcategory_id' => 18,
            'model_id' => null,
            'related_product_ids' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function getThroughKernel(string $uri)
    {
        $kernel = $this->app->make(HttpKernel::class);
        $request = Request::create($uri, 'GET');
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $this->createTestResponse($response);
    }

    private function pathAndQuery(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        return is_string($query) && $query !== '' ? $path.'?'.$query : $path;
    }
}
