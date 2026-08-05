

@extends('layouts.app')
@section('title', '模擬付款｜'.$order->number)
@section('vendor-style')
@endsection
@section('page-style')
@vite('resources/scss/pages/payment-simulator.scss')
@endsection
@section('content')
<nav class="wrap nav"><a href="/">質感選物</a><a href="/account">我的訂單</a></nav><main class="wrap layout"><section class="card"><h1>模擬付款中心</h1>
@if($order->status === \App\Enums\OrderStatus::PendingPayment)
<form method="post" action="{{ route('payments.confirm', $order) }}">@csrf
@foreach($methods as $code => $label)
<label class="method"><input type="radio" name="method" value="{{ $code }}" @checked($loop->first)> {{ $label }}</label>
@endforeach
<div class="actions"><button class="success" name="outcome" value="success">模擬付款成功</button><button class="failed" name="outcome" value="failed">模擬付款失敗</button></div></form>
@else<p>目前狀態：{{ $order->status->label() }}</p>@endif
</section><aside class="card"><h2>訂單摘要</h2>@foreach($order->items as $item)<div class="row"><span>{{ $item->name }} × {{ $item->quantity }}</span><b>NT$ {{ number_format($item->line_total/100) }}</b></div>@endforeach<div class="row"><b>合計</b><b>NT$ {{ number_format($order->total/100) }}</b></div></aside></main>
@endsection
@section('vendor-script')
@endsection
@section('page-script')
@vite('resources/js/script/pages/payment-simulator.js')
@endsection




