<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\User;
use App\Services\NavigationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NavigationMenuAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('customer');
            $table->rememberToken();
            $table->timestamps();
        });
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
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_only_admin_can_open_menu_editor(): void
    {
        $this->get('/admin/navigation-menu')->assertRedirect('/');

        $this->actingAs($this->user('customer'))
            ->get('/admin/navigation-menu')
            ->assertRedirect('/');

        $this->actingAs($this->user('admin'))
            ->get('/admin/navigation-menu')
            ->assertOk()
            ->assertSee('Структура меню');
    }

    public function test_admin_replaces_tree_transactionally_and_invalidates_header_cache(): void
    {
        $category = Category::create(['title' => 'Жалюзи', 'slug' => 'jaluzi']);
        $menu = NavigationMenu::create(['key' => 'header', 'name' => 'Главное меню']);
        NavigationItem::create([
            'navigation_menu_id' => $menu->id,
            'node_type' => 'tab',
            'placement' => 'mega',
            'label' => 'Старое меню',
            'source_type' => 'custom',
            'url' => '/old',
            'position' => 0,
            'is_active' => true,
        ]);
        $this->assertSame('Старое меню', app(NavigationService::class)->header()['tabs'][0]['label']);

        $response = $this->actingAs($this->user('admin'))->put('/admin/navigation-menu', [
            'items' => [[
                'node_type' => 'tab',
                'placement' => 'mega',
                'label' => 'Жалюзи',
                'source_type' => 'category',
                'source_id' => $category->id,
                'is_active' => true,
                'children' => [[
                    'node_type' => 'section',
                    'label' => 'Популярные',
                    'is_active' => true,
                    'children' => [[
                        'node_type' => 'link',
                        'label' => 'Все жалюзи',
                        'source_type' => 'category',
                        'source_id' => $category->id,
                        'is_active' => true,
                    ]],
                ]],
            ], [
                'node_type' => 'link',
                'placement' => 'utility',
                'label' => 'Контакты',
                'source_type' => 'custom',
                'url' => '/shop-pages/kontakty',
                'is_active' => true,
            ]],
        ]);

        $response->assertRedirect('/admin/navigation-menu');
        $this->assertDatabaseMissing('navigation_items', ['label' => 'Старое меню']);
        $this->assertDatabaseHas('navigation_items', ['label' => 'Популярные', 'node_type' => 'section']);
        $this->assertSame('Жалюзи', app(NavigationService::class)->header()['tabs'][0]['label']);
        $this->assertSame('Контакты', app(NavigationService::class)->header()['utilityLinks'][0]['label']);
    }

    public function test_invalid_hierarchy_external_url_and_missing_entity_are_rejected_without_replacing_tree(): void
    {
        $menu = NavigationMenu::create(['key' => 'header', 'name' => 'Главное меню']);
        NavigationItem::create([
            'navigation_menu_id' => $menu->id,
            'node_type' => 'tab',
            'placement' => 'mega',
            'label' => 'Рабочее меню',
            'source_type' => 'custom',
            'url' => '/catalog',
            'position' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user('admin'))->from('/admin/navigation-menu')->put('/admin/navigation-menu', [
            'items' => [[
                'node_type' => 'tab',
                'placement' => 'mega',
                'label' => 'Некорректное меню',
                'source_type' => 'category',
                'source_id' => 999999,
                'children' => [[
                    'node_type' => 'link',
                    'label' => 'Неверный уровень',
                    'source_type' => 'custom',
                    'url' => 'https://example.com',
                ]],
            ]],
        ]);

        $response->assertRedirect('/admin/navigation-menu')->assertSessionHasErrors();
        $this->assertDatabaseHas('navigation_items', ['label' => 'Рабочее меню']);
        $this->assertDatabaseMissing('navigation_items', ['label' => 'Некорректное меню']);
    }

    private function user(string $role): User
    {
        return User::forceCreate([
            'name' => ucfirst($role),
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => Hash::make('secret-password'),
            'role' => $role,
        ]);
    }
}
