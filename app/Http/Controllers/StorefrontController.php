<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

final class StorefrontController extends Controller
{
    /**
     * 顯示前台商店首頁與目前可販售商品。
     *
     * @return View 商店首頁畫面
     */
    public function index(): View
    {
        return view('storefront', [
            'products' => Product::query()
                ->with('inventory')
                ->where('is_active', true)
                ->orderBy('id')
                ->get(),
        ]);
    }
}
