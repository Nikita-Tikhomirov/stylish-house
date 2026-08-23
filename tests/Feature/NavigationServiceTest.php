<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Subcategory;
use App\Services\NavigationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NavigationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->timestamps();
        });

        Schema::create('subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id');
            $table->string('title');
            $table->string('slug');
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
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

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('navigation_menus');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('subcategories');
        Schema::dropIfExists('categories');

        parent::tearDown();
    }

    public function test_header_resolves_ordered_active_tree_and_all_internal_source_urls(): void
    {
        $category = Category::create(['title' => 'Жалюзи', 'slug' => 'jaluzi']);
        $subcategory = Subcategory::create([
            'category_id' => $category->id,
            'title' => 'Горизонтальные жалюзи',
            'slug' => 'gorizontalnye-zhalyuzi',
        ]);
        $page = Page::create(['title' => 'Замер', 'slug' => 'zamer']);
        $menu = NavigationMenu::create(['key' => 'header', 'name' => 'Главное меню']);

        $tab = $this->item($menu, [
            'node_type' => 'tab',
            'label' => 'Жалюзи',
            'source_type' => 'category',
            'source_id' => $category->id,
            'position' => 1,
        ]);
        $section = $this->item($menu, [
            'parent_id' => $tab->id,
            'node_type' => 'section',
            'label' => 'По типу',
            'position' => 2,
        ]);
        $this->item($menu, [
            'parent_id' => $section->id,
            'node_type' => 'link',
            'label' => 'Контакты',
            'source_type' => 'custom',
            'url' => '/shop-pages/kontakty',
            'position' => 3,
        ]);
        $this->item($menu, [
            'parent_id' => $section->id,
            'node_type' => 'link',
            'label' => 'Горизонтальные',
            'source_type' => 'subcategory',
            'source_id' => $subcategory->id,
            'position' => 1,
        ]);
        $this->item($menu, [
            'parent_id' => $section->id,
            'node_type' => 'link',
            'label' => 'Скрытая ссылка',
            'source_type' => 'custom',
            'url' => '/hidden',
            'position' => 2,
            'is_active' => false,
        ]);
        $this->item($menu, [
            'node_type' => 'link',
            'placement' => 'quick',
            'label' => 'Все жалюзи',
            'source_type' => 'category',
            'source_id' => $category->id,
            'position' => 1,
        ]);
        $this->item($menu, [
            'node_type' => 'link',
            'placement' => 'utility',
            'label' => 'Бесплатный замер',
            'source_type' => 'page',
            'source_id' => $page->id,
            'position' => 1,
        ]);

        $payload = app(NavigationService::class)->header();

        $this->assertSame('Жалюзи', $payload['tabs'][0]['label']);
        $this->assertSame('/jaluzi/', $payload['tabs'][0]['url']);
        $this->assertSame('Горизонтальные', $payload['tabs'][0]['sections'][0]['links'][0]['label']);
        $this->assertSame('/jaluzi/gorizontalnye-zhalyuzi/', $payload['tabs'][0]['sections'][0]['links'][0]['url']);
        $this->assertSame('/shop-pages/kontakty/', $payload['tabs'][0]['sections'][0]['links'][1]['url']);
        $this->assertCount(2, $payload['tabs'][0]['sections'][0]['links']);
        $this->assertSame('/jaluzi/', $payload['quickLinks'][0]['url']);
        $this->assertSame('/shop-pages/zamer/', $payload['utilityLinks'][0]['url']);
    }

    public function test_header_payload_is_reused_until_cache_is_forgotten(): void
    {
        $menu = NavigationMenu::create(['key' => 'header', 'name' => 'Главное меню']);
        $tab = $this->item($menu, [
            'node_type' => 'tab',
            'label' => 'Жалюзи',
            'source_type' => 'custom',
            'url' => '/jaluzi',
        ]);

        $service = app(NavigationService::class);
        $this->assertSame('Жалюзи', $service->header()['tabs'][0]['label']);

        $tab->update(['label' => 'Новое название']);
        $this->assertSame('Жалюзи', $service->header()['tabs'][0]['label']);

        $service->forgetHeader();
        $this->assertSame('Новое название', $service->header()['tabs'][0]['label']);
    }

    private function item(NavigationMenu $menu, array $attributes): NavigationItem
    {
        return NavigationItem::create(array_merge([
            'navigation_menu_id' => $menu->id,
            'placement' => 'mega',
            'position' => 0,
            'is_active' => true,
        ], $attributes));
    }
}
