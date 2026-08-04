<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use PHPUnit\Framework\TestCase;

final class StatusLabelTest extends TestCase
{
    public function test_status_codes_have_traditional_chinese_display_labels(): void
    {
        $this->assertSame('等待付款', OrderStatus::PendingPayment->label());
        $this->assertSame('已付款', OrderStatus::Paid->label());
        $this->assertSame('付款成功', PaymentStatus::Succeeded->label());
        $this->assertSame('配送中', ShipmentStatus::InTransit->label());
    }

    public function test_status_values_remain_stable_for_database_and_api_integrations(): void
    {
        $this->assertSame('pending_payment', OrderStatus::PendingPayment->value);
        $this->assertSame('succeeded', PaymentStatus::Succeeded->value);
        $this->assertSame('in_transit', ShipmentStatus::InTransit->value);
    }
}
