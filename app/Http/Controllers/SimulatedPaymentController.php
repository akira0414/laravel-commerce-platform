<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Payments\PaymentSimulatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class SimulatedPaymentController extends Controller
{
    /**
     * 顯示舊有的獨立付款測試頁，供工程測試與失敗情境重現。
     *
     * @param  Request  $request  目前登入顧客的請求
     * @param  Order  $order  路由模型綁定的訂單
     * @return View 模擬付款畫面
     */
    public function show(Request $request, Order $order): View
    {
        $this->assertOwnedByCustomer($request, $order);

        return view('payments.simulator', ['order' => $order->load(['items', 'payments']), 'methods' => config('payments.methods'), 'apiBaseUrl' => config('payments.api_base_url'), 'webhookUrl' => config('payments.webhook_url')]);
    }

    /**
     * 送出獨立付款測試頁的模擬結果。
     *
     * @param  Request  $request  付款方式與模擬結果
     * @param  Order  $order  路由模型綁定的訂單
     * @param  PaymentSimulatorService  $simulator  模擬金流服務
     * @return RedirectResponse 返回付款測試頁
     */
    public function confirm(Request $request, Order $order, PaymentSimulatorService $simulator): RedirectResponse
    {
        $this->assertOwnedByCustomer($request, $order);
        abort_if($order->status !== OrderStatus::PendingPayment, 409, '只有等待付款的訂單可以執行模擬付款。');
        $data = $request->validate(['method' => ['required', Rule::in(array_keys(config('payments.methods')))], 'outcome' => ['required', Rule::in(['success', 'failed'])]]);
        $simulator->simulate($order, $data['method'], $data['outcome']);

        return redirect()->route('payments.show', $order)->with('payment_result', $data['outcome']);
    }

    /**
     * 確認訂單屬於目前登入顧客。
     *
     * @param  Request  $request  目前登入顧客的請求
     * @param  Order  $order  欲查看或付款的訂單
     */
    private function assertOwnedByCustomer(Request $request, Order $order): void
    {
        abort_unless($order->customer_email === $request->user()->email, 403, '你沒有權限操作這筆訂單。');
    }
}
