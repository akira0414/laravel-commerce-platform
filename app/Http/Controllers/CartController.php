<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

final class CartController extends Controller
{
    /** @return View 顧客購物車頁面 */
    public function index(): View
    {
        return view('cart');
    }
}
