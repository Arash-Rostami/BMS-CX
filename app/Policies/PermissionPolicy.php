<?php

namespace App\Policies;

use App\Services\AccessLevel;
use Illuminate\Auth\Access\Response;
use App\Models\Permission;
use App\Models\User;

class PermissionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Permission');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Permission $permission): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Permission');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'Permission');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Permission $permission): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'Permission');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Permission $permission): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'Permission');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Permission $permission): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'Permission');
    }
}
