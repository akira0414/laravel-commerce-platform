<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@example.com'], ['name' => '展示管理員', 'password' => 'password', 'role' => User::ROLE_ADMIN]);
        User::updateOrCreate(['email' => 'customer@example.com'], ['name' => '展示顧客', 'password' => 'password', 'role' => User::ROLE_CUSTOMER]);

        collect([['TSHIRT-BLK-M', 'Black T-Shirt / M', 79000, 30], ['HOODIE-GRY-L', 'Grey Hoodie / L', 168000, 15], ['CAP-NVY', 'Navy Cap', 59000, 50]])->each(function (array $data): void {
            [$sku, $name, $price, $stock] = $data;
            $product = Product::updateOrCreate(['sku' => $sku], ['name' => $name, 'price' => $price, 'is_active' => true]);
            $product->inventory()->firstOrCreate([], ['on_hand' => $stock, 'reserved' => 0]);
        });
    }
}
