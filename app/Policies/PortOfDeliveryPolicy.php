<?php

namespace App\Policies;

use App\Services\AccessLevel;
use Illuminate\Auth\Access\Response;
use App\Models\PortOfDelivery;
use App\Models\User;

class PortOfDeliveryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'PortOfDelivery');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PortOfDelivery $portOfDelivery): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'PortOfDelivery');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'PortOfDelivery');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PortOfDelivery $portOfDelivery): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'PortOfDelivery');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PortOfDelivery $portOfDelivery): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'PortOfDelivery');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PortOfDelivery $portOfDelivery): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'PortOfDelivery');
    }
}
