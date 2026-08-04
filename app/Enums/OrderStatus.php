<?php
namespace App\Enums;
enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Fulfillment = 'fulfillment';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::PendingPayment => in_array($next, [self::Paid, self::Cancelled], true),
            self::Paid => in_array($next, [self::Fulfillment, self::Refunded], true),
            self::Fulfillment => in_array($next, [self::Shipped, self::Refunded], true),
            self::Shipped => in_array($next, [self::Delivered, self::Refunded], true),
            default => false,
        };
    }
}
