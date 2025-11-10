<?php

namespace App\Policies;

use App\Models\ContractOverview;
use App\Models\User;
use App\Services\AccessLevel;

class ContractOverviewPolicy
{
    public function view(User $user, ContractOverview $contractOverview): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'ContractOverview');
    }

    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'ContractOverview');
    }
}

