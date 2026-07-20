<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureIsolatedDatabase();

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
            $table->string('slug')->nullable();
            $table->boolean('show_in_catalog')->default(false);
            $table->boolean('show_in_menu')->default(false);
            $table->timestamps();
        });

        Schema::create('subcategories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('slug')->nullable();
            $table->boolean('show_in_catalog')->default(false);
            $table->boolean('show_in_menu')->default(false);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->string('title')->nullable();
            $table->string('h1')->nullable();
            $table->string('slug')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_thumb_path')->nullable();
            $table->boolean('show_in_catalog')->default(false);
            $table->boolean('show_in_menu')->default(false);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->json('items');
            $table->decimal('total_price', 12, 2);
            $table->unsignedTinyInteger('status')->default(1);
            $table->text('comment')->nullable();
            $table->string('delivery_method')->nullable();
            $table->decimal('delivery_cost', 12, 2)->default(0);
            $table->json('customer_details')->nullable();
            $table->timestamps();
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->timestamps();
        });

        Schema::create('throu_elements', function (Blueprint $table) {
            $table->id();
            $table->string('logo_color')->nullable();
            $table->string('working_hours')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('address')->nullable();
            $table->string('text_after_logo')->nullable();
            $table->json('curtain_subcategories')->nullable();
            $table->json('blind_subcategories')->nullable();
            $table->timestamps();
        });

        DB::table('throu_elements')->insert([
            'logo_color' => '#000000',
            'curtain_subcategories' => json_encode([]),
            'blind_subcategories' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('subcategories');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('throu_elements');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_customer_account_shows_only_own_complete_orders_and_favorites(): void
    {
        $customer = $this->customer('customer@example.test', 'Иван');
        $otherCustomer = $this->customer('other@example.test', 'Петр');
        $favorite = Product::forceCreate([
            'title' => 'Избранная штора',
            'h1' => 'Избранная штора',
            'slug' => 'favorite-curtain',
        ]);
        $customer->favoriteProducts()->attach($favorite->id);

        Order::forceCreate([
            'user_id' => $customer->id,
            'items' => [$this->configuredItem('Свой заказ')],
            'total_price' => 26300,
            'status' => 2,
            'comment' => 'Позвонить перед доставкой',
            'delivery_method' => 'courier_mkad',
            'delivery_cost' => 700,
            'customer_details' => [
                'name' => 'Иван',
                'secondname' => 'Петров',
                'phone' => '+7 999 123-45-67',
                'email' => 'customer@example.test',
                'addres' => 'Москва, улица Тестовая, 1',
            ],
        ]);
        Order::forceCreate([
            'user_id' => $otherCustomer->id,
            'items' => [$this->configuredItem('Чужой заказ')],
            'total_price' => 9900,
        ]);

        $response = $this->actingAs($customer)->get('/profile');

        $response->assertOk()
            ->assertSee('Свой заказ')
            ->assertSee('Автоматическое управление')
            ->assertSee('Ригельный замок')
            ->assertSee('Позвонить перед доставкой')
            ->assertSee('Москва, улица Тестовая, 1')
            ->assertSee('Избранная штора')
            ->assertDontSee('Чужой заказ');
    }

    public function test_guest_is_redirected_from_customer_account(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    private function configureIsolatedDatabase(): void
    {
        if (extension_loaded('pdo_sqlite')) {
            config()->set('database.default', 'sqlite');
            config()->set('database.connections.sqlite.database', ':memory:');
            DB::purge('sqlite');

            return;
        }

        $database = getenv('TEST_DB_DATABASE');
        if (!$database) {
            $this->markTestSkipped('TEST_DB_DATABASE is required when pdo_sqlite is unavailable.');
        }

        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', $database);
        DB::purge('mysql');
    }

    private function customer(string $email, string $name): User
    {
        return User::forceCreate([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('secret-password'),
            'role' => 'customer',
        ]);
    }

    private function configuredItem(string $name): array
    {
        return [
            'productId' => 42,
            'productName' => $name,
            'width' => 900,
            'height' => 1200,
            'quantity' => 1,
            'price' => 25600,
            'configuration' => [
                'control_type' => [
                    'label' => 'Тип управления',
                    'value' => 'Автоматическое управление',
                    'code' => 'electric',
                    'price' => 7000,
                ],
                'lock_device' => [
                    'label' => 'Блокирующее устройство',
                    'value' => 'Ригельный замок',
                    'code' => 'rigel',
                    'price' => 900,
                ],
            ],
        ];
    }
}
