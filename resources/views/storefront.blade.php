@extends('layouts.app')
@section('title', '質感選物')
@section('vendor-style')
@endsection
@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/css/pages/storefront.css') }}">
@endsection
@section('content')
<nav class="wrap nav">
    <a class="brand" href="/">質感選物</a>
    <div class="nav-links">
        <a href="#products">本季選物</a>
        <a href="#reliability">安心購物</a>
        @auth
            <a href="{{ auth()->user()->isAdmin() ? route('engineering.dashboard') : route('account') }}">{{ auth()->user()->isAdmin() ? '商店管理' : '我的訂單' }}</a>
            <form class="nav-auth-form" method="post" action="{{ route('logout') }}">
                @csrf
                <button class="nav-auth-button" type="submit">登出</button>
            </form>
        @else
            <a class="nav-auth-button" href="{{ route('login') }}">登入</a>
        @endauth
        <a class="cart-button" href="{{ auth()->check() ? route('cart') : route('login') }}">購物車／前往結帳 <span class="count" id="cart-count">0</span></a>
    </div>
</nav>
<main><section class="wrap hero"><div class="hero-copy"><div class="kicker">Everyday objects, thoughtfully chosen</div><h1>讓日常，<br>多一點從容。</h1><p>挑選耐看、耐用，也值得長久陪伴的生活單品。</p><a class="primary" href="#products">探索本季選物</a></div><div class="hero-art"><div class="orb one"></div><div class="orb two"></div><div class="product-shape"></div></div></section>
<section class="wrap trust"><div><b>即時庫存</b><span>結帳時即刻保留，避免超賣</span></div><div><b>安全付款核對</b><span>確認金額與幣別後才完成扣庫存</span></div><div><b>訂單全程可追蹤</b><span>付款、出貨與送達狀態一致更新</span></div></section>
<section class="wrap" id="products"><div class="section-head"><div><div class="kicker">Selected for you</div><h2>本季選物</h2></div></div><div class="products">@forelse($products as $product)@php($available=$product->inventory?->available()??0)<article class="product-card"><div class="product-visual"><img src="{{ asset('assets/images/product-placeholder.svg') }}" alt="{{ $product->name }}"><span class="sku">{{ $product->sku }}</span></div><div class="product-info"><h3>{{ $product->name }}</h3><div class="meta"><span>{{ $available>0?'現貨供應':'暫時缺貨' }}</span><span>庫存 {{ $available }} 件</span></div><div class="price">NT$ {{ number_format($product->price/100) }}</div><button class="add" data-id="{{ $product->id }}" data-sku="{{ $product->sku }}" data-name="{{ $product->name }}" data-price="{{ $product->price }}" data-stock="{{ $available }}" @disabled($available<1)>{{ $available>0?'加入購物車':'補貨中' }}</button></div></article>@empty<div class="empty-cart">目前沒有可販售商品。</div>@endforelse</div></section>
<section class="story" id="reliability"><div class="wrap story-grid"><div><div class="kicker">Reliable by design</div><h2>簡單購物，可靠處理。</h2><p>訂單、庫存、付款與物流狀態透過交易與 Webhook 保持一致。</p></div><div class="tech-card"><div class="tech-row"><div class="tech-icon">庫</div><div><b>庫存保留</b><span>付款成功才正式扣除。</span></div></div><div class="tech-row"><div class="tech-icon">簽</div><div><b>可信任通知</b><span>HMAC 與時效驗證。</span></div></div></div></div></section></main>
<footer class="wrap footer"><span>Commerce Platform</span><span><a href="/docs/API.md">API 文件</a> · <a href="/engineering">商店管理</a></span></footer>
@endsection
@section('vendor-script')
@endsection
@section('page-script')
<script src="{{ asset('assets/js/pages/storefront.js') }}"></script>
@endsection
