<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PosKdsWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('role', 20);
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->increments('menu_item_id');
            $table->string('category', 50);
            $table->string('item_name', 100);
            $table->decimal('price', 10, 2);
            $table->string('image_url')->nullable();
            $table->boolean('is_available');
            $table->text('description')->nullable();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('order_id');
            $table->unsignedInteger('cashier_id');
            $table->unsignedInteger('table_number')->nullable();
            $table->string('order_type', 20);
            $table->string('status', 20);
            $table->decimal('total_amount', 10, 2);
            $table->timestampTz('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->increments('order_item_id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('menu_item_id');
            $table->unsignedInteger('quantity');
            $table->decimal('total', 10, 2);
        });

        $migration = require database_path('migrations/2026_10_06_000000_add_customer_order_tracking_fields.php');
        $migration->up();
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_guest_order_uses_database_prices_and_requires_the_pickup_code_to_track(): void
    {
        $item = $this->createMenuItem();

        $this->get(route('customer.menu'))->assertOk();
        $this->postJson(route('cart.add'), ['product_id' => $item->menu_item_id])
            ->assertOk()
            ->assertJsonPath('cart.1.price', 42.50);

        $response = $this->postJson(route('orders.submit'), [
            'order_type' => 'takeout',
            'total' => 0.01,
        ])->assertCreated()
            ->assertJsonPath('status', 'unapproved');

        $orderNumber = $response->json('order_number');
        $code = $response->json('code');

        $this->assertDatabaseHas('orders', [
            'order_number' => $orderNumber,
            'code' => $code,
            'status' => 'unapproved',
            'total_amount' => '42.50',
        ]);
        $this->assertDatabaseHas('order_items', [
            'menu_item_id' => $item->menu_item_id,
            'quantity' => 1,
            'total' => '42.50',
        ]);

        $this->getJson(route('orders.status', ['orderNumber' => $orderNumber]))
            ->assertUnprocessable();
        $this->getJson(route('orders.status', ['orderNumber' => $orderNumber, 'code' => '000000']))
            ->assertNotFound();
        $this->getJson(route('orders.status', ['orderNumber' => $orderNumber, 'code' => $code]))
            ->assertOk()
            ->assertJsonPath('order.status', 'unapproved');
    }

    public function test_cashier_kitchen_and_manager_process_an_order_through_separate_roles(): void
    {
        $order = $this->createOrder();
        $cashier = $this->createUser('cashier');
        $kitchen = $this->createUser('kitchen');
        $admin = $this->createUser('admin');

        $this->actingAs($cashier)
            ->post(route('cashier.orders.approve-code'), ['code' => $order->code])
            ->assertRedirect(route('cashier.index'));

        $this->assertDatabaseHas('orders', [
            'order_id' => $order->order_id,
            'status' => 'pending',
            'cashier_id' => $cashier->user_id,
        ]);

        $this->actingAs($kitchen)
            ->get(route('kds.index'))
            ->assertOk();

        $this->patchJson(route('kds.orders.status', $order), ['status' => 'preparing'])
            ->assertOk();
        $this->patchJson(route('kds.orders.status', $order), ['status' => 'ready'])
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('manager.orders.index'))
            ->assertOk();
        $this->patch(route('manager.orders.status', $order), ['status' => 'picked_up'])
            ->assertRedirect(route('manager.orders.index'));

        $this->assertDatabaseHas('orders', [
            'order_id' => $order->order_id,
            'status' => 'picked_up',
        ]);
    }

    public function test_kitchen_role_cannot_access_the_cashier_module(): void
    {
        $this->actingAs($this->createUser('kitchen'))
            ->get(route('cashier.index'))
            ->assertRedirect(route('login'));
    }

    public function test_shared_pickup_display_returns_only_active_pickup_codes_and_statuses(): void
    {
        $order = $this->createOrder();
        $order->update(['status' => 'preparing']);

        $this->getJson(route('display.orders'))
            ->assertOk()
            ->assertJsonPath('orders.0.code', '123456')
            ->assertJsonPath('orders.0.status', 'preparing')
            ->assertJsonMissingPath('orders.0.order_number');
    }

    private function createMenuItem(): MenuItem
    {
        return MenuItem::query()->create([
            'category' => 'Mains',
            'item_name' => 'Test curry',
            'price' => 42.50,
            'is_available' => true,
        ]);
    }

    private function createOrder(): Order
    {
        $item = $this->createMenuItem();
        $order = Order::query()->create([
            'order_number' => 'ORD-TEST-1001',
            'code' => '123456',
            'order_type' => 'takeout',
            'status' => 'unapproved',
            'total_amount' => 42.50,
            'created_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->order_id,
            'menu_item_id' => $item->menu_item_id,
            'quantity' => 1,
            'total' => 42.50,
        ]);

        return $order;
    }

    private function createUser(string $role): User
    {
        return User::query()->create([
            'role' => $role,
            'name' => ucfirst($role) . ' User',
            'email' => $role . '@example.test',
            'password' => 'password',
        ]);
    }
}
