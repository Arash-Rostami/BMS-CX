<?php

namespace App\Policies;

use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\AccessLevel;

class LedgerEntryPolicy
{
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'LedgerEntry');
    }

    public function delete(User $user, LedgerEntry $ledgerEntry): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'LedgerEntry');
    }

    public function restore(User $user, LedgerEntry $ledgerEntry): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'LedgerEntry');
    }

    public function update(User $user, LedgerEntry $ledgerEntry): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'LedgerEntry');
    }

    public function view(User $user, LedgerEntry $ledgerEntry): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'LedgerEntry');
    }

    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'LedgerEntry');
    }
}
