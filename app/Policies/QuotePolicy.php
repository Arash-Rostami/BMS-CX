<?php

namespace App\Policies;

use App\Models\User;
use App\Services\AccessLevel;

class QuotePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Quote');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Quote');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Quote');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Quote');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Quote');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Quote');
    }
}
