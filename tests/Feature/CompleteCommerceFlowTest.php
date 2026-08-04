<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompleteCommerceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_and_pays_order_in_one_request(): void
    {
        [$customer, $product] = $this->shopperAndProduct();
        $response = $this->actingAs($customer)->postJson('/checkout', $this->checkoutPayload($product));

        $response->assertCreated()->assertJsonPath('status', OrderStatus::Paid->value);
        $this->assertDatabaseHas('payments', ['order_id' => $response->json('id'), 'method' => 'credit_card', 'status' => PaymentStatus::Succeeded->value]);
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'on_hand' => 8, 'reserved' => 0]);
    }

    public function test_customer_can_cancel_paid_order_and_restore_inventory(): void
    {
        [$customer, $product] = $this->shopperAndProduct();
        $orderId = $this->actingAs($customer)->postJson('/checkout', $this->checkoutPayload($product))->json('id');

        $this->actingAs($customer)->post(route('account.orders.cancel', $orderId))->assertRedirect(route('account'));

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => OrderStatus::Cancelled->value]);
        $this->assertDatabaseHas('payments', ['order_id' => $orderId, 'status' => PaymentStatus::Refunded->value]);
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'on_hand' => 10, 'reserved' => 0]);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $orderId, 'to_status' => OrderStatus::Cancelled->value]);
    }

    public function test_admin_can_accept_ship_and_deliver_an_order(): void
    {
        [$customer, $product] = $this->shopperAndProduct();
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password', 'role' => User::ROLE_ADMIN]);
        $order = Order::findOrFail($this->actingAs($customer)->postJson('/checkout', $this->checkoutPayload($product))->json('id'));

        $this->actingAs($admin)->post(route('engineering.orders.accept', $order))->assertRedirect();
        $this->assertSame(OrderStatus::Fulfillment, $order->refresh()->status);
        $this->actingAs($admin)->post(route('engineering.orders.ship', $order))->assertRedirect();
        $this->assertSame(OrderStatus::Shipped, $order->refresh()->status);
        $this->actingAs($admin)->post(route('engineering.orders.deliver', $order))->assertRedirect();
        $this->assertSame(OrderStatus::Delivered, $order->refresh()->status);
        $this->assertNotNull($order->shipments()->firstOrFail()->tracking_number);
        $this->assertSame(5, $order->statusHistories()->count());
    }

    /** @return array{User, Product} */
    private function shopperAndProduct(): array
    {
        $customer = User::create(['name' => 'Customer', 'email' => 'customer@example.com', 'password' => 'password', 'role' => User::ROLE_CUSTOMER]);
        $product = Product::create(['sku' => 'FLOW-SKU', 'name' => 'Flow Demo', 'price' => 10000, 'is_active' => true]);
        $product->inventory()->create(['on_hand' => 10, 'reserved' => 0]);

        return [$customer, $product];
    }

    /** @return array<string, mixed> */
    private function checkoutPayload(Product $product): array
    {
        return [
            'shipping_address' => ['recipient' => 'Customer', 'phone' => '0912345678', 'address' => 'Taipei'],
            'payment_method' => 'credit_card',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ];
    }
}
