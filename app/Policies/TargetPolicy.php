<?php

namespace App\Policies;

use App\Models\Target;
use App\Models\User;
use App\Services\AccessLevel;
use Illuminate\Auth\Access\Response;

class TargetPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Target');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Target $target): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Target');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'Target');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Target $target): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'Target');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Target $target): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Target $target): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'Target');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Target $target): bool
    {
        return true;
    }
}
