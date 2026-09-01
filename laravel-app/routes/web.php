<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\OrderMonitorController;
use App\Http\Controllers\Admin\SmartAssistantController;
use App\Http\Controllers\Admin\NotificationController;

Route::get('/', function () {
    return view('welcome');
});

// Guest-only: an already-logged-in admin hitting /login gets redirected
// forward instead of seeing the form again. There is no registration route,
// every staff account is created by an admin through Staff Management.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// 'role:admin', see App\Http\Middleware\EnsureUserHasRole. Cashier and
// Kitchen Staff accounts exist in the users table (Staff Management already
// supports creating them) but have no module of their own yet, so this whole
// prefix is admin-only for now; a future POS/KDS route group would use
// role:cashier / role:kitchen the same way.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {

    // Menu Management
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::get('/menu/create', [MenuController::class, 'create'])->name('menu.create');
    Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
    Route::get('/menu/{menuItem}/edit', [MenuController::class, 'edit'])->name('menu.edit');
    Route::put('/menu/{menuItem}', [MenuController::class, 'update'])->name('menu.update');
    Route::delete('/menu/{menuItem}', [MenuController::class, 'destroy'])->name('menu.destroy');

    // Inventory Management
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/create', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/ledger', [InventoryController::class, 'ledgerIndex'])->name('inventory.ledger');
    Route::get('/inventory/{ingredient}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
    Route::put('/inventory/{ingredient}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{ingredient}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
    Route::post('/inventory/{ingredient}/movement', [InventoryController::class, 'recordMovement'])->name('inventory.movement');
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

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Smart Assistant
    Route::get('/assistant', [SmartAssistantController::class, 'index'])->name('assistant.index');
    Route::post('/assistant/ask', [SmartAssistantController::class, 'ask'])->name('assistant.ask');

    // Notifications (bell dropdown, shared layout)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

});