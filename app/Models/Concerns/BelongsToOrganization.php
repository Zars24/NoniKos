<?php

namespace App\Models\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder) {
            if (TenantContext::has()) {
                $builder->where($builder->getModel()->getTable().'.organization_id', TenantContext::id());
            }
        });

        static::creating(function ($model) {
            if ($model->organization_id === null) {
                $model->organization_id = TenantContext::id();
            }

            if ($model->organization_id === null) {
                throw new \RuntimeException('Cannot persist '.static::class.' without an organization context.');
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }
}
