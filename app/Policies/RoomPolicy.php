<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('rooms.view');
    }

    public function view(User $user, Room $room): bool
    {
        return $user->hasPermission('rooms.view') && $this->sameOrganization($user, $room);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('rooms.manage');
    }

    public function update(User $user, Room $room): bool
    {
        return $user->hasPermission('rooms.manage') && $this->sameOrganization($user, $room);
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->hasPermission('rooms.manage')
            && $this->sameOrganization($user, $room)
            && ! $room->agreements()->exists();
    }

    protected function sameOrganization(User $user, Room $room): bool
    {
        return $user->activeMembership()?->organization_id === $room->organization_id;
    }
}
