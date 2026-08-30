<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GenerateSitemapTest extends TestCase
{
    private string $temporaryPublicPath;

    private string $originalPublicPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalPublicPath = app()->publicPath();
        $this->temporaryPublicPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'stylish-house-sitemap-'.bin2hex(random_bytes(8));

        mkdir($this->temporaryPublicPath);
        app()->usePublicPath($this->temporaryPublicPath);

        $this->createSitemapTables();
        $this->seedSitemapContent();
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        try {
            Schema::dropIfExists('products');
            Schema::dropIfExists('subcategories');
            Schema::dropIfExists('categories');
            Schema::dropIfExists('pages');
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        app()->usePublicPath($this->originalPublicPath);

        $sitemapPath = $this->temporaryPublicPath.DIRECTORY_SEPARATOR.'sitemap.xml';
        if (is_file($sitemapPath)) {
            unlink($sitemapPath);
        }

        if (is_dir($this->temporaryPublicPath)) {
            rmdir($this->temporaryPublicPath);
        }

        parent::tearDown();
    }

    /**
     * Regression rationale: the live sitemap had 35 URLs and 34 lacked the canonical slash.
     */
    public function test_all_public_sitemap_url_families_use_a_trailing_slash(): void
    {
        $this->artisan('sitemap:generate')->assertSuccessful();

        $sitemap = simplexml_load_file($this->temporaryPublicPath.DIRECTORY_SEPARATOR.'sitemap.xml');
        $locations = array_map(
            fn ($url) => (string) $url->loc,
            iterator_to_array($sitemap->url, false)
        );

        $this->assertSame([
            'http://localhost/',
            'http://localhost/policy/',
            'http://localhost/jaluzi/',
            'http://localhost/jaluzi/rulonnye-shtory/',
            'http://localhost/jaluzi/rulonnye-shtory/example-product/',
            'http://localhost/shop-pages/dostavka/',
        ], $locations);
    }

    public function test_sitemap_generation_is_scheduled_daily(): void
    {
        $this->assertSame(0, Artisan::call('schedule:list'));
        $this->assertStringContainsString('sitemap:generate', Artisan::output());

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'sitemap:generate'));

        $this->assertNotNull($event);
        $this->assertSame('15 3 * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }

    public function test_sitemap_excludes_products_with_inconsistent_category_relations(): void
    {
        $now = Carbon::parse('2026-08-17 00:00:00');
        DB::table('categories')->insert([
            'id' => 2,
            'slug' => 'story',
            'show_in_catalog' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('products')->insert([
            'id' => 2,
            'category_id' => 2,
            'subcategory_id' => 1,
            'slug' => 'inconsistent-product',
            'show_in_catalog' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->artisan('sitemap:generate')->assertSuccessful();

        $contents = file_get_contents($this->temporaryPublicPath.DIRECTORY_SEPARATOR.'sitemap.xml');
        $this->assertStringNotContainsString('inconsistent-product', $contents);
    }

    private function createSitemapTables(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug');
            $table->boolean('show_in_catalog');
            $table->timestamps();
        });

        Schema::create('subcategories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id');
            $table->string('slug');
            $table->boolean('show_in_catalog');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id');
            $table->foreignId('subcategory_id');
            $table->string('slug');
            $table->boolean('show_in_catalog');
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug');
            $table->timestamps();
        });
    }

    private function seedSitemapContent(): void
    {
        $now = Carbon::parse('2026-08-17 00:00:00');
        $timestamps = ['created_at' => $now, 'updated_at' => $now];

        DB::table('categories')->insert([
            'id' => 1,
            'slug' => 'jaluzi',
            'show_in_catalog' => true,
            ...$timestamps,
        ]);
        DB::table('subcategories')->insert([
            'id' => 1,
            'category_id' => 1,
            'slug' => 'rulonnye-shtory',
            'show_in_catalog' => true,
            ...$timestamps,
        ]);
        DB::table('products')->insert([
            'id' => 1,
            'category_id' => 1,
            'subcategory_id' => 1,
            'slug' => 'example-product',
            'show_in_catalog' => true,
            ...$timestamps,
        ]);
        DB::table('pages')->insert([
            'id' => 1,
            'slug' => 'dostavka',
            ...$timestamps,
        ]);
    }
}
