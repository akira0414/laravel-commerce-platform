<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Orders\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CustomerOrderController extends Controller
{
    /**
     * 顯示目前登入顧客的訂單、付款與物流資訊。
     *
     * @param  Request  $request  目前登入顧客的 HTTP 請求
     * @return View 顧客訂單中心畫面
     */
    public function index(Request $request): View
    {
        return view('account', [
            'orders' => Order::query()
                ->with(['items', 'payments', 'shipments', 'statusHistories'])
                ->where('customer_email', $request->user()->email)
                ->latest()
                ->get(),
        ]);
    }

    /**
     * 取消目前登入顧客自己的訂單。
     *
     * @param  Request  $request  目前登入顧客的 HTTP 請求
     * @param  Order  $order  路由模型綁定的訂單
     * @param  OrderService  $orders  訂單交易服務
     * @return RedirectResponse 返回我的訂單頁並顯示結果
     */
    public function cancel(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        abort_unless($order->customer_email === $request->user()->email, 403);
        $orders->cancel($order);

        return redirect()->route('account')->with('status', '訂單已取消；若已付款，款項與庫存也已完成退款回補。');
    }
}
