<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\AddOn;
use App\Models\Payment;
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

        Schema::dropIfExists('transaction_requests');
        Schema::dropIfExists('order_item_add_ons');
        Schema::dropIfExists('menu_item_add_ons');
        Schema::dropIfExists('add_ons');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('payments');
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
        $posMigration = require database_path('migrations/2026_10_15_000000_expand_cashier_pos_workflow.php');
        $posMigration->up();
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('transaction_requests');
        Schema::dropIfExists('order_item_add_ons');
        Schema::dropIfExists('menu_item_add_ons');
        Schema::dropIfExists('add_ons');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_public_customer_ordering_routes_are_removed(): void
    {
        $this->get('/menu')->assertNotFound();
        $this->get('/api/products')->assertNotFound();
        $this->get('/api/cart')->assertNotFound();
        $this->postJson('/api/cart/add', [])->assertNotFound();
        $this->postJson('/api/cart/remove', [])->assertNotFound();
        $this->postJson('/api/cart/update', [])->assertNotFound();
        $this->postJson('/api/orders/submit', [])->assertNotFound();
        $this->postJson('/api/orders/details', [])->assertNotFound();
        $this->getJson('/api/orders/ORD-TEST-1001/status')->assertNotFound();
    }

    public function test_home_redirects_to_login_and_cashier_can_create_a_kitchen_order_from_its_menu(): void
    {
        $this->get('/')->assertRedirect(route('login'));

        $item = $this->createMenuItem();
        $cashier = $this->createUser('cashier');
        $this->actingAs($cashier)
            ->get('/')
            ->assertRedirect(route('cashier.index'));

        $this->get(route('cashier.menu'))
            ->assertOk()
            ->assertSee('Create an order')
            ->assertSee('Test curry')
            ->assertSee('Current order')
            ->assertSee('data-category-filter="all"', false);

        $this->postJson(route('cashier.cart.add'), [
            'product_id' => $item->menu_item_id,
            'quantity' => 2,
        ])->assertOk()
            ->assertJsonPath('cartItemCount', 2)
            ->assertJsonPath('cartTotal', 85);

        $cartKey = array_key_first($this->get(route('cashier.cart'))->viewData('cart'));
        $this->postJson(route('cashier.cart.update'), [
            'cart_key' => $cartKey,
            'action' => 'minus',
        ])->assertOk()->assertJsonPath('cartItemCount', 1);
        $this->postJson(route('cashier.cart.update'), [
            'cart_key' => $cartKey,
            'action' => 'remove',
        ])->assertOk()->assertJsonPath('cartItemCount', 0);
        $this->postJson(route('cashier.cart.add'), [
            'product_id' => $item->menu_item_id,
            'quantity' => 1,
        ])->assertOk()->assertJsonPath('cartItemCount', 1);

        $this->post(route('cashier.orders.store'), [
            'order_type' => 'dine-in',
            'table_number' => 4,
        ])->assertRedirect();

        $order = Order::query()->where('cashier_id', $cashier->user_id)->firstOrFail();
        $this->assertDatabaseHas('orders', [
            'order_id' => $order->order_id,
            'status' => 'awaiting_payment',
            'order_type' => 'dine-in',
            'table_number' => 4,
            'total_amount' => '42.50',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->order_id,
            'menu_item_id' => $item->menu_item_id,
            'quantity' => 1,
            'total' => '42.50',
            'item_name_snapshot' => 'Test curry',
            'unit_price' => '42.50',
        ]);
        $this->assertNull($order->code);
        $this->get(route('cashier.orders.payment', $order))
            ->assertOk()
            ->assertSee('Process payment');
    }

    public function test_cashier_payment_validates_cash_records_change_and_prevents_duplicate_payment(): void
    {
        $item = $this->createMenuItem();
        $cashier = $this->createUser('cashier');

        $this->actingAs($cashier)->post(route('cashier.cart.add'), [
            'product_id' => $item->menu_item_id,
            'quantity' => 1,
        ]);
        $this->post(route('cashier.orders.store'), ['order_type' => 'takeout']);
        $order = Order::query()->where('cashier_id', $cashier->user_id)->firstOrFail();

        $this->from(route('cashier.orders.payment', $order))
            ->post(route('cashier.orders.payment.store', $order), [
                'payment_method' => 'cash',
                'cash_received' => '42.49',
            ])
            ->assertSessionHasErrors('cash_received');
        $this->assertDatabaseMissing('payments', ['order_id' => $order->order_id]);

        $this->post(route('cashier.orders.payment.store', $order), [
            'payment_method' => 'cash',
            'cash_received' => '50.00',
        ])->assertRedirect(route('cashier.receipts.show', $order));

        $payment = Payment::query()->where('order_id', $order->order_id)->firstOrFail();
        $this->assertSame('50.00', $payment->amount_tethered);
        $this->assertSame('7.50', $payment->change_due);
        $this->assertSame('completed', $payment->transaction_status);
        $this->assertDatabaseHas('orders', [
            'order_id' => $order->order_id,
            'status' => 'pending',
        ]);

        $this->from(route('cashier.orders.payment', $order))
            ->post(route('cashier.orders.payment.store', $order), [
                'payment_method' => 'cash',
                'cash_received' => '50.00',
            ])
            ->assertSessionHasErrors('payment');

        $this->get(route('cashier.receipts.show', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Cash received');

        $otherCashier = $this->createUser('cashier');
        $this->actingAs($otherCashier)
            ->get(route('cashier.receipts.show', $order))
            ->assertNotFound();
    }

    public function test_cashier_addons_are_validated_and_saved_as_historical_snapshots(): void
    {
        $item = $this->createMenuItem();
        $addon = AddOn::query()->create([
            'add_on_name' => 'Extra sauce',
            'price' => 5.00,
            'is_available' => true,
        ]);
        $item->addOns()->attach($addon->add_on_id);
        $cashier = $this->createUser('cashier');
        $unattachedAddOn = AddOn::query()->create([
            'add_on_name' => 'Unrelated topping',
            'price' => 3.00,
            'is_available' => true,
        ]);

        $this->actingAs($cashier)
            ->post(route('cashier.cart.add'), [
                'product_id' => $item->menu_item_id,
                'quantity' => 2,
                'addon_ids' => [$unattachedAddOn->add_on_id],
            ])
            ->assertSessionHasErrors('addon_ids');
        $this->post(route('cashier.cart.add'), [
                'product_id' => $item->menu_item_id,
                'quantity' => 2,
                'addon_ids' => [$addon->add_on_id],
            ])
            ->assertRedirect(route('cashier.cart'));
        $this->get(route('cashier.cart'))
            ->assertOk()
            ->assertSee('Extra sauce');
        $this->post(route('cashier.orders.store'), ['order_type' => 'takeout']);
        $order = Order::query()->where('cashier_id', $cashier->user_id)->firstOrFail();
        $orderItem = OrderItem::query()->where('order_id', $order->order_id)->firstOrFail();

        $this->assertSame('95.00', $order->total_amount);
        $this->assertSame('95.00', $orderItem->total);
        $this->assertDatabaseHas('order_item_add_ons', [
            'order_item_id' => $orderItem->order_item_id,
            'add_on_name_snapshot' => 'Extra sauce',
            'unit_price' => '5.00',
            'total' => '10.00',
        ]);
    }

    public function test_gcash_requires_a_valid_manual_reference_and_does_not_verify_it(): void
    {
        $order = $this->createOrder();
        $cashier = $this->createUser('cashier');
        $order->update(['cashier_id' => $cashier->user_id, 'status' => 'awaiting_payment']);

        $this->actingAs($cashier)
            ->from(route('cashier.orders.payment', $order))
            ->post(route('cashier.orders.payment.store', $order), [
                'payment_method' => 'gcash',
            ])
            ->assertSessionHasErrors('gcash_ref_num');

        $this->post(route('cashier.orders.payment.store', $order), [
                'payment_method' => 'gcash',
                'gcash_ref_num' => 'bad ref!',
            ])
            ->assertSessionHasErrors('gcash_ref_num');

        $this->post(route('cashier.orders.payment.store', $order), [
            'payment_method' => 'gcash',
            'gcash_ref_num' => '1234567890123',
        ])->assertRedirect(route('cashier.receipts.show', $order));

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->order_id,
            'payment_method' => 'gcash',
            'gcash_ref_num' => '1234567890123',
            'amount_tethered' => '42.50',
            'transaction_status' => 'completed',
        ]);
    }

    public function test_cashier_void_request_requires_admin_approval_and_is_audited(): void
    {
        $order = $this->createOrder();
        $cashier = $this->createUser('cashier');
        $admin = $this->createUser('admin');
        $order->update(['cashier_id' => $cashier->user_id, 'status' => 'pending']);
        $payment = Payment::query()->create([
            'order_id' => $order->order_id,
            'payment_method' => 'cash',
            'amount_tethered' => 50,
            'change_due' => 7.50,
            'transaction_status' => 'completed',
            'created_at' => now(),
        ]);

        $this->actingAs($cashier)
            ->post(route('cashier.transactions.requests.store', $payment), [
                'request_type' => 'void',
                'reason' => 'Order entered in error',
            ])
            ->assertRedirect(route('cashier.transactions.show', $payment));
        $this->from(route('cashier.transactions.show', $payment))
            ->post(route('cashier.transactions.requests.store', $payment), [
                'request_type' => 'refund',
                'reason' => 'Duplicate request',
            ])
            ->assertSessionHasErrors('request_type');
        $this->assertDatabaseHas('payments', [
            'payment_id' => $payment->payment_id,
            'transaction_status' => 'completed',
        ]);

        $request = $payment->transactionRequests()->firstOrFail();
        $this->actingAs($admin)->get(route('admin.transaction-requests.index'))
            ->assertOk()
            ->assertSee($order->order_number);
        $this->actingAs($admin)
            ->post(route('admin.transaction-requests.review', $request), [
                'decision' => 'approve',
                'decision_reason' => 'Verified with shift log',
            ])
            ->assertRedirect(route('admin.transaction-requests.index'));

        $this->assertDatabaseHas('payments', [
            'payment_id' => $payment->payment_id,
            'transaction_status' => 'voided',
        ]);
        $this->assertDatabaseHas('orders', [
            'order_id' => $order->order_id,
            'status' => 'voided',
        ]);
        $this->assertDatabaseHas('transaction_requests', [
            'transaction_request_id' => $request->transaction_request_id,
            'status' => 'approved',
            'requested_by' => $cashier->user_id,
            'reviewed_by' => $admin->user_id,
            'decision_reason' => 'Verified with shift log',
        ]);
    }

    public function test_cashier_history_and_active_order_quick_view_are_limited_to_the_owner(): void
    {
        $order = $this->createOrder();
        $cashier = $this->createUser('cashier');
        $otherCashier = $this->createUser('cashier');
        $order->update(['cashier_id' => $cashier->user_id, 'status' => 'pending']);
        $payment = Payment::query()->create([
            'order_id' => $order->order_id,
            'payment_method' => 'cash',
            'amount_tethered' => 42.50,
            'change_due' => 0,
            'transaction_status' => 'completed',
            'created_at' => now(),
        ]);

        $this->actingAs($cashier)
            ->get(route('cashier.orders.quick-view', $order))
            ->assertOk()
            ->assertJsonPath('order.number', $order->order_number);
        $this->get(route('cashier.transactions.index'))->assertOk()->assertSee($order->order_number);
        $this->get(route('cashier.transactions.show', $payment))->assertOk()->assertSee('Transaction details');

        $this->actingAs($otherCashier)
            ->get(route('cashier.orders.quick-view', $order))
            ->assertNotFound();
        $this->get(route('cashier.transactions.show', $payment))->assertNotFound();
    }

    public function test_dashboard_statistics_and_active_orders_use_cashier_and_business_day_scope(): void
    {
        $cashier = $this->createUser('cashier');
        $todayOrder = $this->createOrder('ORD-TODAY-1001', '123451');
        $todayOrder->update(['cashier_id' => $cashier->user_id, 'status' => 'pending']);
        Payment::query()->create([
            'order_id' => $todayOrder->order_id,
            'payment_method' => 'cash',
            'amount_tethered' => 42.50,
            'change_due' => 0,
            'transaction_status' => 'completed',
            'created_at' => now(),
        ]);

        $oldOrder = $this->createOrder('ORD-OLD-1002', '123452');
        $oldOrder->update(['cashier_id' => $cashier->user_id, 'status' => 'picked_up', 'created_at' => now()->subDay()]);
        Payment::query()->create([
            'order_id' => $oldOrder->order_id,
            'payment_method' => 'gcash',
            'gcash_ref_num' => '123456',
            'amount_tethered' => 42.50,
            'change_due' => 0,
            'transaction_status' => 'completed',
            'created_at' => now()->subDay(),
        ]);

        $unpaidOrder = $this->createOrder('ORD-UNPAID-1003', '123453');
        $unpaidOrder->update(['cashier_id' => $cashier->user_id, 'status' => 'awaiting_payment']);

        $this->actingAs($cashier)
            ->get(route('cashier.index'))
            ->assertOk()
            ->assertSee('data-dashboard-orders="' . route('cashier.dashboard.orders', 'paid-today') . '"', false)
            ->assertSee('data-dashboard-orders="' . route('cashier.dashboard.orders', 'pending') . '"', false)
            ->assertSee('data-dashboard-orders="' . route('cashier.dashboard.orders', 'active') . '"', false)
            ->assertSee('data-dashboard-orders="' . route('cashier.dashboard.orders', 'awaiting-payment') . '"', false)
            ->assertDontSee('<nav class="mb-5 flex flex-wrap gap-2" aria-label="Cashier navigation">', false)
            ->assertDontSee('Customer orders')
            ->assertSee('id="cashier-popup-frame"', false)
            ->assertSee('max-w-7xl', false)
            ->assertDontSee('id="cashier-popup-title"', false)
            ->assertViewHas('processedToday', 1)
            ->assertViewHas('activeOrderCount', 1)
            ->assertViewHas('pendingOrderCount', 1)
            ->assertViewHas('awaitingPaymentCount', 1);
        $this->get(route('cashier.orders.index'))
            ->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->count() === 1 && $orders->first()->order_id === $todayOrder->order_id);
        $this->get(route('cashier.menu'))
            ->assertOk()
            ->assertSee('href="' . route('cashier.index') . '"', false)
            ->assertDontSee('href="' . route('cashier.index') . '" data-cashier-popup', false);
    }

    public function test_dashboard_stat_cards_show_their_matching_orders_and_items(): void
    {
        $cashier = $this->createUser('cashier');
        $otherCashier = $this->createUser('cashier');

        $paidOrder = $this->createOrder('ORD-PAID-TODAY-1001', '123451');
        $paidOrder->update(['cashier_id' => $cashier->user_id, 'status' => 'pending']);
        Payment::query()->create([
            'order_id' => $paidOrder->order_id,
            'payment_method' => 'cash',
            'amount_tethered' => 50,
            'change_due' => 7.50,
            'transaction_status' => 'completed',
            'created_at' => now(),
        ]);

        $unpaidOrder = $this->createOrder('ORD-AWAITING-1002', '123452');
        $unpaidOrder->update(['cashier_id' => $cashier->user_id, 'status' => 'awaiting_payment']);

        $otherCashierOrder = $this->createOrder('ORD-OTHER-CASHIER-1003', '123453');
        $otherCashierOrder->update(['cashier_id' => $otherCashier->user_id, 'status' => 'pending']);

        $this->actingAs($cashier)
            ->getJson(route('cashier.dashboard.orders', 'paid-today'))
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('orders.0.number', 'ORD-PAID-TODAY-1001')
            ->assertJsonPath('orders.0.items.0.name', 'Test curry')
            ->assertJsonPath('orders.0.items.0.quantity', 1);

        $this->getJson(route('cashier.dashboard.orders', 'pending'))
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('orders.0.number', 'ORD-PAID-TODAY-1001');
        $this->getJson(route('cashier.dashboard.orders', 'active'))
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('orders.0.number', 'ORD-PAID-TODAY-1001');
        $this->getJson(route('cashier.dashboard.orders', 'awaiting-payment'))
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('orders.0.number', 'ORD-AWAITING-1002');
        $this->getJson(route('cashier.dashboard.orders', 'invalid-area'))->assertNotFound();
    }

    public function test_only_administrators_can_review_transaction_requests(): void
    {
        $this->actingAs($this->createUser('cashier'))
            ->get(route('admin.transaction-requests.index'))
            ->assertRedirect(route('login'));
    }

    public function test_cashier_kitchen_and_manager_process_an_order_through_separate_roles(): void
    {
        $order = $this->createOrder();
        $cashier = $this->createUser('cashier');
        $kitchen = $this->createUser('kitchen');
        $admin = $this->createUser('admin');
        $order->update(['cashier_id' => $cashier->user_id, 'status' => 'pending']);

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

    private function createOrder(string $orderNumber = 'ORD-TEST-1001', string $code = '123456'): Order
    {
        $item = $this->createMenuItem();
        $order = Order::query()->create([
            'order_number' => $orderNumber,
            'code' => $code,
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
            'email' => $role . '-' . (User::query()->count() + 1) . '@example.test',
            'password' => 'password',
        ]);
    }
}
