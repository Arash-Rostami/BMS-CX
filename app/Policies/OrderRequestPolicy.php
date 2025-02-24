<?php

namespace App\Policies;

use App\Services\AccessLevel;
use Illuminate\Auth\Access\Response;
use App\Models\OrderRequest;
use App\Models\User;

class OrderRequestPolicy
{
//    /**
//     * Determine whether the user can view any models.
//     */
//    public function viewAny(User $user): bool
//    {
//        return AccessLevel::hasPermissionForModel('view', 'OrderRequest');
//    }
//
//    /**
//     * Determine whether the user can view the model.
//     */
//    public function view(User $user, OrderRequest $orderRequest): bool
//    {
//        return AccessLevel::hasPermissionForModel('view', 'OrderRequest');
//    }
//
//    /**
//     * Determine whether the user can create models.
//     */
//    public function create(User $user): bool
//    {
//        return AccessLevel::hasPermissionForModel('create', 'OrderRequest');
//    }
//
//    /**
//     * Determine whether the user can update the model.
//     */
//    public function update(User $user, OrderRequest $orderRequest): bool
//    {
//        return AccessLevel::hasPermissionForModel('edit', 'OrderRequest');
//    }
//
//    /**
//     * Determine whether the user can delete the model.
//     */
//    public function delete(User $user, OrderRequest $orderRequest): bool
//    {
//        return AccessLevel::hasPermissionForModel('delete', 'OrderRequest');
//    }
//
//    /**
//     * Determine whether the user can restore the model.
//     */
//    public function restore(User $user, OrderRequest $orderRequest): bool
//    {
//        return AccessLevel::hasPermissionForModel('restore', 'OrderRequest');
//    }
}
