<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class AdminDashboardController extends Controller
{
    /**
     * 顯示可搜尋、篩選及分頁的商店營運後台。
     *
     * @param  Request  $request  訂單搜尋與狀態篩選條件
     * @return View 管理後台畫面
     */
    public function __invoke(Request $request): View
    {
        $products = Product::query()->with('inventory')->orderBy('id')->get();
        $orders = Order::query()
            ->with(['items', 'payments', 'shipments'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $keyword = '%'.$request->string('q').'%';
                $query->where(fn ($query) => $query->where('number', 'like', $keyword)->orWhere('customer_email', 'like', $keyword));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('dashboard', [
            'products' => $products, 'orders' => $orders,
            'payments' => Payment::query()->with('order')->latest()->limit(30)->get(),
            'shipments' => Shipment::query()->with('order')->latest()->limit(30)->get(),
            'webhookEvents' => WebhookEvent::query()->latest()->limit(30)->get(),
            'orderStatuses' => OrderStatus::cases(),
            'metrics' => [
                'orders' => Order::count(), 'pending' => Order::where('status', OrderStatus::PendingPayment)->count(),
                'paid_revenue' => Order::whereNotNull('paid_at')->sum('total'),
                'available_stock' => $products->sum(fn (Product $product) => $product->inventory?->available() ?? 0),
                'low_stock' => $products->filter(fn (Product $product) => ($product->inventory?->available() ?? 0) <= 5)->count(),
                'queued_jobs' => DB::table('jobs')->count(), 'failed_jobs' => DB::table('failed_jobs')->count(),
            ],
        ]);
    }
}
