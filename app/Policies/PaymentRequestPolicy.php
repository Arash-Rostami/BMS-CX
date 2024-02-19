<?php

namespace App\Policies;

use App\Models\User;

class PaymentRequestPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {

    }


    public static function updateStatus(string $status): bool
    {
        if (isUserAdmin()) {
            return false; // Admin CAN update any status as no option is disabled
        }

        if (isUserManager() || isUserAccountant()) {
            return $status === 'cancelled';  // Managers and accountants CANNOT update 'cancelled' status
        }

        if (isUserAgent()) {
            return $status !== 'cancelled';  // Agents CANNOT update any status except 'cancelled'
        }

        return true; // All other roles CANNOT update any status as all options are disabled
    }
}
