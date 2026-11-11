<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomType extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'property_id',
        'name',
        'monthly_price',
        'deposit_amount',
        'billing_cycle_months',
        'description',
    ];

    protected $casts = [
        'monthly_price' => 'integer',
        'deposit_amount' => 'integer',
        'billing_cycle_months' => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
