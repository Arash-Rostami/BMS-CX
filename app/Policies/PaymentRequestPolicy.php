<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PaymentRequestPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {

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
            return ($record->getOriginal('status') === 'approved')
                ? !in_array($status, ['processing', 'completed'])
                : !in_array($status, ['allowed', 'rejected']);  // Accountants CAN update these statuses
        }

        if (isUserAgent()) {
            return $status !== 'cancelled';  // Agents CANNOT update any status except 'cancelled'
        }

        return true; // All other roles CANNOT update any status as all options are disabled
    }
}
