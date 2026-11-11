<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Building extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['organization_id', 'property_id', 'name', 'floors'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
