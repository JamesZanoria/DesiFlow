<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\OrderMonitorController;
use App\Http\Controllers\Admin\SmartAssistantController;
use App\Http\Controllers\Admin\NotificationsController;
use App\Http\Controllers\Admin\ProtectedFileController;
use App\Http\Controllers\Admin\TransactionRequestController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\CashierPaymentController;
use App\Http\Controllers\KdsController;
use App\Http\Controllers\PublicMenuImageController;

Route::get('/', function (Request $request) {
    $user = $request->user();

    if (! $user) {
        return redirect()->route('login');
    }

    $landingRoute = match ($user->role) {
        'admin' => 'admin.dashboard.index',
        'cashier' => 'cashier.index',
        'kitchen' => 'kds.index',
        default => abort(403),
    };

    return redirect()->route($landingRoute);
});

// Public self-ordering / pickup-code status tracking.
Route::get('/menu', [CustomerOrderingController::class, 'menu'])->name('customer.menu');
Route::get('/api/products', [CustomerOrderingController::class, 'products'])->name('products.get');
Route::get('/api/cart', [CustomerOrderingController::class, 'cart'])->name('cart.get');
Route::post('/api/cart/add', [CustomerOrderingController::class, 'addToCart'])->name('cart.add');
Route::post('/api/cart/remove', [CustomerOrderingController::class, 'removeFromCart'])->name('cart.remove');
Route::post('/api/cart/update', [CustomerOrderingController::class, 'updateCart'])->name('cart.update');
Route::post('/api/orders/submit', [CustomerOrderingController::class, 'submit'])->name('orders.submit');
Route::post('/api/orders/details', [CustomerOrderingController::class, 'details'])->name('orders.details');
Route::get('/api/orders/{orderNumber}/status', [CustomerOrderingController::class, 'status'])->name('orders.status');
Route::get('/menu-images/{filename}', [PublicMenuImageController::class, 'show'])
    ->where('filename', '[A-Za-z0-9_-]+\.(jpg|jpeg|png)')
    ->name('customer.menu-image');

// Guest-only: an already-logged-in admin hitting /login gets redirected
// forward instead of seeing the form again. There is no registration route,
// every staff account is created by an admin through Staff Management.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::get('/login/cashier', [AuthController::class, 'showLoginForm'])->name('login.cashier');
    Route::get('/login/kitchen', [AuthController::class, 'showLoginForm'])->name('login.kitchen');
    Route::post('/login', [AuthController::class, 'login']);

    // Password Reset Flow
    Route::get('/forgot-password', [AuthController::class, 'showResetForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/files/{type}/{filename}', [ProtectedFileController::class, 'show'])
        ->name('files.show')
        ->where('type', 'menu-items|ingredients')
        ->where('filename', '[A-Za-z0-9_-]+\.(jpg|jpeg|png)');
});

Route::prefix('cashier')->name('cashier.')->middleware(['auth', 'role:cashier'])->group(function () {
    Route::get('/', [CashierController::class, 'index'])->name('index');
    Route::get('/dashboard/orders/{area}', [CashierController::class, 'dashboardOrders'])->name('dashboard.orders');
    Route::get('/orders', [CashierController::class, 'activeOrders'])->name('orders.index');
    Route::get('/orders/{order}/quick-view', [CashierController::class, 'quickView'])->name('orders.quick-view');
    Route::get('/menu', [CashierController::class, 'menu'])->name('menu');
    Route::get('/cart', [CashierController::class, 'cart'])->name('cart');
    Route::post('/cart/add', [CashierController::class, 'addToCart'])->name('cart.add');
    Route::post('/cart/update', [CashierController::class, 'updateCart'])->name('cart.update');
    Route::post('/orders', [CashierController::class, 'storeOrder'])->name('orders.store');
    Route::get('/orders/{order}/payment', [CashierPaymentController::class, 'create'])->name('orders.payment');
    Route::post('/orders/{order}/payment', [CashierPaymentController::class, 'store'])->name('orders.payment.store');
    Route::get('/orders/{order}/receipt', [CashierPaymentController::class, 'receipt'])->name('receipts.show');
    Route::get('/transactions', [CashierPaymentController::class, 'history'])->name('transactions.index');
    Route::get('/transactions/{payment}', [CashierPaymentController::class, 'showTransaction'])->name('transactions.show');
    Route::post('/transactions/{payment}/requests', [CashierPaymentController::class, 'requestTransactionAction'])->name('transactions.requests.store');
});

Route::prefix('kds')->name('kds.')->middleware(['auth', 'role:kitchen'])->group(function () {
    Route::get('/', [KdsController::class, 'index'])->name('index');
    Route::patch('/orders/{order}/status', [KdsController::class, 'updateStatus'])->name('orders.status');
});

// Admin-only management and reporting modules.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {

    Route::get('/', function() {
        return redirect()->route('admin.dashboard.index');
    });

    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard.index');

    // Menu Management
    Route::get('/menu/create', [MenuController::class, 'create'])->name('menu.create');
    Route::post('/menu/save-draft', [MenuController::class, 'saveDraft'])->name('menu.save-draft');
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::get('/menu/{menuItem}', [MenuController::class, 'show'])->name('menu.show');
    Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
    Route::get('/menu/{menuItem}/edit', [MenuController::class, 'edit'])->name('menu.edit');
    Route::put('/menu/{menuItem}', [MenuController::class, 'update'])->name('menu.update');
    Route::delete('/menu/{menuItem}', [MenuController::class, 'destroy'])->name('menu.destroy');

    Route::put('/inventory/{ingredient}/waste-expired', [InventoryController::class, 'wasteExpired'])->name('inventory.waste-expired');
    Route::get('/inventory/export', [InventoryController::class, 'export'])->name('inventory.export');
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/create', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/ledger', [InventoryController::class, 'ledgerIndex'])->name('inventory.ledger');
    Route::get('/inventory/{ingredient}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
    Route::put('/inventory/{ingredient}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{ingredient}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
    Route::post('/inventory/{ingredient}/movement', [InventoryController::class, 'recordMovement'])->name('inventory.movement');
    Route::get('/inventory/{ingredient}/has-opening-balance', [InventoryController::class, 'hasOpeningBalance'])->name('inventory.hasOpeningBalance');
    Route::put('/inventory/movement/{movement}', [InventoryController::class, 'updateMovement'])->name('inventory.movement.update');
    Route::delete('/inventory/movement/{movement}', [InventoryController::class, 'destroyMovement'])->name('inventory.movement.destroy');
    Route::get('/inventory/{ingredient}/history', [InventoryController::class, 'history'])->name('inventory.history');

    // Staff Management
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::get('/staff/{user}/edit', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/{user}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{user}', [StaffController::class, 'destroy'])->name('staff.destroy');

    // Real-Time Order Monitoring
    Route::get('/orders', [OrderMonitorController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderMonitorController::class, 'show'])->name('orders.show');

    // Void and refund approval audit trail
    Route::get('/transaction-requests', [TransactionRequestController::class, 'index'])->name('transaction-requests.index');
    Route::post('/transaction-requests/{transactionRequest}/review', [TransactionRequestController::class, 'review'])->name('transaction-requests.review');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Smart Assistant
    Route::get('/assistant', [SmartAssistantController::class, 'index'])->name('assistant.index');
    Route::post('/assistant/ask', [SmartAssistantController::class, 'ask'])->name('assistant.ask');

    // Notifications (bell dropdown, shared layout)
    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-read', [NotificationsController::class, 'markAllAsRead'])->name('notifications.markRead');

});