<?php

namespace App\Policies;

use App\Models\Beneficiary;
use App\Models\User;
use App\Services\AccessLevel;
use Illuminate\Auth\Access\Response;

class BeneficiaryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Beneficiary');

    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Beneficiary $beneficiary): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Beneficiary');

    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'Beneficiary');

    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Beneficiary $beneficiary): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'Beneficiary');

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Beneficiary $beneficiary): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'Beneficiary');

    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Beneficiary $beneficiary): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'Beneficiary');

    }
}
