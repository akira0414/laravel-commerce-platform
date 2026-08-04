<?php

namespace App\Support\Webhooks;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class WebhookSignature
{
    public function verify(Request $request, string $secret): void
    {
        $timestamp = (int) $request->header('X-Webhook-Timestamp');
        $signature = (string) $request->header('X-Webhook-Signature');
        $tolerance = config('webhooks.tolerance_seconds', 300);
        if (! $timestamp || abs(now()->timestamp - $timestamp) > $tolerance) {
            throw new AccessDeniedHttpException('Stale webhook timestamp.');
        }
        // 必須使用未解析的原始 body；重新編碼 JSON 可能改變位元組並導致簽章不一致。
        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        // hash_equals 使用固定時間比較，避免一般字串比較洩漏簽章前綴資訊。
        if (! hash_equals($expected, $signature)) {
            throw new AccessDeniedHttpException('Invalid webhook signature.');
        }
    }
}
