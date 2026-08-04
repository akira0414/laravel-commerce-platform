<?php
namespace App\Services\Payments;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Inventory\InventoryService;
use App\Services\Orders\OrderStateMachine;
use DomainException;
use Illuminate\Support\Facades\DB;
final readonly class PaymentWebhookService
{
    public function __construct(private InventoryService $inventory, private OrderStateMachine $states) {}
    /** @param array<string,mixed> $payload */
    public function handle(string $provider, string $topic, array $payload): void
    {
        DB::transaction(function () use ($provider, $topic, $payload): void {
            $order = Order::query()->where('number', $payload['order_number'])->lockForUpdate()->firstOrFail();
            if ((int) $payload['amount'] !== $order->total || $payload['currency'] !== $order->currency) throw new DomainException('Payment amount or currency mismatch.');
            $payment = $order->payments()->firstOrNew(['provider' => $provider, 'provider_payment_id' => $payload['payment_id']]);
            if ($payment->status === PaymentStatus::Succeeded) return;
            $succeeded = $topic === 'payment.succeeded';
            if ($succeeded && $order->status !== OrderStatus::PendingPayment) throw new DomainException("Late or conflicting payment for order in {$order->status->value} state.");
            $payment->fill(['status' => $succeeded ? PaymentStatus::Succeeded : PaymentStatus::Failed, 'amount' => $payload['amount'], 'currency' => $payload['currency'], 'failure_reason' => $payload['failure_reason'] ?? null, 'paid_at' => $succeeded ? now() : null, 'raw_payload' => $payload])->save();
            if ($succeeded && $order->status === OrderStatus::PendingPayment) {
                $this->inventory->commit($order);
                $this->states->transition($order, OrderStatus::Paid);
                $order->update(['paid_at' => now()]);
            }
        }, 3);
    }
}
