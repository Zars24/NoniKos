<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\RentalAgreement;
use App\Models\User;

class AgreementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('contracts.view');
    }

    public function view(User $user, RentalAgreement $agreement): bool
    {
        if ($user->isTenant()) {
            return $agreement->tenant?->user_id === $user->id;
        }

        return $user->hasPermission('contracts.view') && $this->sameOrganization($user, $agreement);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('contracts.manage');
    }

    public function update(User $user, RentalAgreement $agreement): bool
    {
        return $user->hasPermission('contracts.manage') && $this->sameOrganization($user, $agreement);
    }

    public function delete(User $user, RentalAgreement $agreement): bool
    {
        return false;
    }

    public function manage(User $user, RentalAgreement $agreement): bool
    {
        return $user->hasPermission('contracts.manage') && $this->sameOrganization($user, $agreement);
    }

    protected function sameOrganization(User $user, RentalAgreement $agreement): bool
    {
        return $user->activeMembership()?->organization_id === $agreement->organization_id;
    }
}
