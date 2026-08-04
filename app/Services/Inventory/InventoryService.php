<?php
namespace App\Services\Inventory;
use App\Exceptions\InsufficientInventory;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
final class InventoryService
{
    /** @param array<int, array{product_id:int, quantity:int}> $items */
    public function reserve(array $items): void
    {
        collect($items)->sortBy('product_id')->each(function (array $item): void {
            $inventory = Inventory::query()->where('product_id', $item['product_id'])->lockForUpdate()->firstOrFail();
            if ($inventory->available() < $item['quantity']) {
                $sku = Product::find($item['product_id'])?->sku ?? (string) $item['product_id'];
                throw new InsufficientInventory("Insufficient inventory for {$sku}");
            }
            $inventory->increment('reserved', $item['quantity']);
        });
    }

    public function commit(Order $order): void
    {
        $order->items()->orderBy('product_id')->get()->each(function ($item): void {
            $inventory = Inventory::query()->where('product_id', $item->product_id)->lockForUpdate()->firstOrFail();
            $inventory->decrement('reserved', $item->quantity);
            $inventory->decrement('on_hand', $item->quantity);
        });
    }

    public function release(Order $order): void
    {
        $order->items()->orderBy('product_id')->get()->each(function ($item): void {
            Inventory::query()->where('product_id', $item->product_id)->lockForUpdate()->firstOrFail()->decrement('reserved', $item->quantity);
        });
    }
}
