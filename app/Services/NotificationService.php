<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\MessageTemplate;
use App\Models\NotificationDelivery;
use App\Models\Tenant;
use App\Services\Integrations\WhatsAppSender;
use App\Support\Money;
use App\Support\TenantContext;

class NotificationService
{
    public function __construct(protected WhatsAppSender $sender)
    {
    }

    /**
     * Queue-ready send: records a delivery row, attempts the provider, stores the real outcome.
     * A message is only marked "sent" when the provider confirmed it.
     */
    public function sendTemplate(string $key, string $recipient, array $variables = [], ?Invoice $invoice = null, ?Tenant $tenant = null): NotificationDelivery
    {
        $template = MessageTemplate::where('key', $key)->first();
        $body = $this->render($template?->body ?? $this->defaultBody($key), $variables);

        $delivery = NotificationDelivery::create([
            'organization_id' => TenantContext::id(),
            'invoice_id' => $invoice?->id,
            'tenant_id' => $tenant?->id,
            'channel' => 'whatsapp',
            'template_key' => $key,
            'recipient' => $recipient,
            'body' => $body,
            'status' => 'queued',
            'attempts' => 0,
        ]);

        return $this->attempt($delivery);
    }

    public function attempt(NotificationDelivery $delivery): NotificationDelivery
    {
        $delivery->increment('attempts');

        $result = $this->sender->send($delivery->recipient, $delivery->body);

        $delivery->forceFill([
            'status' => $result['sent'] ? 'sent' : 'failed',
            'provider_message_id' => $result['provider_message_id'],
            'error' => $result['error'],
            'sent_at' => $result['sent'] ? now() : null,
        ])->save();

        return $delivery;
    }

    public function render(string $body, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $body = str_replace('{'.$key.'}', (string) $value, $body);
        }

        return $body;
    }

    public function invoiceVariables(Invoice $invoice): array
    {
        return [
            'nama_penyewa' => $invoice->tenant?->full_name ?? '-',
            'nomor_invoice' => $invoice->number,
            'total' => Money::format($invoice->total),
            'sisa' => Money::format($invoice->balance()),
            'jatuh_tempo' => $invoice->due_date->translatedFormat('d F Y'),
            'kamar' => $invoice->agreement?->room?->number ?? '-',
            'organisasi' => $invoice->organization?->name ?? config('app.name'),
        ];
    }

    public function defaultBody(string $key): string
    {
        return match ($key) {
            'invoice_issued' => 'Halo {nama_penyewa}, tagihan {nomor_invoice} sebesar {total} telah diterbitkan. Jatuh tempo {jatuh_tempo}. Terima kasih.',
            'payment_reminder' => 'Halo {nama_penyewa}, pengingat tagihan {nomor_invoice} sebesar {sisa} akan jatuh tempo pada {jatuh_tempo}.',
            'overdue_reminder' => 'Halo {nama_penyewa}, tagihan {nomor_invoice} sebesar {sisa} telah melewati jatuh tempo {jatuh_tempo}. Mohon segera diselesaikan.',
            'payment_received' => 'Terima kasih {nama_penyewa}, pembayaran untuk {nomor_invoice} telah kami terima.',
            'contract_ending' => 'Halo {nama_penyewa}, kontrak sewa kamar {kamar} akan segera berakhir. Silakan hubungi pengelola untuk perpanjangan.',
            default => 'Pemberitahuan dari {organisasi}.',
        };
    }
}
