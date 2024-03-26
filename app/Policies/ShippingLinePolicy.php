<?php

namespace App\Policies;

use App\Services\AccessLevel;
use Illuminate\Auth\Access\Response;
use App\Models\ShippingLine;
use App\Models\User;

class ShippingLinePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'ShippingLine');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ShippingLine $shippingLine): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'ShippingLine');

    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'ShippingLine');

    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ShippingLine $shippingLine): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'ShippingLine');

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ShippingLine $shippingLine): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'ShippingLine');

    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ShippingLine $shippingLine): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'ShippingLine');

    }
}
