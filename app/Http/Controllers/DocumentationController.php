<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DocumentationController extends Controller
{
    /**
     * 以 Markdown 格式回傳專案 API 文件。
     *
     * @return BinaryFileResponse API 文件檔案回應
     */
    public function api(): BinaryFileResponse
    {
        return response()->file(
            base_path('docs/API.md'),
            ['Content-Type' => 'text/markdown; charset=UTF-8']
        );
    }
}
