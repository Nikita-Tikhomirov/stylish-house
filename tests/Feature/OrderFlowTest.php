<?php

namespace Tests\Feature;

use App\Mail\OrderCreatedMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureIsolatedDatabase();
        config()->set('mail.order_recipient', 'orders@example.test');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('secondname')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('addres')->nullable();
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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('orders');
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

    public function test_checkout_stores_complete_cart_customer_and_delivery_snapshots(): void
    {
        Mail::fake();

        $response = $this->withSession([
            'cart' => [$this->cartKey() => $this->cartItem()],
            'delivery_cost' => 700,
        ])->postJson('/create-order', $this->customerPayload());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['order_id', 'redirect_url']);

        $order = Order::query()->firstOrFail();
        $this->assertSame('Нужен монтаж после 18:00', $order->comment);
        $this->assertSame('courier_mkad', $order->delivery_method);
        $this->assertSame('700.00', $order->delivery_cost);
        $this->assertSame('Иван', $order->customer_details['name']);
        $this->assertSame(
            'Автоматическое управление',
            $order->items[0]['configuration']['control_type']['value']
        );
        $this->assertSame('left', $order->items[0]['side']);
        $this->assertSame('fabric', $order->items[0]['widthType']);
        $this->assertSame('white', $order->items[0]['controlColor']);
        $this->assertSame(
            'Ригельный замок',
            $order->items[0]['configuration']['lock_device']['value']
        );
        $this->assertSame('26300.00', $order->total_price);

        Mail::assertSent(OrderCreatedMail::class, 2);
        Mail::assertSent(OrderCreatedMail::class, function (OrderCreatedMail $mail) {
            if (!$mail->adminCopy || !$mail->hasTo('orders@example.test')) {
                return false;
            }

            $html = $mail->render();
            $this->assertStringContainsString('Автоматическое управление', $html);
            $this->assertStringContainsString('Ригельный замок', $html);
            $this->assertStringContainsString('Окраска в цвет RAL', $html);
            $this->assertStringContainsString('Слева', $html);
            $this->assertStringContainsString('По ткани', $html);
            $this->assertStringContainsString('Белый', $html);
            $this->assertStringContainsString('Нужен монтаж после 18:00', $html);
            $this->assertStringContainsString('26 300', $html);

            return true;
        });
        Mail::assertSent(OrderCreatedMail::class, fn (OrderCreatedMail $mail) =>
            !$mail->adminCopy && $mail->hasTo('ivan@example.test')
        );

        $accountAndAdminHtml = view('partials.order-item-details', [
            'item' => $order->items[0],
        ])->render();
        $this->assertStringContainsString('Автоматическое управление', $accountAndAdminHtml);
        $this->assertStringContainsString('Ригельный замок', $accountAndAdminHtml);
        $this->assertStringContainsString('Окраска в цвет RAL', $accountAndAdminHtml);
        $this->assertStringContainsString('Слева', $accountAndAdminHtml);

        $adminHtml = view('admin.home', [
            'orders' => Order::query()->with('user')->get(),
        ])->render();
        $this->assertStringContainsString('Иван Петров', $adminHtml);
        $this->assertStringContainsString('ivan@example.test', $adminHtml);
        $this->assertStringContainsString('Автоматическое управление', $adminHtml);
        $this->assertStringContainsString('Нужен монтаж после 18:00', $adminHtml);
    }

    public function test_existing_customer_email_does_not_authenticate_guest_without_password(): void
    {
        Mail::fake();
        $existing = User::forceCreate([
            'name' => 'Владелец',
            'email' => 'customer@example.test',
            'password' => Hash::make('secret-password'),
            'role' => 'customer',
        ]);

        $response = $this->withSession([
            'cart' => [$this->cartKey() => $this->cartItem()],
        ])->postJson('/create-order', $this->customerPayload([
            'email' => $existing->email,
        ]));

        $response->assertOk()->assertJsonPath('requires_login', true);
        $this->assertFalse(Auth::check());
        $this->assertSame($existing->id, Order::query()->firstOrFail()->user_id);
    }

    public function test_authenticated_customer_keeps_their_account_and_receives_the_order(): void
    {
        Mail::fake();
        $customer = User::forceCreate([
            'name' => 'Ирина',
            'email' => 'irina@example.test',
            'password' => Hash::make('secret-password'),
            'role' => 'customer',
        ]);

        $response = $this->actingAs($customer)
            ->withSession(['cart' => [$this->cartKey() => $this->cartItem()]])
            ->postJson('/create-order', $this->customerPayload([
                'email' => 'other@example.test',
            ]));

        $response->assertOk()->assertJsonPath('requires_login', false);
        $this->assertSame($customer->id, Order::query()->firstOrFail()->user_id);
        $this->assertAuthenticatedAs($customer);
    }

    public function test_customer_notification_is_attempted_when_admin_notification_fails(): void
    {
        $customerMail = Mockery::mock();
        $customerMail->shouldReceive('send')
            ->once()
            ->with(Mockery::type(OrderCreatedMail::class));

        Mail::shouldReceive('to')
            ->once()
            ->with('orders@example.test')
            ->andThrow(new RuntimeException('Admin mailbox is unavailable'));
        Mail::shouldReceive('to')
            ->once()
            ->with('ivan@example.test')
            ->andReturn($customerMail);

        $this->withSession([
            'cart' => [$this->cartKey() => $this->cartItem()],
        ])->postJson('/create-order', $this->customerPayload())
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    private function cartKey(): string
    {
        return 'configured-role-shutter';
    }

    private function cartItem(): array
    {
        return [
            'productId' => 42,
            'productName' => 'Рольворота RH77M антрацит',
            'width' => 900,
            'height' => 1200,
            'quantity' => 1,
            'price' => 25600,
            'side' => 'left',
            'widthType' => 'fabric',
            'controlColor' => 'white',
            'configuration' => [
                'installation_type' => [
                    'label' => 'Тип монтажа',
                    'value' => 'Короб снаружи',
                    'code' => 'outside',
                    'price' => 0,
                ],
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
                'additional_options' => [
                    [
                        'label' => 'Дополнительная опция',
                        'value' => 'Окраска в цвет RAL',
                        'code' => 'ral-paint',
                        'price' => 3500,
                    ],
                ],
            ],
        ];
    }

    private function customerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Иван',
            'secondname' => 'Петров',
            'addres' => 'Москва',
            'phone' => '+7 999 123-45-67',
            'email' => 'ivan@example.test',
            'comment' => 'Нужен монтаж после 18:00',
        ], $overrides);
    }
}
