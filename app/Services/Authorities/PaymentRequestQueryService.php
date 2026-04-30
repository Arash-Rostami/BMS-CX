<?php

namespace App\Services\Authorities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PaymentRequestQueryService extends BaseQueryService
{

    private const CX = 6;
    private const PERSORE = 10;
    private const GLOBAL_DEPT = 0;
    private const CASH = 'cash';
    private const RIAL = 'RIAL';
    private const USD = 'USD';
    private const EURO = 'EURO';


    protected function accountantJnr(Builder $q, int $dept): Builder
    {
        return $this->whereDepartmentOrCostCenter($q, [self::CX, $dept]);
    }

    protected function accountantMdr(Builder $q, int $dept): Builder
    {
        $userId = auth()->id();

        return $q->where(fn(Builder $q) => $q
            ->where(fn(Builder $q) => $q
                ->where('department_id', self::CX)
                ->orWhere('cost_center', self::CX)
            )
            ->orWhere(fn(Builder $q) => $q
                ->where(fn(Builder $q) => $q
                    ->where('department_id', $dept)
                    ->orWhere('cost_center', $dept)
                )
                ->where('user_id', $userId)
            )
            ->orWhere(fn(Builder $q) => $q
                ->where(fn(Builder $q) => $q
                    ->where('department_id', self::PERSORE)
                    ->orWhere('cost_center', self::PERSORE)
                    ->orWhere('extra->paymentMethod', self::CASH)
                )
                ->where('currency', '!=', self::RIAL)
            )
        );
    }

    protected function defaultForDepartment(Builder $q, int $dept): Builder
    {
        return $this->whereDepartmentOrCostCenter($q, [$dept, self::GLOBAL_DEPT]);
    }

    protected function juniorPosition(Builder $q, User $user): Builder
    {
        return $q->where('user_id', $user->id);
    }

    protected function partner(Builder $q): Builder
    {
        return $q->whereIn('currency', [self::USD, self::EURO])
            ->whereNotNull('account_number')
            ->where('extra->paymentMethod', '<>', self::CASH);
    }

    private function whereDepartmentOrCostCenter(Builder $q, array $values): Builder
    {
        return $q->where(fn(Builder $q) => $q
            ->whereIn('department_id', $values)
            ->orWhereIn('cost_center', $values));
    }
}
