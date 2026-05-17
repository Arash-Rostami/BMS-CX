<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;
use App\Services\AccessLevel;

class AccountPolicy
{
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'Account');
    }

    public function delete(User $user, Account $account): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'Account');
    }

    public function restore(User $user, Account $account): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'Account');
    }

    public function update(User $user, Account $account): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'Account');
    }

    public function view(User $user, Account $account): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Account');
    }

    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'Account');
    }
}
