<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Append an audit entry. Never pass secrets or raw credentials in $meta.
     */
    public function log(string $action, ?Model $subject = null, string $description = '', array $meta = []): ActivityLog
    {
        return ActivityLog::create([
            'organization_id' => TenantContext::id(),
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description !== '' ? $description : $action,
            'meta' => $meta ?: null,
            'ip_address' => request()->ip(),
        ]);
    }
}
