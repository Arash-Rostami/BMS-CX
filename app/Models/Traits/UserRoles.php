<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Cache;

trait UserRoles
{
    public function hasRole(string $role): bool
    {
        return strtolower($this->role) == strtolower($role);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByRoles($query, $roles)
    {
        return $query->whereIn('role', $roles);
    }

    public function scopeExcludeRole($query, $role)
    {
        return $query->where('role', '!=', $role);
    }

    public function scopeExcludeRoles($query, $roles)
    {
        return $query->whereNotIn('role', $roles);
    }

    public static function getUsersByRole($role)
    {
        return self::active()->byRole($role)->get();
    }

    public static function getUsersByRoles($roles)
    {
        return self::active()->byRoles($roles)->get();
    }

    public static function getUsersExcludingRole($role)
    {
        return self::active()->excludeRole($role)->get();
    }

    public static function getUsersExcludingRoles($roles)
    {
        return self::active()->excludeRoles($roles)->get();
    }

    public static function isAdmin()
    {
        $user = auth()->user();
        return $user && $user->role === 'admin';
    }

    public static function isManager()
    {
        $user = auth()->user();
        return $user && $user->role === 'manager';
    }

    public static function getAccountant()
    {
        $cacheKey = 'users_with_role_accountant_or_admin';

        return Cache::remember($cacheKey, 60, function () {
            return self::where('role', 'accountant')
                ->orWhere('role', 'admin')
                ->get();
        });
    }

    public static function getAgent()
    {
        $cacheKey = 'users_with_role_agent_or_admin';

        return Cache::remember($cacheKey, 60, function () {
            return self::where('role', 'agent')
                ->orWhere('role', 'admin')
                ->get();
        });
    }
    public static function getManager()
    {
        $cacheKey = 'users_with_role_manager_or_admin';

        return Cache::remember($cacheKey, 60, function () {
            return self::where('role', 'manager')
                ->orWhere('role', 'admin')
                ->get();
        });
    }
    public static function getPartnersWithPosition(string $position = null)
    {
        $cacheKey = $position
            ? "users_with_role_partner_position_{$position}"
            : 'users_with_role_partner_all_positions';

        return Cache::remember($cacheKey, 60, function () use ($position) {
            $query = self::where('role', 'partner');

            if (!empty($position)) {
                $query->where('info->position', $position);
            }

            return $query->get();
        });
    }
}
