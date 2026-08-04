<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PaymentSimulatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_pay_owned_order_through_simulator(): void
    {
        [$customer, $order, $product] = $this->pendingOrder();

        $this->actingAs($customer)->post(route('payments.confirm', $order), [
            'method' => 'credit_card',
            'outcome' => 'success',
        ])->assertRedirect(route('payments.show', $order));

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'credit_card',
            'status' => PaymentStatus::Succeeded->value,
        ]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Paid->value]);
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'on_hand' => 8, 'reserved' => 0]);
    }

    public function test_local_simulator_api_runs_signed_webhook_flow(): void
    {
        [, $order] = $this->pendingOrder();

        $this->postJson('/api/v1/simulator/payments', [
            'order_number' => $order->number,
            'method' => 'mobile_payment',
            'outcome' => 'success',
        ])->assertAccepted()
            ->assertJsonPath('event_status', 'processed')
            ->assertJsonPath('order_status', OrderStatus::Paid->value);
    }

    /** @return array{User, Order, Product} */
    private function pendingOrder(): array
    {
        $customer = User::create(['name' => 'Customer', 'email' => 'customer@example.com', 'password' => 'password', 'role' => User::ROLE_CUSTOMER]);
        $product = Product::create(['sku' => 'PAY-SKU', 'name' => 'Payment Demo', 'price' => 10000, 'is_active' => true]);
        $product->inventory()->create(['on_hand' => 10, 'reserved' => 0]);
        $response = $this->postJson('/api/v1/orders', [
            'customer_email' => $customer->email,
            'shipping_address' => ['recipient' => 'Customer', 'phone' => '0912345678', 'address' => 'Taipei'],
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertCreated();

        return [$customer, Order::findOrFail($response->json('id')), $product];
    }
}
