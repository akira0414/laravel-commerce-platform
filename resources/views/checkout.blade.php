@extends('layouts.app')
@section('title', '結帳付款｜'.config('app.name'))
@section('vendor-style')
@endsection
@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/css/pages/checkout.css') }}">
@endsection
@section('content')
<nav class="wrap nav"><a class="brand" href="/">質感選物</a><div class="steps"><span>1 購物車</span><span>›</span><b>2 結帳付款</b><span>›</span><span>3 訂單完成</span></div></nav>
<main class="wrap" id="checkout-page"><h1>結帳與付款</h1><p class="lead">確認商品、收件資料與付款方式，付款完成後即可追蹤訂單進度。</p><div class="layout"><section class="card"><h2>收件與付款資料</h2><form id="checkout-form"><div class="field"><label>會員帳號</label><input value="{{ auth()->user()->email }}" readonly></div><div class="row"><div class="field"><label for="recipient">收件人姓名</label><input id="recipient" value="{{ auth()->user()->name }}" required></div><div class="field"><label for="phone">聯絡電話</label><input id="phone" value="0912345678" required></div></div><div class="field"><label for="address">配送地址</label><input id="address" value="台北市信義區信義路五段 7 號" required></div><div class="field"><label for="payment_method">付款方式</label><select id="payment_method" required>@foreach($methods as $code=>$label)<option value="{{ $code }}">{{ $label }}</option>@endforeach</select></div><button class="pay" id="pay-button" type="submit">確認訂單並付款</button><div class="message" id="message"></div></form></section><aside class="card"><h2>訂單摘要</h2><div id="items"></div><div class="total"><span>合計</span><span id="total">NT$ 0</span></div><p><a class="back" href="/cart">← 返回購物車修改商品</a></p></aside></div></main>
@endsection
@section('vendor-script')
@endsection
@section('page-script')
<script src="{{ asset('assets/js/pages/checkout.js') }}"></script>
@endsection
