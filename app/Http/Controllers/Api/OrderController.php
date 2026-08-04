<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Orders\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    /** @return JsonResponse 分頁訂單清單 */
    public function index(): JsonResponse
    {
        return response()->json(Order::with('items')->latest()->cursorPaginate(20));
    }

    /**
     * @param  Order  $order  路由模型綁定的訂單
     * @return JsonResponse 訂單完整資料
     */
    public function show(Order $order): JsonResponse
    {
        return response()->json($order->load(['items', 'payments', 'shipments']));
    }

    /**
     * @param  Request  $request  建立訂單所需資料
     * @param  OrderService  $orders  訂單交易服務
     * @return JsonResponse 新建立的訂單
     */
    public function store(Request $request, OrderService $orders): JsonResponse
    {
        $data = $request->validate(['customer_email' => ['required', 'email'], 'currency' => ['sometimes', 'string', 'size:3'], 'shipping_fee' => ['sometimes', 'integer', 'min:0'], 'shipping_address' => ['required', 'array'], 'shipping_address.recipient' => ['required', 'string'], 'shipping_address.phone' => ['required', 'string'], 'shipping_address.address' => ['required', 'string'], 'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'], 'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99']]);

        return response()->json($orders->create($data), 201);
    }

    /**
     * @param  Order  $order  路由模型綁定的訂單
     * @param  OrderService  $orders  訂單交易服務
     * @return JsonResponse 取消後的訂單
     */
    public function cancel(Order $order, OrderService $orders): JsonResponse
    {
        return response()->json($orders->cancel($order));
    }
}
