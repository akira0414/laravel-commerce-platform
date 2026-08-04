<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';

    /** 取得付款狀態的繁體中文名稱。 */
    public function label(): string
    {
        return match ($this) {
            self::Pending => '等待付款', self::Succeeded => '付款成功',
            self::Failed => '付款失敗', self::Refunded => '已退款',
        };
    }
}
