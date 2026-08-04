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

    /** 取得供前端顯示的繁體中文名稱，資料庫與 API 仍使用英文代號。 */
    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => '等待付款', self::Paid => '已付款',
            self::Fulfillment => '接單備貨中', self::Shipped => '已出貨',
            self::Delivered => '已送達', self::Cancelled => '已取消',
            self::Refunded => '已退款',
        };
    }

    /** 判斷訂單是否允許轉移至下一個狀態。 */
    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::PendingPayment => in_array($next, [self::Paid, self::Cancelled], true),
            self::Paid => in_array($next, [self::Fulfillment, self::Cancelled, self::Refunded], true),
            self::Fulfillment => in_array($next, [self::Shipped, self::Refunded], true),
            self::Shipped => in_array($next, [self::Delivered, self::Refunded], true),
            default => false,
        };
    }
}
