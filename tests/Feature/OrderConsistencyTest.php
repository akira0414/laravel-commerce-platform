<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Services\Payments\PaymentWebhookService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrderConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_an_order_releases_reserved_inventory(): void
    {
        [$product, $order] = $this->createPendingOrder(quantity: 3);

        $this->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('status', OrderStatus::Cancelled->value);

        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'on_hand' => 10,
            'reserved' => 0,
        ]);
    }

    public function test_successful_payment_atomically_commits_reserved_inventory(): void
    {
        [$product, $order] = $this->createPendingOrder(quantity: 2);

        app(PaymentWebhookService::class)->handle('demo', 'payment.succeeded', [
            'order_number' => $order->number,
            'payment_id' => 'pay_success_1',
            'amount' => $order->total,
            'currency' => $order->currency,
        ]);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Paid->value]);
        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'on_hand' => 8,
            'reserved' => 0,
        ]);
    }

    public function test_payment_amount_mismatch_rolls_back_without_touching_inventory(): void
    {
        [$product, $order] = $this->createPendingOrder(quantity: 2);

        try {
            app(PaymentWebhookService::class)->handle('demo', 'payment.succeeded', [
                'order_number' => $order->number,
                'payment_id' => 'pay_tampered_1',
                'amount' => $order->total + 1,
                'currency' => $order->currency,
            ]);
            $this->fail('A mismatched payment must be rejected.');
        } catch (DomainException $exception) {
            $this->assertSame('Payment amount or currency mismatch.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('payments', ['provider_payment_id' => 'pay_tampered_1']);
        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'on_hand' => 10,
            'reserved' => 2,
        ]);
    }

    public function test_expired_unpaid_order_is_cancelled_and_inventory_is_released(): void
    {
        [$product, $order] = $this->createPendingOrder(quantity: 4);
        $order->update(['expires_at' => now()->subMinute()]);

        $this->artisan('orders:release-expired')->assertSuccessful();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Cancelled->value]);
        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'on_hand' => 10,
            'reserved' => 0,
        ]);
    }

    /** @return array{Product, Order} */
    private function createPendingOrder(int $quantity): array
    {
        $product = Product::create(['sku' => 'DEMO-SKU', 'name' => 'Demo Product', 'price' => 12500, 'is_active' => true]);
        $product->inventory()->create(['on_hand' => 10, 'reserved' => 0]);

        $response = $this->postJson('/api/v1/orders', [
            'customer_email' => 'buyer@example.com',
            'shipping_address' => ['recipient' => 'Demo User', 'phone' => '0912345678', 'address' => 'Taipei'],
            'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
        ])->assertCreated();

        return [$product, Order::findOrFail($response->json('id'))];
    }
}
