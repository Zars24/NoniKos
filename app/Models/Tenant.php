<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'user_id',
        'code',
        'full_name',
        'email',
        'phone',
        'identity_number',
        'address',
        'emergency_name',
        'emergency_phone',
        'date_of_birth',
        'status',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(RentalAgreement::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TenantDocument::class);
    }

    public function activeAgreement(): ?RentalAgreement
    {
        return $this->agreements()->where('status', 'active')->latest('start_date')->first();
    }

    public function hasFinancialHistory(): bool
    {
        return $this->invoices()->exists() || $this->payments()->exists();
    }
}
