<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('finance.expenses.manage');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->hasPermission('finance.expenses.manage')
            && $user->activeMembership()?->organization_id === $expense->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('finance.expenses.manage');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $this->view($user, $expense);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $this->view($user, $expense);
    }
}
