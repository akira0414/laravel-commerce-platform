<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class AuthController extends Controller
{
    /** @return View 登入表單畫面 */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * 驗證帳號密碼並依角色導向顧客或管理員頁面。
     *
     * @param  Request  $request  登入憑證與記住登入選項
     * @return RedirectResponse 登入後的角色首頁或驗證錯誤
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => '帳號或密碼不正確。'])->onlyInput('email');
        }
        $request->session()->regenerate();

        return redirect()->intended($request->user()->isAdmin() ? route('engineering.dashboard') : route('account'));
    }

    /**
     * 登出使用者並更新 Session 與 CSRF token。
     *
     * @param  Request  $request  目前登入工作階段
     * @return RedirectResponse 返回商店首頁
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('storefront');
    }
}
