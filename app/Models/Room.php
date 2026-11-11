<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use BelongsToOrganization, HasFactory;

    public const STATUSES = ['available', 'reserved', 'occupied', 'maintenance', 'unavailable'];

    protected $fillable = [
        'organization_id',
        'property_id',
        'building_id',
        'room_type_id',
        'number',
        'floor',
        'monthly_price',
        'deposit_amount',
        'billing_cycle_months',
        'status',
        'facilities',
        'notes',
        'photo_path',
        'is_active',
    ];

    protected $casts = [
        'monthly_price' => 'integer',
        'deposit_amount' => 'integer',
        'billing_cycle_months' => 'integer',
        'is_active' => 'boolean',
        'floor' => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(RoomPhoto::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(RoomStatusHistory::class)->latest();
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(RentalAgreement::class);
    }

    public function activeAgreement(): ?RentalAgreement
    {
        return $this->agreements()->where('status', 'active')->first();
    }

    public function scopeAvailable(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'available')->where('is_active', true);
    }

    public function statusLabel(): string
    {
        return ucfirst($this->status);
    }
}
