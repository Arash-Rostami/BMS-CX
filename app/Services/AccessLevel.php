<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AccessLevel
{
    private const CACHE_KEY_PREFIX = 'user_permissions_map_';
    private const CACHE_TTL = 864000;

    private static ?array $permissionsMap = null;
    private static ?int $currentUserId = null;

    public static function forgetUserPermissionsCache(int $userId): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX . $userId);

        if (self::$currentUserId === $userId) {
            self::$permissionsMap = null;
            self::$currentUserId = null;
        }
    }

    public static function hasPermissionForModel(string $permission, string $model): bool
    {
        $user = cachedUser();

        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        $perms = self::getAuthenticatedUserPermissionsMap($user);

        return isset($perms['All']['all'])
            || isset($perms['All'][$permission])
            || isset($perms[$model]['all'])
            || isset($perms[$model][$permission]);
    }

    private static function getAuthenticatedUserPermissionsMap(User $user): array
    {
        if (self::$currentUserId === $user->id && self::$permissionsMap !== null) {
            return self::$permissionsMap;
        }

        self::$currentUserId = $user->id;

        return self::$permissionsMap = Cache::remember(
            self::CACHE_KEY_PREFIX . $user->id,
            self::CACHE_TTL,
            function () use ($user) {
                $rawPermissions = $user->permissions()->select(['permission', 'model'])->get();
                $map = [];

                foreach ($rawPermissions as $p) {
                    $map[$p->model][$p->permission] = true;
                }

                return $map;
            }
        );
    }
}
