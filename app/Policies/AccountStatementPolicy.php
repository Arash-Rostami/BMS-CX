<?php

namespace App\Policies;

use App\Models\AccountStatement;
use App\Models\User;
use App\Services\AccessLevel;

class AccountStatementPolicy
{
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'AccountStatement');
    }

    public function delete(User $user, AccountStatement $accountStatement): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'AccountStatement');
    }

    public function restore(User $user, AccountStatement $accountStatement): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'AccountStatement');
    }

    public function update(User $user, AccountStatement $accountStatement): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'AccountStatement');
    }

    public function view(User $user, AccountStatement $accountStatement): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'AccountStatement');
    }

    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'AccountStatement');
    }
}
