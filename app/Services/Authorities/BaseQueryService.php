<?php

namespace App\Services\Authorities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseQueryService
{
    public function apply(Builder $query, User $user): Builder
    {
        [$department, $position, $role] = $this->getUserDetails($user);

        if ($role === 'partner') {
            return $this->partner($query);
        }

        if ($role === 'accountant' && $position === 'jnr') {
            return $this->accountantJnr($query, $department);
        }

        if ($role === 'accountant' && $position === 'mdr') {
            return $this->accountantMdr($query, $department);
        }

        if (in_array($role, ['admin', 'manager', 'accountant'], true)) {
            return $query;
        }

        if ($position === 'jnr') {
            return $this->juniorPosition($query, $user);
        }

        return $this->defaultForDepartment($query, $department);
    }

    abstract protected function accountantJnr(Builder $q, int $dept): Builder;

    abstract protected function accountantMdr(Builder $q, int $dept): Builder;

    abstract protected function defaultForDepartment(Builder $q, int $dept): Builder;

    protected function getUserDetails(User $user): array
    {
        return [
            (int)($user->info['department'] ?? 0),
            $user->info['position'] ?? null,
            $user->role
        ];
    }

    abstract protected function juniorPosition(Builder $q, User $user): Builder;

    abstract protected function partner(Builder $q): Builder;
}
