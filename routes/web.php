<?php

use App\Http\Controllers\AdminNetworkController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OwnerOrderController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoreController::class, 'home'])->name('store.home');
Route::get('/owner', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'))->name('owner.entry');
Route::get('/networks/{network:slug}', [StoreController::class, 'show'])->name('store.network');
Route::post('/checkout/start', [StoreController::class, 'startCheckout'])->name('checkout.start');
Route::get('/checkout/{network:slug}', [StoreController::class, 'checkout'])->name('checkout.show');
Route::post('/checkout/{network:slug}/receipt-signature', [OrderController::class, 'receiptSignature'])->middleware('throttle:20,1')->name('checkout.receipt-signature');
Route::post('/checkout/{network:slug}', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('orders.store');
Route::get('/track', [OrderController::class, 'recoverForm'])->name('orders.track');
Route::post('/track', [OrderController::class, 'recover'])->middleware('throttle:10,1')->name('orders.track.submit');
Route::get('/recover', [OrderController::class, 'recoverForm'])->name('orders.recover');
Route::post('/recover', [OrderController::class, 'recover'])->middleware('throttle:10,1')->name('orders.recover.submit');
Route::get('/orders/{order:access_token}', [OrderController::class, 'show'])->middleware('signed:relative')->name('orders.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/owner/login', [AuthController::class, 'showLogin'])->name('owner.login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:network_owner,super_admin'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('dashboard')->name('dashboard.')->group(function (): void {
        Route::get('/orders', [OwnerOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OwnerOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/approve', [OwnerOrderController::class, 'approve'])->name('orders.approve');
        Route::post('/orders/{order}/reject', [OwnerOrderController::class, 'reject'])->name('orders.reject');

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/packages', [InventoryController::class, 'storePackage'])->name('packages.store');
        Route::patch('/packages/{package}', [InventoryController::class, 'updatePackage'])->name('packages.update');
        Route::delete('/packages/{package}', [InventoryController::class, 'destroyPackage'])->name('packages.destroy');
        Route::post('/cards/import', [InventoryController::class, 'importCards'])->name('cards.import');
        Route::post('/payment-settings', [InventoryController::class, 'updatePayment'])->name('payment.update');

        Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    });
});

Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/networks', [AdminNetworkController::class, 'index'])->name('networks.index');
    Route::get('/networks/create', [AdminNetworkController::class, 'create'])->name('networks.create');
    Route::post('/networks', [AdminNetworkController::class, 'store'])->name('networks.store');
});
