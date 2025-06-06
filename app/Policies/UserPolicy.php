<?php

namespace App\Policies;

use App\Models\User;
use App\Services\AccessLevel;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): bool
    {
        return true;

    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'User');

    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'User');

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'User');

    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'User');
    }
}
