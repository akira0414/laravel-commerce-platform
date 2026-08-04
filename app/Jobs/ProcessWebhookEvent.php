<?php
namespace App\Jobs;
use App\Models\WebhookEvent;
use App\Services\Payments\PaymentWebhookService;
use App\Services\Shipping\ShippingWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
final class ProcessWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 5;
    /** @var int[] */ public array $backoff = [5, 30, 120, 300];
    public function __construct(public readonly int $webhookEventId) {}
    public function handle(PaymentWebhookService $payments, ShippingWebhookService $shipping): void
    {
        $event = WebhookEvent::findOrFail($this->webhookEventId);
        if ($event->processed_at) return;
        $event->increment('attempts');
        try {
            str_starts_with($event->topic, 'payment.') ? $payments->handle($event->provider, $event->topic, $event->payload) : $shipping->handle($event->provider, $event->topic, $event->payload);
            $event->update(['status' => 'processed', 'processed_at' => now(), 'last_error' => null]);
        } catch (Throwable $exception) {
            $event->update(['status' => 'failed', 'last_error' => mb_substr($exception->getMessage(), 0, 1000)]);
            throw $exception;
        }
    }
}
