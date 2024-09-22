<?php

namespace App\Policies;

use App\Models\QuoteRequest;
use App\Models\User;
use App\Services\AccessLevel;

class QuoteRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('view', 'QuoteRequest');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, QuoteRequest $quoteRequest): bool
    {
        if ($quoteRequest->trashed()) {
            return false;
        }

        return AccessLevel::hasPermissionForModel('view', 'QuoteRequest');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('create', 'QuoteRequest');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, QuoteRequest $quoteRequest): bool
    {
        if ($quoteRequest->trashed()) {
            return false;
        }

        return AccessLevel::hasPermissionForModel('edit', 'QuoteRequest');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, QuoteRequest $quoteRequest): bool
    {
        if ($quoteRequest->trashed()) {
            return false;
        }

        return AccessLevel::hasPermissionForModel('delete', 'QuoteRequest');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): bool
    {
        return AccessLevel::hasPermissionForModel('restore', 'QuoteRequest');
    }
}
