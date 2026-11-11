<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('contracts.view');
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->hasPermission('contracts.view') && $this->sameOrganization($user, $booking);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('contracts.manage');
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->hasPermission('contracts.manage') && $this->sameOrganization($user, $booking);
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->hasPermission('contracts.manage')
            && $this->sameOrganization($user, $booking)
            && $booking->status === 'pending';
    }

    protected function sameOrganization(User $user, Booking $booking): bool
    {
        return $user->activeMembership()?->organization_id === $booking->organization_id;
    }
}
