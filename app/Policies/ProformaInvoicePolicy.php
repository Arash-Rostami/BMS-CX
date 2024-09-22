<?php

namespace App\Policies;

use App\Services\AccessLevel;
use Illuminate\Auth\Access\Response;
use App\Models\ProformaInvoice;
use App\Models\User;

class ProformaInvoicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'ProformaInvoice');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProformaInvoice $ProformaInvoice): bool
    {
        if ($ProformaInvoice->trashed()) {
            return false;
        }
        return AccessLevel::hasPermissionForModel('view', 'ProformaInvoice');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'ProformaInvoice');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProformaInvoice $ProformaInvoice): bool
    {
        if ($ProformaInvoice->trashed()) {
            return false;
        }

        return AccessLevel::hasPermissionForModel('edit', 'ProformaInvoice');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProformaInvoice $ProformaInvoice): bool
    {
        if ($ProformaInvoice->trashed()) {
            return false;
        }

        return AccessLevel::hasPermissionForModel('delete', 'ProformaInvoice');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProformaInvoice $ProformaInvoice): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'ProformaInvoice');
    }
}
