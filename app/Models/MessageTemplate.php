<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    use BelongsToOrganization;

    public const KEYS = ['invoice_issued', 'payment_reminder', 'overdue_reminder', 'payment_received', 'contract_ending'];

    protected $fillable = ['organization_id', 'key', 'name', 'body', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
