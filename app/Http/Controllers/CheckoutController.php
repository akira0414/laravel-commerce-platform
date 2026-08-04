<?php

namespace App\Http\Controllers;

use App\Services\Orders\OrderService;
use App\Services\Payments\PaymentSimulatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class CheckoutController extends Controller
{
    /**
     * 顯示獨立的訂單確認與付款頁面。
     *
     * @return View 結帳付款畫面
     */
    public function show(): View
    {
        return view('checkout', ['methods' => config('payments.methods')]);
    }

    /**
     * 建立訂單並立即完成模擬付款。
     *
     * @param  Request  $request  顧客送出的收件資料、購物明細與付款方式
     * @param  OrderService  $orders  訂單建立服務
     * @param  PaymentSimulatorService  $simulator  本機金流模擬服務
     * @return JsonResponse 建立完成的訂單與付款結果
     */
    public function store(Request $request, OrderService $orders, PaymentSimulatorService $simulator): JsonResponse
    {
        $data = $request->validate([
            'shipping_address' => ['required', 'array'],
            'shipping_address.recipient' => ['required', 'string'],
            'shipping_address.phone' => ['required', 'string'],
            'shipping_address.address' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'payment_method' => ['required', Rule::in(array_keys(config('payments.methods', [])))],
        ]);
        $data['customer_email'] = $request->user()->email;

        $order = $orders->create($data);
        $simulator->simulate($order, $data['payment_method'], 'success');
        $order->refresh()->load(['items', 'payments']);

        return response()->json([
            ...$order->toArray(),
            'status_label' => $order->status->label(),
            'payment_method_label' => config('payments.methods.'.$data['payment_method']),
            'account_url' => route('account'),
        ], 201);
    }
}
