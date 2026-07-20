<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CartFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureIsolatedDatabase();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('h1')->nullable();
            $table->timestamps();
        });

        DB::table('products')->insert([
            'id' => 42,
            'title' => 'Рольворота RH77M',
            'h1' => 'Рольворота RH77M антрацит',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('products');

        parent::tearDown();
    }

    public function test_add_to_cart_keeps_all_product_and_configuration_choices(): void
    {
        $response = $this->postJson('/cart/add', [
            'productId' => 42,
            'width' => 900,
            'height' => 1200,
            'control' => true,
            'quantity' => 1,
            'price' => 25600,
            'side' => 'left',
            'widthType' => 'fabric',
            'controlColor' => 'white',
            'configuration' => [
                'installation_type' => [
                    'label' => 'Вид монтажа',
                    'value' => 'Встроенный монтаж',
                    'code' => 'built-in',
                    'price' => 1200,
                ],
                'box_position' => [
                    'label' => 'Тип монтажа',
                    'value' => 'Короб снаружи',
                    'code' => 'outside',
                    'price' => 0,
                ],
                'control_type' => [
                    'label' => 'Тип управления рольставни',
                    'value' => 'Автоматическое управление',
                    'code' => 'electric',
                    'price' => 7000,
                ],
                'lock_type' => [
                    'label' => 'Тип запорного устройства',
                    'value' => 'Замок',
                    'code' => 'lock',
                    'price' => 1600,
                ],
                'lock_device' => [
                    'label' => 'Блокирующее устройство',
                    'value' => 'Ригельный замок',
                    'code' => 'rigel',
                    'price' => 900,
                ],
                'additional_options' => [
                    [
                        'label' => 'Дополнительная опция',
                        'value' => 'Окраска в цвет RAL',
                        'code' => 'ral-paint',
                        'price' => 3500,
                    ],
                ],
            ],
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $item = array_values(session('cart'))[0];
        $this->assertSame('left', $item['side']);
        $this->assertSame('fabric', $item['widthType']);
        $this->assertSame('white', $item['controlColor']);
        $this->assertSame('electric', $item['configuration']['control_type']['code']);
        $this->assertSame(7000, $item['configuration']['control_type']['price']);
        $this->assertSame('rigel', $item['configuration']['lock_device']['code']);
        $this->assertSame('ral-paint', $item['configuration']['additional_options'][0]['code']);
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
}
