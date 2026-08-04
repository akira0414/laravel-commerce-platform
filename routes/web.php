<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\SimulatedPaymentController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

// Storefront Route
Route::get('/', [StorefrontController::class, 'index'])->name('storefront');
Route::get('/cart', [CartController::class, 'index'])
    ->middleware(['auth', 'role:customer'])
    ->name('cart');

// Admin Dashboard Route
Route::get('/engineering', AdminDashboardController::class)
    ->middleware(['auth', 'role:admin'])
    ->name('engineering.dashboard');

// Admin Order Management Routes
Route::middleware(['auth', 'role:admin'])->prefix('engineering/orders')->group(function (): void {
    Route::post('/{order}/accept', [AdminOrderController::class, 'accept'])->name('engineering.orders.accept');
    Route::post('/{order}/ship', [AdminOrderController::class, 'ship'])->name('engineering.orders.ship');
    Route::post('/{order}/deliver', [AdminOrderController::class, 'deliver'])->name('engineering.orders.deliver');
});

// Customer and Payment Routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:10,1');
});

// Customer and Payment Routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/checkout', [CheckoutController::class, 'show'])->middleware('role:customer')->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('role:customer')->name('checkout.store');
    Route::get('/account', [CustomerOrderController::class, 'index'])->middleware('role:customer')->name('account');
    Route::post('/account/orders/{order}/cancel', [CustomerOrderController::class, 'cancel'])->middleware('role:customer')->name('account.orders.cancel');
    Route::get('/payments/{order}', [SimulatedPaymentController::class, 'show'])->middleware('role:customer')->name('payments.show');
    Route::post('/payments/{order}/confirm', [SimulatedPaymentController::class, 'confirm'])->middleware(['role:customer', 'throttle:10,1'])->name('payments.confirm');
});

// Documentation Route
Route::get('/docs/API.md', [DocumentationController::class, 'api'])->name('docs.api');
