<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_provider_event_is_acknowledged_but_dispatched_once(): void
    {
        Queue::fake();
        $body = json_encode(['id' => 'evt_123', 'type' => 'payment.succeeded', 'data' => ['order_number' => 'ORD-1', 'payment_id' => 'pay_1', 'amount' => 1000, 'currency' => 'TWD']], JSON_THROW_ON_ERROR);
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'test-secret');
        $headers = ['Content-Type' => 'application/json', 'X-Webhook-Timestamp' => $timestamp, 'X-Webhook-Signature' => $signature];
        $this->call('POST', '/api/v1/webhooks/payments/demo', [], [], [], $this->transformHeadersToServerVars($headers), $body)->assertAccepted();
        $this->call('POST', '/api/v1/webhooks/payments/demo', [], [], [], $this->transformHeadersToServerVars($headers), $body)->assertAccepted();
        $this->assertDatabaseCount('webhook_events', 1);
        Queue::assertPushed(ProcessWebhookEvent::class, 1);
    }

    public function test_reused_event_id_with_a_different_payload_is_rejected(): void
    {
        Queue::fake();
        $first = ['id' => 'evt_conflict', 'type' => 'payment.succeeded', 'data' => ['order_number' => 'ORD-1', 'payment_id' => 'pay_1', 'amount' => 1000, 'currency' => 'TWD']];
        $second = ['id' => 'evt_conflict', 'type' => 'payment.succeeded', 'data' => ['order_number' => 'ORD-1', 'payment_id' => 'pay_1', 'amount' => 999999, 'currency' => 'TWD']];

        $this->sendSignedWebhook($first)->assertAccepted();
        $this->sendSignedWebhook($second)->assertConflict();

        $this->assertDatabaseCount('webhook_events', 1);
        Queue::assertPushed(ProcessWebhookEvent::class, 1);
    }

    private function sendSignedWebhook(array $payload): TestResponse
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = now()->timestamp;
        $headers = ['Content-Type' => 'application/json', 'X-Webhook-Timestamp' => $timestamp, 'X-Webhook-Signature' => hash_hmac('sha256', $timestamp.'.'.$body, 'test-secret')];

        return $this->call('POST', '/api/v1/webhooks/payments/demo', [], [], [], $this->transformHeadersToServerVars($headers), $body);
    }
}
