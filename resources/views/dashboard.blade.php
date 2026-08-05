

@extends('layouts.app')
@section('title', '商店管理後台')
@section('vendor-style')
@endsection
@section('page-style')
    @vite('resources/scss/pages/dashboard.scss')
@endsection
@section('content')
    <div class="shell">
        <aside class="sidebar">
            <div class="brand">商店管理</div>
            <nav class="menu"><a href="#overview">營運總覽</a><a href="#orders">訂單管理</a><a href="#inventory">商品與庫存</a><a
                    href="#payments">付款紀錄</a><a href="#shipping">物流管理</a><a href="#webhooks">Webhook 事件</a><a
                    href="/">返回商店</a></nav>
            <form class="logout" method="post" action="/logout">@csrf<button>登出</button></form>
        </aside>
        <main class="main">
            <header class="topbar">
                <h1>商店營運中心</h1>
                <div>{{ auth()->user()->name }} · 管理員</div>
            </header>
            <div class="content">
                <section id="overview">
                    <div class="welcome">
                        <div>
                            <h2>營運總覽</h2>
                            <p>快速掌握訂單、營收、庫存與背景工作。</p>
                        </div>
                        <div class="health">● 系統正常</div>
                    </div>
                    <div class="metrics">
                        <div class="metric"><small>累計訂單</small><strong>{{ $metrics['orders'] }}</strong><span>等待付款
                                {{ $metrics['pending'] }} 筆</span></div>
                        <div class="metric"><small>已付款營業額</small><strong>NT$
                                {{ number_format($metrics['paid_revenue'] / 100) }}</strong></div>
                        <div class="metric"><small>可售庫存</small><strong>{{ $metrics['available_stock'] }}</strong><span>低庫存
                                {{ $metrics['low_stock'] }} 項</span></div>
                        <div class="metric"><small>背景工作</small><strong>{{ $metrics['queued_jobs'] }}</strong><span>失敗
                                {{ $metrics['failed_jobs'] }} 筆</span></div>
                    </div>
                </section>
                <section class="section" id="orders">
                    <div class="section-head">
                        <div>
                            <h2>訂單管理</h2>
                            <p>每頁 20 筆，可依關鍵字或狀態篩選。</p>
                        </div><span>共 {{ $orders->total() }} 筆</span>
                    </div>
                    <form class="filters" method="get"><input name="q" value="{{ request('q') }}"
                            placeholder="訂單編號或顧客 Email"><select name="status">
                            <option value="">全部狀態</option>
                            @foreach ($orderStatuses as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        <button class="action-btn">套用篩選</button>
                    </form>
                    @if (session('status'))
                        <div class="tip">{{ session('status') }}</div>
                    @endif
                    <div class="panel">
                        <table>
                            <thead>
                                <tr>
                                    <th>訂單</th>
                                    <th>顧客</th>
                                    <th>商品</th>
                                    <th>金額</th>
                                    <th>狀態</th>
                                    <th>下一步</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    <tr>
                                        <td><b>{{ $order->number }}</b><br><span
                                                class="muted">{{ $order->created_at->format('Y/m/d H:i') }}</span></td>
                                        <td>{{ $order->customer_email }}</td>
                                        <td>{{ $order->items->sum('quantity') }} 件</td>
                                        <td class="money">{{ $order->currency }} {{ number_format($order->total / 100) }}
                                        </td>
                                        <td><span class="pill">{{ $order->status->label() }}</span></td>
                                        <td>
                                            @if ($order->status === \App\Enums\OrderStatus::Paid)
                                                <form method="post"
                                                    action="{{ route('engineering.orders.accept', $order) }}">
                                                    @csrf<button>接單並備貨</button></form>
                                            @elseif($order->status === \App\Enums\OrderStatus::Fulfillment)
                                                <form method="post"
                                                    action="{{ route('engineering.orders.ship', $order) }}">
                                                    @csrf<button>標記已出貨</button></form>
                                            @elseif($order->status === \App\Enums\OrderStatus::Shipped)
                                                <form method="post"
                                                    action="{{ route('engineering.orders.deliver', $order) }}">
                                                @csrf<button>標記已送達</button></form>@else<span class="muted">目前無操作</span>
                                            @endif
                                        </td>
                                </tr>@empty<tr>
                                        <td colspan="6" class="empty">目前沒有訂單</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="pagination"><span>第 {{ $orders->currentPage() }}／{{ $orders->lastPage() }}
                                頁</span><span>
                                @if ($orders->previousPageUrl())
                                    <a href="{{ $orders->previousPageUrl() }}#orders">上一頁</a>
                                    @endif @if ($orders->nextPageUrl())
                                        <a href="{{ $orders->nextPageUrl() }}#orders">下一頁</a>
                                    @endif
                            </span>
                        </div>
                    </div>
                </section>
                <section class="section" id="inventory">
                    <div class="section-head">
                        <h2>商品與庫存</h2>
                    </div>
                    <div class="panel">
                        <table>
                            <thead>
                                <tr>
                                    <th>SKU／商品</th>
                                    <th>售價</th>
                                    <th>實體</th>
                                    <th>保留</th>
                                    <th>可售</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $product)
                                    <tr>
                                        <td><b>{{ $product->sku }}</b><br>{{ $product->name }}</td>
                                        <td>NT$ {{ number_format($product->price / 100) }}</td>
                                        <td>{{ $product->inventory?->on_hand ?? 0 }}</td>
                                        <td>{{ $product->inventory?->reserved ?? 0 }}</td>
                                        <td>{{ $product->inventory?->available() ?? 0 }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
                <section class="section" id="payments">
                    <div class="section-head">
                        <h2>付款紀錄</h2>
                    </div>
                    <div class="panel">
                        <table>
                            <thead>
                                <tr>
                                    <th>付款編號</th>
                                    <th>訂單</th>
                                    <th>方式</th>
                                    <th>金額</th>
                                    <th>狀態</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                    <tr>
                                        <td>{{ $payment->provider_payment_id }}</td>
                                        <td>{{ $payment->order?->number }}</td>
                                        <td>{{ config('payments.methods.' . $payment->method, $payment->method ?? '未指定') }}</td>
                                        <td>{{ $payment->currency }} {{ number_format($payment->amount / 100) }}</td>
                                        <td>{{ $payment->status->label() }}</td>
                                </tr>@empty<tr>
                                        <td colspan="5">尚無付款紀錄</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
                <section class="section" id="shipping">
                    <div class="section-head">
                        <h2>物流管理</h2>
                    </div>
                    <div class="panel">
                        <table>
                            <thead>
                                <tr>
                                    <th>物流編號</th>
                                    <th>訂單</th>
                                    <th>追蹤碼</th>
                                    <th>狀態</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shipments as $shipment)
                                    <tr>
                                        <td>{{ $shipment->provider_shipment_id }}</td>
                                        <td>{{ $shipment->order?->number }}</td>
                                        <td>{{ $shipment->tracking_number ?? '—' }}</td>
                                        <td>{{ $shipment->status->label() }}</td>
                                </tr>@empty<tr>
                                        <td colspan="4">尚無物流紀錄</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
                <section class="section" id="webhooks">
                    <div class="section-head">
                        <h2>Webhook 事件收件匣</h2>
                    </div>
                    <div class="panel">
                        <table>
                            <thead>
                                <tr>
                                    <th>事件 ID</th>
                                    <th>供應商</th>
                                    <th>主題</th>
                                    <th>狀態</th>
                                    <th>嘗試</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($webhookEvents as $event)
                                    <tr>
                                        <td>{{ $event->event_id }}</td>
                                        <td>{{ $event->provider }}</td>
                                        <td>{{ $event->topic }}</td>
                                        <td>{{ $event->statusLabel() }}</td>
                                        <td>{{ $event->attempts }}</td>
                                </tr>@empty<tr>
                                        <td colspan="5">尚無 Webhook 事件</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
@endsection
@section('vendor-script')
@endsection
@section('page-script')
    @vite('resources/js/script/pages/dashboard.js')
@endsection




