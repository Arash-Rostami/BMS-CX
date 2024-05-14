<?php

namespace App\Policies;

use App\Models\Balance;
use App\Models\User;
use App\Services\AccessLevel;

class BalancePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Balance');

    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Balance $balance): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Balance');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'Balance');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Balance $balance): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'Balance');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Balance $balance): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'Balance');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Balance $balance): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'Balance');
    }
}
