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
        if (! $timestamp || abs(now()->timestamp - $timestamp) > $tolerance) throw new AccessDeniedHttpException('Stale webhook timestamp.');
        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        if (! hash_equals($expected, $signature)) throw new AccessDeniedHttpException('Invalid webhook signature.');
    }
}
