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
        //
    }

    public static function updateStatus(string $status): bool
    {
        return match (auth()->user()->role) {
            'Admin' => false, // Admin CAN update any status as no option is disabled
            'Manager', 'Accountant' => $status === 'cancelled', // Managers and accountants CANNOT update 'cancelled' status
            'Agent' => $status !== 'cancelled', // Agents CANNOT update any status except 'cancelled'
            default => true, // All other roles CANNOT update any status as all options are disabled
        };
    }

}
