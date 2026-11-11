<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->isTenant()) {
            return $payment->tenant?->user_id === $user->id;
        }

        return $user->hasPermission('payments.view') && $this->sameOrganization($user, $payment);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payments.record');
    }

    public function reverse(User $user, Payment $payment): bool
    {
        return $user->hasPermission('payments.refund') && $this->sameOrganization($user, $payment);
    }

    protected function sameOrganization(User $user, Payment $payment): bool
    {
        return $user->activeMembership()?->organization_id === $payment->organization_id;
    }
}
