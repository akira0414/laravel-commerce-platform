<?php

namespace App\Services\Shipping;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Services\Orders\OrderStateMachine;
use Illuminate\Support\Facades\DB;

final readonly class ShippingWebhookService
{
    public function __construct(private OrderStateMachine $states) {}

    /** @param array<string,mixed> $payload */
    public function handle(string $provider, string $topic, array $payload): void
    {
        DB::transaction(function () use ($provider, $topic, $payload): void {
            $order = Order::query()->where('number', $payload['order_number'])->lockForUpdate()->firstOrFail();
            $status = match ($topic) {
                'shipment.in_transit' => ShipmentStatus::InTransit, 'shipment.delivered' => ShipmentStatus::Delivered, 'shipment.exception' => ShipmentStatus::Exception, default => ShipmentStatus::Ready
            };
            $shipment = $order->shipments()->updateOrCreate(['provider' => $provider, 'provider_shipment_id' => $payload['shipment_id']], ['tracking_number' => $payload['tracking_number'] ?? null, 'status' => $status, 'shipped_at' => $status === ShipmentStatus::InTransit ? now() : null, 'delivered_at' => $status === ShipmentStatus::Delivered ? now() : null, 'raw_payload' => $payload]);
            if ($status === ShipmentStatus::Ready && $order->status === OrderStatus::Paid) {
                $this->states->transition($order, OrderStatus::Fulfillment);
            }
            if ($status === ShipmentStatus::InTransit && $order->status === OrderStatus::Paid) {
                $this->states->transition($order, OrderStatus::Fulfillment);
            }
            if ($status === ShipmentStatus::InTransit && $order->status === OrderStatus::Fulfillment) {
                $this->states->transition($order, OrderStatus::Shipped);
            }
            if ($status === ShipmentStatus::Delivered && $order->status === OrderStatus::Shipped) {
                $this->states->transition($order, OrderStatus::Delivered);
            }
        }, 3);
    }
}
