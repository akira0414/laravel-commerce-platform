<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 導覽列是否顯示只是 UX；真正的授權必須在每次請求的伺服器端執行。
        abort_unless($request->user() && in_array($request->user()->role, $roles, true), 403, '你的帳號沒有權限進入此頁面。');

        return $next($request);
    }
}
