<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_protected_pages(): void
    {
        $this->get('/account')->assertRedirect('/login');
        $this->get('/engineering')->assertRedirect('/login');
    }

    public function test_customer_and_admin_are_redirected_to_their_own_area_after_login(): void
    {
        User::create(['name' => 'Customer', 'email' => 'customer@example.com', 'password' => 'password', 'role' => User::ROLE_CUSTOMER]);
        User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password', 'role' => User::ROLE_ADMIN]);

        $this->post('/login', ['email' => 'customer@example.com', 'password' => 'password'])->assertRedirect('/account');
        $this->post('/logout')->assertRedirect('/');
        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password'])->assertRedirect('/engineering');
    }

    public function test_roles_cannot_open_each_others_protected_area(): void
    {
        $customer = User::create(['name' => 'Customer', 'email' => 'customer@example.com', 'password' => 'password', 'role' => User::ROLE_CUSTOMER]);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password', 'role' => User::ROLE_ADMIN]);

        $this->actingAs($customer)->get('/engineering')->assertForbidden();
        $this->actingAs($admin)->get('/account')->assertForbidden();
    }

    public function test_authenticated_customer_checkout_uses_the_account_email(): void
    {
        $customer = User::create(['name' => 'Customer', 'email' => 'customer@example.com', 'password' => 'password', 'role' => User::ROLE_CUSTOMER]);
        $product = Product::create(['sku' => 'AUTH-SKU', 'name' => 'Secure Checkout', 'price' => 10000, 'is_active' => true]);
        $product->inventory()->create(['on_hand' => 5, 'reserved' => 0]);

        $this->actingAs($customer)->postJson('/checkout', [
            'shipping_address' => ['recipient' => 'Customer', 'phone' => '0912345678', 'address' => 'Taipei'],
            'payment_method' => 'credit_card',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertCreated()
            ->assertJsonPath('customer_email', 'customer@example.com')
            ->assertJsonPath('status', 'paid');

        $this->assertDatabaseHas('orders', ['customer_email' => 'customer@example.com']);
    }
}
