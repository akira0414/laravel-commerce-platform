<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWebhookEvent;
use App\Models\WebhookEvent;
use App\Support\Webhooks\WebhookSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShippingWebhookController extends Controller
{
    /**
     * 驗證物流 Webhook，保存冪等事件並派送背景工作。
     *
     * @param  Request  $request  第三方物流原始請求
     * @param  string  $provider  物流供應商代號
     * @param  WebhookSignature  $signature  HMAC 與 timestamp 驗證器
     * @return JsonResponse Webhook 接收結果
     */
    public function __invoke(Request $request, string $provider, WebhookSignature $signature): JsonResponse
    {
        $signature->verify($request, (string) config('webhooks.shipping.secret'));
        $payload = $request->validate(['id' => ['required', 'string', 'max:255'], 'type' => ['required', 'in:shipment.ready,shipment.in_transit,shipment.delivered,shipment.exception'], 'data.order_number' => ['required', 'string'], 'data.shipment_id' => ['required', 'string'], 'data.tracking_number' => ['nullable', 'string']]);
        $payloadHash = hash('sha256', $request->getContent());
        $event = WebhookEvent::firstOrCreate(['provider' => $provider, 'event_id' => $payload['id']], ['topic' => $payload['type'], 'payload_hash' => $payloadHash, 'payload' => $payload['data'], 'status' => 'pending', 'attempts' => 0]);
        // Event ID 是冪等鍵，payload hash 則防止相同鍵被不同內容覆用。
        abort_if(! $event->wasRecentlyCreated && ! hash_equals($event->payload_hash, $payloadHash), 409, 'Webhook event ID was reused with a different payload.');
        if ($event->wasRecentlyCreated) {
            ProcessWebhookEvent::dispatch($event->id)->afterCommit();
        }

        return response()->json(['received' => true], 202);
    }
}
