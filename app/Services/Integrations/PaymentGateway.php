<?php

namespace App\Services\Integrations;

use App\Models\Invoice;
use App\Models\PaymentLink;

interface PaymentGateway
{
    public function name(): string;

    /**
     * Create a hosted payment link for the invoice balance.
     */
    public function createLink(Invoice $invoice): PaymentLink;

    /**
     * Verify an incoming webhook request. Returns a normalised event array or null when invalid.
     *
     * @return array{event_id:string,type:string,external_id:string,amount:int,currency:string,status:string}|null
     */
    public function verifyWebhook(string $payload, array $headers): ?array;
}
