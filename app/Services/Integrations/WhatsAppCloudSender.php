<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Cloud API adapter. Without credentials it runs in simulation mode:
 * the message is logged locally and reported as NOT sent, so no delivery is ever faked.
 */
class WhatsAppCloudSender implements WhatsAppSender
{
    public function name(): string
    {
        return 'whatsapp_cloud';
    }

    public function isLive(): bool
    {
        return filled(config('services.whatsapp.token')) && filled(config('services.whatsapp.phone_number_id'));
    }

    public function send(string $recipient, string $body): array
    {
        if (! $this->isLive()) {
            Log::info('[whatsapp:simulation] '.$recipient.' — '.$body);

            return [
                'sent' => false,
                'provider_message_id' => null,
                'error' => 'Mode simulasi: kredensial WhatsApp belum dikonfigurasi, pesan tidak dikirim.',
            ];
        }

        $response = Http::withToken(config('services.whatsapp.token'))
            ->timeout(20)
            ->post(sprintf('https://graph.facebook.com/v20.0/%s/messages', config('services.whatsapp.phone_number_id')), [
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
                'type' => 'text',
                'text' => ['body' => $body],
            ]);

        if ($response->failed()) {
            return [
                'sent' => false,
                'provider_message_id' => null,
                'error' => 'Provider error '.$response->status().': '.substr($response->body(), 0, 300),
            ];
        }

        return [
            'sent' => true,
            'provider_message_id' => $response->json('messages.0.id'),
            'error' => null,
        ];
    }
}
