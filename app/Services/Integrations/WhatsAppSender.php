<?php

namespace App\Services\Integrations;

interface WhatsAppSender
{
    public function name(): string;

    public function isLive(): bool;

    /**
     * @return array{sent:bool,provider_message_id:?string,error:?string}
     */
    public function send(string $recipient, string $body): array;
}
