<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Subcategory;
use Database\Seeders\HeaderNavigationSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HeaderNavigationSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('titleh1')->nullable();
            $table->string('slug');
            $table->timestamps();
        });
        Schema::create('subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id');
            $table->string('title')->nullable();
            $table->string('titleh1')->nullable();
            $table->string('menu_title')->nullable();
            $table->string('slug');
            $table->timestamps();
        });
        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('navigation_menu_id');
            $table->foreignId('parent_id')->nullable();
            $table->string('node_type');
            $table->string('placement')->default('mega');
            $table->string('label');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('url')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->catalogFixtures();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('navigation_menus');
        Schema::dropIfExists('subcategories');
        Schema::dropIfExists('categories');

        parent::tearDown();
    }

    public function test_it_seeds_catalog_tabs_and_internal_seo_links_once(): void
    {
        $seeder = app(HeaderNavigationSeeder::class);
        $seeder->run();

        $menu = NavigationMenu::where('key', 'header')->firstOrFail();
        $this->assertSame(3, $menu->items()->where('node_type', 'tab')->count());
        $this->assertSame(3, $menu->items()->where('placement', 'quick')->count());
        $this->assertGreaterThanOrEqual(6, $menu->items()->where('source_type', 'subcategory')->count());
        $this->assertFalse($menu->items()->where('source_type', 'product')->exists());

        $before = NavigationItem::count();
        $seeder->run();
        $this->assertSame($before, NavigationItem::count());
    }

    private function catalogFixtures(): void
    {
        $fixtures = [
            'jaluzi' => ['Жалюзи', ['gorizontalnye-zhalyuzi' => 'Горизонтальные жалюзи', 'vertikalnye-zhalyuzi' => 'Вертикальные жалюзи']],
            'story' => ['Шторы', ['rulstori' => 'Рулонные шторы', 'rulonnye-shtory-den-noch' => 'Рулонные шторы День-Ночь']],
            'rolstavni' => ['Рольставни', ['santehnicheskie-rolleti' => 'Сантехнические роллеты', 'sekcionnye-vorota' => 'Секционные ворота']],
        ];

        foreach ($fixtures as $categorySlug => [$categoryTitle, $subcategories]) {
            $category = Category::create(['titleh1' => $categoryTitle, 'slug' => $categorySlug]);
            foreach ($subcategories as $slug => $title) {
                Subcategory::create([
                    'category_id' => $category->id,
                    'titleh1' => $title,
                    'slug' => $slug,
                ]);
            }
        }
    }
}
