<?php

namespace App\Services\Integrations;

use App\Models\Invoice;
use App\Models\PaymentLink;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Sandbox / generic gateway adapter.
 *
 * Live activation requires PAYMENT_GATEWAY_KEY and PAYMENT_GATEWAY_BASE_URL.
 * When they are missing, links are created in "sandbox" mode and clearly marked as such:
 * no real charge can happen and no payment is ever recorded from the sandbox page.
 */
class SandboxPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'sandbox';
    }

    public function isLive(): bool
    {
        return filled(config('services.payment_gateway.key')) && filled(config('services.payment_gateway.base_url'));
    }

    public function createLink(Invoice $invoice): PaymentLink
    {
        $token = Str::random(40);
        $amount = $invoice->balance();

        $link = PaymentLink::create([
            'organization_id' => $invoice->organization_id,
            'invoice_id' => $invoice->id,
            'provider' => $this->isLive() ? 'gateway' : 'sandbox',
            'token' => $token,
            'amount' => $amount,
            'status' => 'pending',
            'url' => route('payment-links.show', $token),
            'expires_at' => now()->addDays(7),
        ]);

        if ($this->isLive()) {
            $response = Http::withToken(config('services.payment_gateway.key'))
                ->timeout(15)
                ->post(rtrim(config('services.payment_gateway.base_url'), '/').'/payment-links', [
                    'reference' => $link->token,
                    'amount' => $amount,
                    'currency' => 'IDR',
                    'description' => 'Invoice '.$invoice->number,
                    'callback_url' => route('webhooks.payments', 'gateway'),
                ]);

            if ($response->failed()) {
                $link->forceFill(['status' => 'failed'])->save();
                throw new \RuntimeException('Payment gateway menolak pembuatan tautan: '.$response->status());
            }

            $link->forceFill([
                'external_id' => $response->json('id'),
                'url' => $response->json('payment_url', $link->url),
            ])->save();
        }

        return $link;
    }

    public function verifyWebhook(string $payload, array $headers): ?array
    {
        $secret = (string) config('services.payment_gateway.webhook_secret');

        if ($secret === '') {
            return null;
        }

        $signature = $headers['x-signature'][0] ?? $headers['X-Signature'][0] ?? null;

        if (! is_string($signature)) {
            return null;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $data = json_decode($payload, true);

        if (! is_array($data) || empty($data['event_id']) || empty($data['reference'])) {
            return null;
        }

        return [
            'event_id' => (string) $data['event_id'],
            'type' => (string) ($data['event_type'] ?? 'payment.updated'),
            'external_id' => (string) $data['reference'],
            'amount' => (int) ($data['amount'] ?? 0),
            'currency' => (string) ($data['currency'] ?? 'IDR'),
            'status' => (string) ($data['status'] ?? 'unknown'),
        ];
    }
}
