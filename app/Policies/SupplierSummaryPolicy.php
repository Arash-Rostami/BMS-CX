<?php

namespace App\Policies;

use App\Models\SupplierSummary;
use App\Models\User;
use App\Services\AccessLevel;
use Illuminate\Auth\Access\Response;

class SupplierSummaryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'SupplierSummary');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SupplierSummary $supplierSummary): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'SupplierSummary');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'SupplierSummary');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SupplierSummary $supplierSummary): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'SupplierSummary');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SupplierSummary $supplierSummary): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'SupplierSummary');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SupplierSummary $supplierSummary): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'SupplierSummary');
    }
}
