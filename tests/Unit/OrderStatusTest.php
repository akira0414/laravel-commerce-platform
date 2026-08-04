<?php
namespace Tests\Unit;
use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;
final class OrderStatusTest extends TestCase
{
    public function test_only_explicit_transitions_are_allowed(): void
    {
        $this->assertTrue(OrderStatus::PendingPayment->canTransitionTo(OrderStatus::Paid));
        $this->assertTrue(OrderStatus::Shipped->canTransitionTo(OrderStatus::Delivered));
        $this->assertFalse(OrderStatus::Delivered->canTransitionTo(OrderStatus::PendingPayment));
        $this->assertFalse(OrderStatus::Cancelled->canTransitionTo(OrderStatus::Paid));
    }
}
