<?php

namespace App\Policies;

use App\Models\Settlement;
use App\Models\User;
use App\Services\AccessLevel;

class SettlementPolicy
{
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'Settlement');
    }

    public function delete(User $user, Settlement $settlement): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'Settlement');
    }

    public function restore(User $user, Settlement $settlement): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'Settlement');
    }

    public function update(User $user, Settlement $settlement): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'Settlement');
    }

    public function view(User $user, Settlement $settlement): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Settlement');
    }

    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Settlement');
    }
}
