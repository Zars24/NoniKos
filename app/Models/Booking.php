<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use BelongsToOrganization, HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'converted', 'cancelled', 'expired'];

    protected $fillable = [
        'organization_id',
        'property_id',
        'room_id',
        'tenant_id',
        'code',
        'start_date',
        'duration_months',
        'monthly_price',
        'deposit_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'duration_months' => 'integer',
        'monthly_price' => 'integer',
        'deposit_amount' => 'integer',
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

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'id', 'booking_id');
    }
}
