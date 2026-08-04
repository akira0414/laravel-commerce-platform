<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\InsufficientInventory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_creation_reserves_stock_and_snapshots_price(): void
    {
        $product = Product::create(['sku' => 'SKU-1', 'name' => 'Demo', 'price' => 12000, 'is_active' => true]);
        $product->inventory()->create(['on_hand' => 10, 'reserved' => 0]);
        $response = $this->postJson('/api/v1/orders', ['customer_email' => 'buyer@example.com', 'shipping_address' => ['recipient' => 'Demo User', 'phone' => '0912345678', 'address' => 'Taipei'], 'items' => [['product_id' => $product->id, 'quantity' => 2]]]);
        $response->assertCreated()->assertJsonPath('status', OrderStatus::PendingPayment->value)->assertJsonPath('total', 24000);
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'on_hand' => 10, 'reserved' => 2]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'unit_price' => 12000, 'quantity' => 2]);
    }

    public function test_order_is_rejected_when_available_stock_is_insufficient(): void
    {
        $product = Product::create(['sku' => 'SKU-2', 'name' => 'Rare', 'price' => 900, 'is_active' => true]);
        $product->inventory()->create(['on_hand' => 1, 'reserved' => 0]);
        $this->withoutExceptionHandling();
        $this->expectException(InsufficientInventory::class);
        $this->postJson('/api/v1/orders', ['customer_email' => 'buyer@example.com', 'shipping_address' => ['recipient' => 'Demo', 'phone' => '0900', 'address' => 'Taipei'], 'items' => [['product_id' => $product->id, 'quantity' => 2]]]);
    }
}
