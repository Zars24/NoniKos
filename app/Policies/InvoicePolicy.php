<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isTenant()) {
            return $invoice->tenant?->user_id === $user->id;
        }

        return $user->hasPermission('invoices.view') && $this->sameOrganization($user, $invoice);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.adjust') && $this->sameOrganization($user, $invoice);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return false;
    }

    public function adjust(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.adjust') && $this->sameOrganization($user, $invoice);
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.cancel') && $this->sameOrganization($user, $invoice);
    }

    protected function sameOrganization(User $user, Invoice $invoice): bool
    {
        return $user->activeMembership()?->organization_id === $invoice->organization_id;
    }
}
