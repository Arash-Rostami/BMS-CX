<?php

namespace App\Services;

class AccessLevel
{
    public static function hasPermissionForModel($permission, $model)
    {
        $loggedUser = auth()->user();

        // Allow Admin to access every Module
        if ($loggedUser->role == 'admin') {
            return true;
        }

        // Retrieve profile Permissions
        $permissions = $loggedUser->permissions()
            ->whereIn('model', [$model, 'All'])
            ->whereIn('permission', [$permission, 'all'])
            ->get();

        return $permissions->contains(fn($permission): bool => $permission->user_id === auth()->user()->id);
    }
}
