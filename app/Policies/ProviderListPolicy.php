<?php

namespace App\Policies;

use App\Models\ProviderList;
use App\Models\User;
use App\Services\AccessLevel;

class ProviderListPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'ProviderList');

    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProviderList $list): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'ProviderList');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'ProviderList');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProviderList $list): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'ProviderList');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProviderList $list): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'ProviderList');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProviderList $list): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'ProviderList');
    }
}
