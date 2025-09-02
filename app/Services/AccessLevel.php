<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AccessLevel
{
    private static ?User $loggedInUser = null;

    public static function hasPermissionForModel($permission, $model)
    {
        $loggedUser = self::getAuthenticatedUser();

        if (!$loggedUser) {
            return false;
        }

        // Allow Admin to access every Module
        if ($loggedUser->role == 'admin') {
            return true;
        }

        // Generate a unique cache key
        $cacheKey = 'permissions_' . $loggedUser->id . '_' . $model . '_' . $permission;

        // Retrieve profile Permissions with caching
        return Cache::remember($cacheKey, 60, function () use ($loggedUser, $permission, $model) {
            return $loggedUser->permissions()
                ->where(function ($query) use ($model) {
                    $query->where('model', $model)->orWhere('model', 'All');
                })
                ->where(function ($query) use ($permission) {
                    $query->where('permission', $permission)->orWhere('permission', 'all');
                })
                ->exists();
        });
    }

    private static function getAuthenticatedUser(): ?User
    {
        if (self::$loggedInUser === null) {
            self::$loggedInUser = auth()->user();
        }
        return self::$loggedInUser;
    }
}
