<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Orders\OrderService;
use Illuminate\Console\Command;

final class ReleaseExpiredOrders extends Command
{
    protected $signature = 'orders:release-expired';

    protected $description = 'Cancel unpaid expired orders and release reserved inventory';

    public function handle(OrderService $orders): int
    {
        // 分批查詢控制記憶體用量；每筆取消仍由 OrderService 重新鎖定並驗證狀態。
        Order::query()->where('status', OrderStatus::PendingPayment)->where('expires_at', '<=', now())->select('id')->chunkById(100, fn ($expired) => $expired->each(fn ($order) => $orders->cancel($order)));

        return self::SUCCESS;
    }
}
