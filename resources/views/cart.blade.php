

@extends('layouts.app')
@section('title', '購物車｜'.config('app.name'))
@section('vendor-style')
@endsection
@section('page-style')
@vite('resources/scss/pages/cart.scss')
@endsection
@section('content')
<nav class="wrap nav"><a class="brand" href="/">質感選物</a><a class="btn" href="/">繼續購物</a></nav>
<main class="wrap"><h1>我的購物車</h1><p>確認商品與數量後，再前往結帳付款。</p><section class="card"><div id="items"></div><div class="summary" id="summary"><div>商品合計<div class="total" id="total">NT$ 0</div></div><a class="btn primary" href="/checkout">前往結帳付款</a></div></section></main>
@endsection
@section('vendor-script')
@endsection
@section('page-script')
@vite('resources/js/script/pages/cart.js')
@endsection




