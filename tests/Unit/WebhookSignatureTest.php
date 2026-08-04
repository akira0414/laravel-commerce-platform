<?php

namespace Tests\Unit;

use App\Support\Webhooks\WebhookSignature;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

final class WebhookSignatureTest extends TestCase
{
    public function test_accepts_valid_signature(): void
    {
        $body = '{"id":"evt_1"}';
        $timestamp = now()->timestamp;
        $request = Request::create('/hook', 'POST', [], [], [], ['HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp, 'HTTP_X_WEBHOOK_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$body, 'secret')], $body);
        (new WebhookSignature)->verify($request, 'secret');
        $this->addToAssertionCount(1);
    }

    public function test_rejects_invalid_signature(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $request = Request::create('/hook', 'POST', [], [], [], ['HTTP_X_WEBHOOK_TIMESTAMP' => now()->timestamp, 'HTTP_X_WEBHOOK_SIGNATURE' => 'invalid'], '{}');
        (new WebhookSignature)->verify($request, 'secret');
    }

    public function test_rejects_a_valid_signature_with_a_stale_timestamp(): void
    {
        $body = '{"id":"evt_old"}';
        $timestamp = now()->subMinutes(10)->timestamp;
        $request = Request::create('/hook', 'POST', [], [], [], [
            'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_X_WEBHOOK_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$body, 'secret'),
        ], $body);

        $this->expectException(AccessDeniedHttpException::class);
        (new WebhookSignature)->verify($request, 'secret');
    }
}
