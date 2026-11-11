<?php

namespace App\Policies;

use App\Models\MaintenanceRequest;
use App\Models\User;

class MaintenancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('maintenance.view');
    }

    public function view(User $user, MaintenanceRequest $request): bool
    {
        if ($user->isTenant()) {
            return $request->tenant?->user_id === $user->id;
        }

        return $user->hasPermission('maintenance.view') && $this->sameOrganization($user, $request);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('maintenance.manage') || $user->isTenant();
    }

    public function update(User $user, MaintenanceRequest $request): bool
    {
        return $user->hasPermission('maintenance.manage') && $this->sameOrganization($user, $request);
    }

    public function delete(User $user, MaintenanceRequest $request): bool
    {
        return false;
    }

    protected function sameOrganization(User $user, MaintenanceRequest $request): bool
    {
        return $user->activeMembership()?->organization_id === $request->organization_id;
    }
}
