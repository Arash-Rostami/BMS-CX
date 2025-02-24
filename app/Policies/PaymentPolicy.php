<?php

namespace App\Policies;

use App\Services\AccessLevel;
use Illuminate\Auth\Access\Response;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Payment');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Payment $payment): bool
    {
        if ($payment->trashed()) {
            return false;
        }

        return AccessLevel::hasPermissionForModel('view', 'Payment');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'Payment');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Payment $payment): bool
    {
        if ($payment->trashed()) {
            return false;
        }

        return AccessLevel::hasPermissionForModel('edit', 'Payment');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Payment $payment): bool
    {
        if ($payment->trashed()) {
            return false;
        }

        return AccessLevel::hasPermissionForModel('delete', 'Payment');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Payment $payment): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'Payment');
    }

    public function canEditInput(User $user)
    {
        if (in_array($user->role, ['accountant', 'admin'])) {
            return true;
        }

        if ($user->role == 'agent') {
            if (isset($user->info['department'], $user->info['position']) && $user->info['department'] == 6 && $user->info['position'] == 'mdr') {
                return true;
            }
        }

        return false;
    }
}
