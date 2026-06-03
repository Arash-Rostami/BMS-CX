<?php

namespace App\Policies;

use App\Models\LoanSummary;
use App\Models\User;
use App\Services\AccessLevel;

class LoanSummaryPolicy
{
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'LoanSummary');
    }

    public function delete(User $user, LoanSummary $loanSummary): bool
    {
        return AccessLevel::hasPermissionForModel('delete', 'LoanSummary');
    }

    public function restore(User $user, LoanSummary $loanSummary): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'LoanSummary');
    }

    public function update(User $user, LoanSummary $loanSummary): bool
    {
        return AccessLevel::hasPermissionForModel('edit', 'LoanSummary');
    }

    public function view(User $user, LoanSummary $loanSummary): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'LoanSummary');
    }

    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'LoanSummary');
    }
}
