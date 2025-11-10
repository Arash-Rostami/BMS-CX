<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AccessLevel
{
    private const CACHE_KEY_PREFIX = 'user_permissions_collection_';
    private const CACHE_TTL = 864000;

    private static ?User $user = null;
    private static ?Collection $permissions = null;


    public static function forgetUserPermissionsCache(int $userId): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX . $userId);

        if (self::$user?->id === $userId) {
            self::$permissions = null;
            self::$user = null;
        }
    }

    public static function hasPermissionForModel(string $permission, string $model): bool
    {
        $user = self::getAuthenticatedUser();

        if (!$user) return false;
        // Allow Admin to access every Module
        if ($user->role === 'admin') return true;

        // Retrieve profile Permissions with caching
        return self::getAuthenticatedUserPermissions()->contains(
            fn($p) => ($p->model === $model || $p->model === 'All') &&
                ($p->permission === $permission || $p->permission === 'all')
        );
    }

    private static function getAuthenticatedUser()
    {
        return self::$user ??= cachedUser();
    }

    private static function getAuthenticatedUserPermissions(): Collection
    {
        if (self::$permissions) return self::$permissions;

        $user = self::getAuthenticatedUser();
        if (!$user) return collect();

        // Generate a unique cache key
        return self::$permissions = Cache::remember(
            self::CACHE_KEY_PREFIX . $user->id,
            self::CACHE_TTL,
            fn() => $user->permissions()->get(['permission', 'model'])
        );
    }
}
