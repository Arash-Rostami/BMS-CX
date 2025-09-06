<?php

namespace App\Policies;

use App\Models\CashTransaction;
use App\Models\User;
use App\Services\AccessLevel;

class CashTransactionPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'CashTransaction');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CashTransaction $cashTransaction): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'CashTransaction');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CashTransaction $cashTransaction): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'CashTransaction');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CashTransaction $cashTransaction): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'CashTransaction');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CashTransaction $cashTransaction): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'CashTransaction');
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'CashTransaction');
    }
}
