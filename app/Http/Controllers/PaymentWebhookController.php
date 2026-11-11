<?php

namespace App\Http\Controllers;

use App\Models\PaymentLink;
use App\Models\PaymentWebhookEvent;
use App\Services\Integrations\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected PaymentGateway $gateway,
        protected PaymentService $payments,
    ) {
    }

    public function handle(Request $request, string $provider)
    {
        $payload = $request->getContent();
        $event = $this->gateway->verifyWebhook($payload, $request->headers->all());

        if (! $event) {
            Log::warning('payment.webhook_rejected', ['provider' => $provider, 'ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid signature or payload'], 400);
        }

        // Idempotency: the unique (provider, event_id) index rejects duplicates.
        if (PaymentWebhookEvent::where('provider', $provider)->where('event_id', $event['event_id'])->exists()) {
            return response()->json(['message' => 'Duplicate event ignored'], 200);
        }

        $link = PaymentLink::withoutGlobalScopes()->where('token', $event['external_id'])->first();

        if (! $link) {
            return response()->json(['message' => 'Unknown reference'], 404);
        }

        $record = PaymentWebhookEvent::create([
            'provider' => $provider,
            'event_id' => $event['event_id'],
            'event_type' => $event['type'],
            'organization_id' => $link->organization_id,
            'payment_link_id' => $link->id,
            'amount' => $event['amount'],
            'status' => 'received',
            'payload' => json_decode($payload, true) ?: null,
        ]);

        if ($event['currency'] !== 'IDR') {
            $record->update(['status' => 'rejected', 'error' => 'Mata uang tidak didukung: '.$event['currency']]);
            return response()->json(['message' => 'Unsupported currency'], 422);
        }

        if ($event['status'] !== 'paid') {
            $record->update(['status' => 'ignored']);
            return response()->json(['message' => 'Event recorded, no payment action'], 200);
        }

        if ($event['amount'] !== $link->amount) {
            $record->update(['status' => 'rejected', 'error' => 'Nominal tidak cocok dengan tautan.']);

            return response()->json(['message' => 'Amount mismatch'], 422);
        }

        try {
            DB::transaction(function () use ($link, $event, $record) {
                $link->forceFill(['status' => 'paid', 'paid_at' => now()])->save();

                $this->payments->confirmOnline(
                    $link->invoice,
                    $event['amount'],
                    'ONLINE-'.$link->token,
                    $record
                );

                $record->update(['status' => 'processed']);
            });
        } catch (\Throwable $e) {
            $record->update(['status' => 'failed', 'error' => $e->getMessage()]);
            Log::error('payment.webhook_failed', ['event' => $event['event_id'], 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Processing failed'], 500);
        }

        return response()->json(['message' => 'OK']);
    }
}
