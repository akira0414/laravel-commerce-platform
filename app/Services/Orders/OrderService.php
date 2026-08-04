<?php
namespace App\Services\Orders;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
final readonly class OrderService
{
    public function __construct(private InventoryService $inventory, private OrderStateMachine $states) {}

    /** @param array{customer_email:string,currency?:string,shipping_fee?:int,shipping_address:array,items:array<int,array{product_id:int,quantity:int}>} $data */
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data): Order {
            $products = Product::query()->whereIn('id', collect($data['items'])->pluck('product_id'))->where('is_active', true)->get()->keyBy('id');
            abort_if($products->count() !== count($data['items']), 422, 'One or more products are unavailable.');
            $this->inventory->reserve($data['items']);
            $subtotal = collect($data['items'])->sum(fn (array $item) => $products[$item['product_id']]->price * $item['quantity']);
            $shipping = (int) ($data['shipping_fee'] ?? 0);
            $order = Order::create(['number' => 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)), 'status' => OrderStatus::PendingPayment, 'currency' => $data['currency'] ?? 'TWD', 'subtotal' => $subtotal, 'shipping_fee' => $shipping, 'total' => $subtotal + $shipping, 'customer_email' => $data['customer_email'], 'shipping_address' => $data['shipping_address'], 'expires_at' => now()->addMinutes(15)]);
            foreach ($data['items'] as $item) {
                $product = $products[$item['product_id']];
                $order->items()->create(['product_id' => $product->id, 'sku' => $product->sku, 'name' => $product->name, 'unit_price' => $product->price, 'quantity' => $item['quantity'], 'line_total' => $product->price * $item['quantity']]);
            }
            return $order->load('items');
        }, 3);
    }

    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $this->states->transition($order, OrderStatus::Cancelled);
            $this->inventory->release($order);
            return $order->refresh();
        }, 3);
    }
}
