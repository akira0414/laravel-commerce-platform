<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_renders_products_and_checkout_controls(): void
    {
        $product = Product::create(['sku' => 'PORTFOLIO-1', 'name' => 'Portfolio Product', 'price' => 79000, 'is_active' => true]);
        $product->inventory()->create(['on_hand' => 20, 'reserved' => 3]);

        $this->get('/')
            ->assertOk()
            ->assertSee('讓日常')
            ->assertSee('PORTFOLIO-1')
            ->assertSee('加入購物車')
            ->assertSee('前往結帳');
    }

    public function test_engineering_dashboard_renders_inventory_and_failure_controls(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password', 'role' => User::ROLE_ADMIN]);
        $product = Product::create(['sku' => 'PORTFOLIO-2', 'name' => 'Engineering Product', 'price' => 59000, 'is_active' => true]);
        $product->inventory()->create(['on_hand' => 20, 'reserved' => 3]);

        $this->actingAs($admin)->get('/engineering')
            ->assertOk()
            ->assertSee('商店營運中心')
            ->assertSee('PORTFOLIO-2')
            ->assertSee('商品與庫存')
            ->assertSee('Webhook 事件收件匣');
    }
}
