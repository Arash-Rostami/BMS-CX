<?php

namespace App\Policies;

use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\AccessLevel;
use Illuminate\Database\Eloquent\Model;

class PaymentRequestPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {

    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'PaymentRequest');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PaymentRequest $paymentRequest): bool
    {
        if ($paymentRequest->trashed()) {
            return false;
        }

        return AccessLevel::hasPermissionForModel('view', 'PaymentRequest');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'PaymentRequest');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PaymentRequest $paymentRequest): bool
    {
        if ($paymentRequest->trashed()) {
            return false;
        }

        return AccessLevel::hasPermissionForModel('edit', 'PaymentRequest');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PaymentRequest $paymentRequest): bool
    {
        if ($paymentRequest->trashed()) {
            return false;
        }

        return AccessLevel::hasPermissionForModel('delete', 'PaymentRequest');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'PaymentRequest');
    }


    public static function updateStatus(string $status, Model $record): bool
    {
        if (isUserAdmin()) {
            return false; // Admin CAN update any status as no option is disabled
        }

        if (isUserManager()) {
            return !in_array($status, ['rejected', 'processing', 'approved']); // Manager CAN update these statuses
        }

        if (isUserAccountant()) {
            if ($record->getOriginal('status') === 'approved') {
                return true;  // Accountant CANNOT update any status
            }

            if ($record->getOriginal('department_id') == 6) {
                return !in_array($status, ['approved', 'rejected']);
            }
            return !in_array($status, ['allowed', 'rejected']);
        }

        if (isUserCXHead()) {
            if ($record->getOriginal('department_id') == 6 or $record->getOriginal('cost_center') == 6) {
                return !in_array($status, ['allowed', 'rejected', 'cancelled']);
            }
            return true;
        }

        if (isUserAgent()) {
            return $status !== 'cancelled';  // Agents CANNOT update any status except 'cancelled'
        }

        return true; // All other roles CANNOT update any status as all options are disabled
    }
}
