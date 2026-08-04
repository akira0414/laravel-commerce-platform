<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWebhookEvent;
use App\Models\WebhookEvent;
use App\Support\Webhooks\WebhookSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentWebhookController extends Controller
{
    /**
     * 驗證金流 Webhook 簽章並保存事件。
     *
     * @param  Request  $request  第三方金流原始請求
     * @param  string  $provider  金流供應商代號
     * @param  WebhookSignature  $signature  HMAC 與 timestamp 驗證器
     * @return JsonResponse Webhook 接收結果
     */
    public function __invoke(Request $request, string $provider, WebhookSignature $signature): JsonResponse
    {
        $signature->verify($request, (string) config('webhooks.payments.secret'));

        return $this->accept($request, $provider);
    }

    /**
     * 將付款事件寫入 inbox，並以冪等方式派送背景工作。
     *
     * @param  Request  $request  已通過簽章驗證的請求
     * @param  string  $provider  金流供應商代號
     * @return JsonResponse 事件接收結果
     */
    private function accept(Request $request, string $provider): JsonResponse
    {
        $payload = $request->validate(['id' => ['required', 'string', 'max:255'], 'type' => ['required', 'in:payment.succeeded,payment.failed'], 'data.order_number' => ['required', 'string'], 'data.payment_id' => ['required', 'string'], 'data.method' => ['nullable', 'string', 'max:50'], 'data.amount' => ['required', 'integer', 'min:0'], 'data.currency' => ['required', 'string', 'size:3']]);
        $payloadHash = hash('sha256', $request->getContent());
        $event = WebhookEvent::firstOrCreate(['provider' => $provider, 'event_id' => $payload['id']], ['topic' => $payload['type'], 'payload_hash' => $payloadHash, 'payload' => $payload['data'], 'status' => 'pending', 'attempts' => 0]);
        // 相同 ID、不同內容不是合法重送，必須拒絕而不能默默沿用第一筆事件。
        abort_if(! $event->wasRecentlyCreated && ! hash_equals($event->payload_hash, $payloadHash), 409, 'Webhook event ID was reused with a different payload.');
        if ($event->wasRecentlyCreated) {
            if (app()->environment('local', 'testing') && $request->boolean('simulator_sync')) {
                ProcessWebhookEvent::dispatchSync($event->id);
            } else {
                ProcessWebhookEvent::dispatch($event->id)->afterCommit();
            }
        }

        return response()->json(['received' => true], 202);
    }
}
