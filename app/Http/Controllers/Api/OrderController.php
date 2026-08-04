<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Orders\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
final class OrderController extends Controller
{
    public function index(): JsonResponse { return response()->json(Order::with('items')->latest()->cursorPaginate(20)); }
    public function show(Order $order): JsonResponse { return response()->json($order->load(['items', 'payments', 'shipments'])); }
    public function store(Request $request, OrderService $orders): JsonResponse
    {
        $data = $request->validate(['customer_email' => ['required', 'email'], 'currency' => ['sometimes', 'string', 'size:3'], 'shipping_fee' => ['sometimes', 'integer', 'min:0'], 'shipping_address' => ['required', 'array'], 'shipping_address.recipient' => ['required', 'string'], 'shipping_address.phone' => ['required', 'string'], 'shipping_address.address' => ['required', 'string'], 'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'], 'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        return response()->json($orders->create($data), 201);
    }
    public function cancel(Order $order, OrderService $orders): JsonResponse { return response()->json($orders->cancel($order)); }
}
