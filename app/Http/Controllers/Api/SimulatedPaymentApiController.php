<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\PaymentSimulatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class SimulatedPaymentApiController extends Controller
{
    /**
     * 執行僅限本機與測試環境的模擬付款 API。
     *
     * @param  Request  $request  訂單編號、付款方式與模擬結果
     * @param  PaymentSimulatorService  $simulator  模擬金流服務
     * @return JsonResponse Webhook 事件與訂單處理結果
     */
    public function __invoke(Request $request, PaymentSimulatorService $simulator): JsonResponse
    {
        abort_unless(app()->environment('local', 'testing'), 404);
        $data = $request->validate([
            'order_number' => ['required', 'string', 'exists:orders,number'],
            'method' => ['required', Rule::in(array_keys(config('payments.methods')))],
            'outcome' => ['required', Rule::in(['success', 'failed'])],
        ]);
        $order = Order::where('number', $data['order_number'])->firstOrFail();
        abort_if($order->status !== OrderStatus::PendingPayment, 409, 'Only pending orders can be simulated.');
        $event = $simulator->simulate($order, $data['method'], $data['outcome']);

        return response()->json([
            'provider' => config('payments.provider'),
            'event_id' => $event->event_id,
            'event_status' => $event->status,
            'order_number' => $order->number,
            'order_status' => $order->refresh()->status->value,
        ], 202);
    }
}
