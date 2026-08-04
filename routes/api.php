<?php
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use App\Http\Controllers\Webhooks\ShippingWebhookController;
use Illuminate\Support\Facades\Route;
Route::prefix('v1')->group(function (): void {
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::post('webhooks/payments/{provider}', PaymentWebhookController::class)->middleware('throttle:120,1');
    Route::post('webhooks/shipping/{provider}', ShippingWebhookController::class)->middleware('throttle:120,1');
});
