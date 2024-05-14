<?php

namespace App\Policies;

use App\Services\AccessLevel;
use Illuminate\Auth\Access\Response;
use App\Models\DeliveryTerm;
use App\Models\User;

class DeliveryTermPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'DeliveryTerm');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DeliveryTerm $deliveryTerm): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'DeliveryTerm');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'DeliveryTerm');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DeliveryTerm $deliveryTerm): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'DeliveryTerm');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DeliveryTerm $deliveryTerm): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'DeliveryTerm');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DeliveryTerm $deliveryTerm): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'DeliveryTerm');
    }
}
