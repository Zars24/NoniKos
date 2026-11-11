<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhookEvent extends Model
{
    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'organization_id',
        'payment_link_id',
        'amount',
        'status',
        'payload',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'amount' => 'integer',
    ];

    public function paymentLink(): BelongsTo
    {
        return $this->belongsTo(PaymentLink::class);
    }
}
