<?php

namespace App\Policies;

use App\Models\Allocation;
use App\Models\User;
use App\Services\AccessLevel;

class AllocationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Allocation');

    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Allocation $allocation): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Allocation');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'Allocation');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user,  Allocation $allocation): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'Allocation');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user,  Allocation $allocation): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'Allocation');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user,  Allocation $allocation): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'Allocation');
    }
}
