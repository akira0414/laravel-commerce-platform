<?php
namespace App\Http\Controllers\Webhooks;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessWebhookEvent;
use App\Models\WebhookEvent;
use App\Support\Webhooks\WebhookSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
final class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, WebhookSignature $signature): JsonResponse
    {
        $signature->verify($request, (string) config('webhooks.payments.secret'));
        return $this->accept($request, $provider);
    }
    private function accept(Request $request, string $provider): JsonResponse
    {
        $payload = $request->validate(['id' => ['required', 'string', 'max:255'], 'type' => ['required', 'in:payment.succeeded,payment.failed'], 'data.order_number' => ['required', 'string'], 'data.payment_id' => ['required', 'string'], 'data.amount' => ['required', 'integer', 'min:0'], 'data.currency' => ['required', 'string', 'size:3']]);
        $event = WebhookEvent::firstOrCreate(['provider' => $provider, 'event_id' => $payload['id']], ['topic' => $payload['type'], 'payload_hash' => hash('sha256', $request->getContent()), 'payload' => $payload['data'], 'status' => 'pending', 'attempts' => 0]);
        if ($event->wasRecentlyCreated) ProcessWebhookEvent::dispatch($event->id)->afterCommit();
        return response()->json(['received' => true], 202);
    }
}
