

@extends('layouts.app')

@section('title', '我的訂單｜'.config('app.name'))

@section('vendor-style')
@endsection

@section('page-style')
@vite('resources/scss/pages/account.scss')
@endsection

@section('content')
<nav class="wrap nav">
    <a class="brand" href="/">質感選物</a>
    <div>
        <a class="button" href="/">繼續購物</a>
        <form style="display:inline" method="post" action="/logout">@csrf<button>登出</button></form>
    </div>
</nav>

<main class="wrap">
    <section class="hero">
        <h1>{{ auth()->user()->name }}，您好</h1>
        <p>訂單、付款及配送進度都會集中顯示在這裡。</p>
    </section>

    @if(session('status'))
        <div class="notice">{{ session('status') }}</div>
    @endif

    @forelse($orders as $order)
        @php
            $cancelled = in_array($order->status, [
                \App\Enums\OrderStatus::Cancelled,
                \App\Enums\OrderStatus::Refunded,
            ], true);
            $historyByStatus = $order->statusHistories->keyBy(
                fn ($history) => $history->to_status->value
            );
        @endphp

        <article class="order">
            <div class="head">
                <div>
                    <div class="number">{{ $order->number }}</div>
                    <div class="meta">
                        <span>{{ $order->created_at->format('Y/m/d H:i') }}</span>
                        <span>{{ $order->currency }} {{ number_format($order->total / 100) }}</span>
                        <span>{{ $order->items->sum('quantity') }} 件商品</span>
                    </div>
                </div>
                <span class="status rounded-pill">{{ $order->status->label() }}</span>
            </div>

            @if($cancelled)
                @php
                    $cancelHistory = $historyByStatus->get(\App\Enums\OrderStatus::Cancelled->value);
                @endphp
                <div class="notice" style="background:#f7e8e8;color:var(--danger)">
                    此訂單已取消，不再進入配送流程。
                    @if($cancelHistory)
                        <br>取消時間：{{ $cancelHistory->changed_at->format('Y/m/d H:i') }}
                    @endif
                </div>
            @else
                <div class="timeline">
                    @foreach([
                        'pending_payment' => '收到訂單',
                        'paid' => '付款完成',
                        'fulfillment' => '接單備貨',
                        'shipped' => '已出貨',
                        'delivered' => '已送達',
                    ] as $statusCode => $label)
                        @php($history = $historyByStatus->get($statusCode))
                        <div class="step {{ $history ? 'active' : '' }}">
                            <span class="dot"></span>{{ $label }}
                            @if($history)
                                <br><small>{{ $history->changed_at->format('m/d H:i') }}</small>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="items">
                @foreach($order->items as $item)
                    <div class="item">
                        <span>{{ $item->name }} × {{ $item->quantity }}</span>
                        <span>NT$ {{ number_format($item->line_total / 100) }}</span>
                    </div>
                @endforeach
            </div>

            <div class="actions">
                <span class="muted">
                    @if($order->shipments->last()?->tracking_number)
                        物流追蹤碼：{{ $order->shipments->last()->tracking_number }}
                    @else
                        商店接單後將顯示物流資訊
                    @endif
                </span>

                @if(in_array($order->status, [\App\Enums\OrderStatus::PendingPayment, \App\Enums\OrderStatus::Paid], true))
                    <form method="post" action="{{ route('account.orders.cancel', $order) }}" onsubmit="return confirm('確定要取消這筆訂單嗎？已付款款項將模擬退款。')">
                        @csrf
                        <button class="cancel" type="submit">取消訂單</button>
                    </form>
                @endif
            </div>
        </article>
    @empty
        <div class="empty">目前還沒有訂單。<br><a href="/">前往選購商品</a></div>
    @endforelse
</main>
@endsection

@section('vendor-script')
@endsection

@section('page-script')
@vite('resources/js/script/pages/account.js')
@endsection




