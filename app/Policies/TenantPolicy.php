<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tenants.view');
    }

    public function view(User $user, Tenant $tenant): bool
    {
        if ($user->isTenant()) {
            return $tenant->user_id === $user->id;
        }

        return $user->hasPermission('tenants.view') && $this->sameOrganization($user, $tenant);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tenants.manage');
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->hasPermission('tenants.manage') && $this->sameOrganization($user, $tenant);
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->hasPermission('tenants.manage')
            && $this->sameOrganization($user, $tenant)
            && ! $tenant->hasFinancialHistory();
    }

    public function import(User $user): bool
    {
        return $user->hasPermission('tenants.import');
    }

    protected function sameOrganization(User $user, Tenant $tenant): bool
    {
        return $user->activeMembership()?->organization_id === $tenant->organization_id;
    }
}
