<?php
namespace Database\Seeders;
use App\Models\Product;
use Illuminate\Database\Seeder;
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        collect([['TSHIRT-BLK-M', 'Black T-Shirt / M', 79000, 30], ['HOODIE-GRY-L', 'Grey Hoodie / L', 168000, 15], ['CAP-NVY', 'Navy Cap', 59000, 50]])->each(function (array $data): void { [$sku, $name, $price, $stock] = $data; $product = Product::updateOrCreate(['sku' => $sku], ['name' => $name, 'price' => $price, 'is_active' => true]); $product->inventory()->updateOrCreate([], ['on_hand' => $stock, 'reserved' => 0]); });
    }
}
