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

    /** 將付款成功訂單的保留量轉為實際扣庫存。 */
    public function commit(Order $order): void
    {
        $order->items()->orderBy('product_id')->get()->each(function ($item): void {
            $inventory = Inventory::query()->where('product_id', $item->product_id)->lockForUpdate()->firstOrFail();
            $inventory->decrement('reserved', $item->quantity);
            $inventory->decrement('on_hand', $item->quantity);
        });
    }

    /** 釋放未付款取消訂單所占用的保留量。 */
    public function release(Order $order): void
    {
        $order->items()->orderBy('product_id')->get()->each(function ($item): void {
            Inventory::query()->where('product_id', $item->product_id)->lockForUpdate()->firstOrFail()->decrement('reserved', $item->quantity);
        });
    }

    /** 將已付款訂單取消後的商品數量回補至現有庫存。 */
    public function restore(Order $order): void
    {
        $order->items()->orderBy('product_id')->get()->each(function ($item): void {
            Inventory::query()->where('product_id', $item->product_id)->lockForUpdate()->firstOrFail()->increment('on_hand', $item->quantity);
        });
    }
}
