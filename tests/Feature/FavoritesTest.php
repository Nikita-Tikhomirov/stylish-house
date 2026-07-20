<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FavoritesTest extends TestCase
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

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('h1')->nullable();
            $table->timestamps();
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        DB::table('products')->insert([
            ['id' => 10, 'title' => 'Товар 10', 'h1' => 'Товар 10'],
            ['id' => 11, 'title' => 'Товар 11', 'h1' => 'Товар 11'],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('products');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
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

    public function test_customer_can_add_and_remove_a_favorite(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->postJson('/favorites/10')
            ->assertOk()
            ->assertJson(['favorite' => true]);

        $this->assertDatabaseHas('favorites', ['user_id' => $customer->id, 'product_id' => 10]);

        $this->actingAs($customer)
            ->deleteJson('/favorites/10')
            ->assertOk()
            ->assertJson(['favorite' => false]);

        $this->assertDatabaseMissing('favorites', ['user_id' => $customer->id, 'product_id' => 10]);
    }

    public function test_guest_favorites_can_be_synchronized_after_login(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->postJson('/favorites/sync', ['product_ids' => [10, 11, 99999, 10]])
            ->assertOk()
            ->assertJsonPath('product_ids', [10, 11]);

        $this->assertDatabaseCount('favorites', 2);
    }

    public function test_favorite_endpoints_require_authentication(): void
    {
        $this->postJson('/favorites/10')->assertUnauthorized();
        $this->postJson('/favorites/sync', ['product_ids' => [10]])->assertUnauthorized();
    }

    private function customer(): User
    {
        return User::forceCreate([
            'name' => 'Покупатель',
            'email' => 'customer@example.test',
            'password' => Hash::make('secret-password'),
            'role' => 'customer',
        ]);
    }
}
