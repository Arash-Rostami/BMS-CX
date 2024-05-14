<?php

namespace App\Policies;

use App\Services\AccessLevel;
use Illuminate\Auth\Access\Response;
use App\Models\Contractor;
use App\Models\User;

class ContractorPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Contractor');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Contractor $contractor): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Contractor');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'Contractor');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Contractor $contractor): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'Contractor');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Contractor $contractor): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'Contractor');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Contractor $contractor): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'Contractor');
    }

}
