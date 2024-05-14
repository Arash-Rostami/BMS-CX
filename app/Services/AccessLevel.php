<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AccessLevel
{
    public static function hasPermissionForModel($permission, $model)
    {
        $loggedUser = auth()->user();

        // Allow Admin to access every Module
        if ($loggedUser->role == 'admin') {
            return true;
        }

        // Generate a unique cache key
        $cacheKey = 'permissions_' . $loggedUser->id . '_' . $model . '_' . $permission;

        // Retrieve profile Permissions with caching
        $permissions = Cache::remember($cacheKey, 60, function () use ($loggedUser, $permission, $model) {
            return $loggedUser->permissions()
                ->whereIn('model', [$model, 'All'])
                ->whereIn('permission', [$permission, 'all'])
                ->get();
        });

        return $permissions->contains(fn($permission): bool => $permission->user_id === $loggedUser->id);
    }
}
