<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransition;
use App\Models\Order;

final class OrderStateMachine
{
    public function transition(Order $order, OrderStatus $next): void
    {
        if (! $order->status->canTransitionTo($next)) {
            throw new InvalidOrderTransition("Cannot transition order from {$order->status->value} to {$next->value}");
        }
        $previous = $order->status;
        $order->update(['status' => $next]);
        $order->statusHistories()->create([
            'from_status' => $previous,
            'to_status' => $next,
            'changed_at' => now(),
        ]);
    }
}
