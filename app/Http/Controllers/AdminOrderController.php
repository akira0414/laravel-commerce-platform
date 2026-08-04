<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Shipping\ShippingWebhookService;
use Illuminate\Http\RedirectResponse;

final class AdminOrderController extends Controller
{
    /**
     * 將已付款訂單標記為商店已接單並進入備貨。
     *
     * @param  Order  $order  路由模型綁定的訂單
     * @param  ShippingWebhookService  $shipping  物流狀態處理服務
     * @return RedirectResponse 返回後台訂單區塊
     */
    public function accept(Order $order, ShippingWebhookService $shipping): RedirectResponse
    {
        $this->advance($order, $shipping, 'shipment.ready');

        return $this->completed('訂單已接單，開始備貨。');
    }

    /**
     * 將備貨中的訂單標記為已交付物流商。
     *
     * @param  Order  $order  路由模型綁定的訂單
     * @param  ShippingWebhookService  $shipping  物流狀態處理服務
     * @return RedirectResponse 返回後台訂單區塊
     */
    public function ship(Order $order, ShippingWebhookService $shipping): RedirectResponse
    {
        $this->advance($order, $shipping, 'shipment.in_transit');

        return $this->completed('訂單已出貨。');
    }

    /**
     * 將運送中的訂單標記為已送達顧客。
     *
     * @param  Order  $order  路由模型綁定的訂單
     * @param  ShippingWebhookService  $shipping  物流狀態處理服務
     * @return RedirectResponse 返回後台訂單區塊
     */
    public function deliver(Order $order, ShippingWebhookService $shipping): RedirectResponse
    {
        $this->advance($order, $shipping, 'shipment.delivered');

        return $this->completed('訂單已完成送達。');
    }

    /**
     * 以統一的模擬物流資料推進訂單與出貨狀態。
     *
     * @param  Order  $order  欲推進狀態的訂單
     * @param  ShippingWebhookService  $shipping  物流狀態處理服務
     * @param  string  $topic  模擬物流事件名稱
     */
    private function advance(Order $order, ShippingWebhookService $shipping, string $topic): void
    {
        $shipping->handle('simulator', $topic, [
            'order_number' => $order->number,
            'shipment_id' => 'ship_sim_'.$order->id,
            'tracking_number' => 'SIM'.str_pad((string) $order->id, 10, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * 建立後台操作成功的重新導向回應。
     *
     * @param  string  $message  顯示給管理員的成功訊息
     * @return RedirectResponse 返回後台訂單區塊
     */
    private function completed(string $message): RedirectResponse
    {
        return redirect()->route('engineering.dashboard')->withFragment('orders')->with('status', $message);
    }
}
