<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceRequest extends Model
{
    use BelongsToOrganization, HasFactory;

    public const STATUSES = ['open', 'assigned', 'in_progress', 'waiting', 'completed', 'cancelled'];
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];
    public const CATEGORIES = ['plumbing', 'electrical', 'furniture', 'cleaning', 'security', 'other'];

    protected $fillable = [
        'organization_id',
        'property_id',
        'room_id',
        'tenant_id',
        'assigned_to',
        'title',
        'category',
        'description',
        'priority',
        'status',
        'estimated_cost',
        'actual_cost',
        'photo_path',
        'completed_at',
    ];

    protected $casts = [
        'estimated_cost' => 'integer',
        'actual_cost' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(MaintenanceComment::class);
    }
}
