<?php

namespace App\Services\Authorities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PaymentRequestQueryService extends BaseQueryService
{
    protected function accountantJnr(Builder $q, int $dept): Builder
    {
        return $q->where(function (Builder $q) use ($dept) {
            $q->whereIn('department_id', [6, $dept])
                ->orWhereIn('cost_center', [6, $dept]);
        });
    }

    protected function accountantMdr(Builder $q, int $dept): Builder
    {
        return $q->where(fn($q) => $q->where('department_id', 6)->orWhere('cost_center', 6))
            ->orWhere(fn($q) => $q
                ->where(fn($q2) => $q2
                    ->where('department_id', $dept)
                    ->orWhere('cost_center', $dept)
                )->where('user_id', auth()->id())
            )
            ->orWhere(fn($q) => $q
                ->where(fn($q2) => $q2
                    ->where('department_id', 10)
                    ->orWhere('cost_center', 10)
                    ->orWhere('extra->paymentMethod', 'cash')
                )->where('currency', '!=', 'RIAL')
            );
    }

    protected function defaultForDepartment(Builder $q, int $dept): Builder
    {
        return $q->where(fn($q) => $q
            ->whereIn('department_id', [$dept, 0])
            ->orWhereIn('cost_center', [$dept, 0])
        );
    }

    protected function juniorPosition(Builder $q, User $user): Builder
    {
        return $q->where('user_id', $user->id);
    }

    protected function partner(Builder $q): Builder
    {
        return $q->whereIn('currency', ['USD', 'EURO'])
            ->whereNotNull('account_number')
            ->where('extra->paymentMethod', '<>', 'cash');
    }
}
