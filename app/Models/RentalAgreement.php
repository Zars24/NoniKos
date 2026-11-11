<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalAgreement extends Model
{
    use BelongsToOrganization, HasFactory;

    public const STATUSES = ['active', 'expired', 'terminated'];

    protected $fillable = [
        'organization_id',
        'property_id',
        'room_id',
        'tenant_id',
        'booking_id',
        'code',
        'start_date',
        'end_date',
        'monthly_price',
        'deposit_amount',
        'billing_cycle_months',
        'due_days',
        'status',
        'checked_in_at',
        'checked_out_at',
        'termination_reason',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'checked_in_at' => 'date',
        'checked_out_at' => 'date',
        'monthly_price' => 'integer',
        'deposit_amount' => 'integer',
        'billing_cycle_months' => 'integer',
        'due_days' => 'integer',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
