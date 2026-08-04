<?php
namespace App\Http\Controllers\Webhooks;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessWebhookEvent;
use App\Models\WebhookEvent;
use App\Support\Webhooks\WebhookSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
final class ShippingWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, WebhookSignature $signature): JsonResponse
    {
        $signature->verify($request, (string) config('webhooks.shipping.secret'));
        $payload = $request->validate(['id' => ['required', 'string', 'max:255'], 'type' => ['required', 'in:shipment.ready,shipment.in_transit,shipment.delivered,shipment.exception'], 'data.order_number' => ['required', 'string'], 'data.shipment_id' => ['required', 'string'], 'data.tracking_number' => ['nullable', 'string']]);
        $event = WebhookEvent::firstOrCreate(['provider' => $provider, 'event_id' => $payload['id']], ['topic' => $payload['type'], 'payload_hash' => hash('sha256', $request->getContent()), 'payload' => $payload['data'], 'status' => 'pending', 'attempts' => 0]);
        if ($event->wasRecentlyCreated) ProcessWebhookEvent::dispatch($event->id)->afterCommit();
        return response()->json(['received' => true], 202);
    }
}
