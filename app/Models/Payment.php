<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToOrganization, HasFactory;

    public const METHODS = ['cash', 'bank_transfer', 'online', 'other'];
    public const STATUSES = ['pending', 'confirmed', 'reversed'];

    protected $fillable = [
        'organization_id',
        'invoice_id',
        'tenant_id',
        'reference',
        'amount',
        'method',
        'status',
        'paid_at',
        'proof_path',
        'notes',
        'recorded_by',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'date',
        'reversed_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
