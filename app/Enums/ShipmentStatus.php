<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Exception = 'exception';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '等待處理',
            self::Ready => '準備出貨',
            self::InTransit => '配送中',
            self::Delivered => '已送達',
            self::Exception => '配送異常',
            self::Returned => '已退回',
        };
    }
}
