<?php

namespace App\Services\Payments;

use App\Http\Controllers\Webhooks\PaymentWebhookController;
use App\Jobs\ProcessWebhookEvent;
use App\Models\Order;
use App\Models\WebhookEvent;
use App\Services\Shipping\ShippingWebhookService;
use App\Support\Webhooks\WebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class PaymentSimulatorService
{
    public function __construct(
        private PaymentWebhookController $webhooks,
        private WebhookSignature $signatureVerifier,
        private PaymentWebhookService $payments,
        private ShippingWebhookService $shipping,
    ) {}

    public function simulate(Order $order, string $method, string $outcome): WebhookEvent
    {
        $eventId = 'evt_sim_'.Str::lower(Str::random(16));
        $body = json_encode([
            'id' => $eventId,
            'type' => $outcome === 'success' ? 'payment.succeeded' : 'payment.failed',
            'data' => [
                'order_number' => $order->number,
                'payment_id' => 'pay_sim_'.Str::lower(Str::random(16)),
                'method' => $method,
                'amount' => $order->total,
                'currency' => $order->currency,
            ],
        ], JSON_THROW_ON_ERROR);
        $timestamp = now()->timestamp;
        $request = Request::create(config('payments.webhook_url'), 'POST', ['simulator_sync' => '1'], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_X_WEBHOOK_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$body, (string) config('webhooks.payments.secret')),
        ], $body);

        // 模擬器仍走正式簽章驗證與 inbox；同步模式讓本機展示不必等待外部 worker。
        ($this->webhooks)($request, (string) config('payments.provider'), $this->signatureVerifier);
        $event = WebhookEvent::where('provider', config('payments.provider'))->where('event_id', $eventId)->firstOrFail();
        if (! $event->processed_at) {
            (new ProcessWebhookEvent($event->id))->handle($this->payments, $this->shipping);
        }

        return $event->refresh();
    }
}
