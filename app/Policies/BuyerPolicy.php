<?php

namespace App\Policies;

use App\Services\AccessLevel;
use Illuminate\Auth\Access\Response;
use App\Models\Buyer;
use App\Models\User;

class BuyerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Buyer');

    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Buyer $buyer): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Buyer');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'Buyer');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Buyer $buyer): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'Buyer');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Buyer $buyer): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'Buyer');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Buyer $buyer): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'Buyer');
    }
}
