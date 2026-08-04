@extends('layouts.app')
@section('title', '會員登入')
@section('vendor-style')
@endsection
@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/css/pages/login.css') }}">
@endsection
@section('content')
<main class="card"><a href="/">質感選物</a><h1>歡迎回來</h1><form method="post" action="/login">@csrf<div class="field"><label for="email">電子郵件</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>@error('email')<div class="error">{{ $message }}</div>@enderror</div><div class="field"><label for="password">密碼</label><input id="password" name="password" type="password" required></div><button type="submit">登入</button></form><p>顧客：customer@example.com / password<br>管理員：admin@example.com / password</p></main>
@endsection
@section('vendor-script')
@endsection
@section('page-script')
<script src="{{ asset('assets/js/pages/login.js') }}"></script>
@endsection
