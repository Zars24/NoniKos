<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupRecord extends Model
{
    protected $fillable = [
        'organization_id',
        'filename',
        'disk',
        'size',
        'checksum',
        'status',
        'trigger',
        'error',
        'created_by',
    ];

    protected $casts = ['size' => 'integer'];
}
