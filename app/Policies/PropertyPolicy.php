<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('properties.view');
    }

    public function view(User $user, Property $property): bool
    {
        return $user->hasPermission('properties.view') && $this->sameOrganization($user, $property);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('properties.create');
    }

    public function update(User $user, Property $property): bool
    {
        return $user->hasPermission('properties.update') && $this->sameOrganization($user, $property);
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->hasPermission('properties.delete') && $this->sameOrganization($user, $property);
    }

    protected function sameOrganization(User $user, Property $property): bool
    {
        return $user->activeMembership()?->organization_id === $property->organization_id;
    }
}
