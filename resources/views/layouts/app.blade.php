
<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/scss/common.scss', 'resources/js/script/common.js'])
    @yield('vendor-style')
    @yield('page-style')
</head>
<body data-signed-in="{{ auth()->check() ? '1' : '0' }}" data-user-role="{{ auth()->user()?->role }}">
    @yield('content')
    @yield('vendor-script')
    @yield('page-script')
</body>
</html>

