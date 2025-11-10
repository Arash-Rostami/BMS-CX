<?php

namespace App\Services\Authorities;

namespace App\Services\Authorities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BalanceQueryService extends BaseQueryService
{
    protected function accountantJnr(Builder $q, int $dept): Builder
    {
        return $q->whereIn('department_id', [6, $dept]);
    }

    protected function accountantMdr(Builder $q, int $dept): Builder
    {
        return $q->where(fn($q) => $q->where('department_id', 6))
            ->orWhere(fn($q) => $q
                ->where('department_id', 10)
                ->where('currency', '!=', 'RIAL')
            )
            ->orWhereExists(fn($exists) => $exists
                ->select(DB::raw(1))
                ->from('payments')
                ->join('payment_payment_request', 'payments.id', '=', 'payment_payment_request.payment_id')
                ->join('payment_requests', 'payment_payment_request.payment_request_id', '=', 'payment_requests.id')
                ->whereColumn('balances.currency', 'payments.currency')
                ->whereColumn('balances.payment', 'payments.amount')
                ->whereRaw('ABS(TIMESTAMPDIFF(SECOND, balances.created_at, payments.created_at)) <= 1')
                ->where('payment_requests.extra->paymentMethod', 'cash')
                ->where('payment_requests.currency', '!=', 'RIAL')
                ->whereNull('payments.deleted_at')
                ->whereNull('payment_requests.deleted_at')
            );
    }

    protected function defaultForDepartment(Builder $q, int $dept): Builder
    {
        return $q->whereIn('department_id', [$dept, 0]);
    }

    protected function juniorPosition(Builder $q, User $user): Builder
    {
        return $q->whereExists(function ($exists) use ($user) {
            $exists->select(DB::raw(1))
                ->from('payments')
                ->join('payment_payment_request', 'payments.id', '=', 'payment_payment_request.payment_id')
                ->join('payment_requests', 'payment_payment_request.payment_request_id', '=', 'payment_requests.id')
                ->where('payment_requests.user_id', $user->id)
                ->whereNull('payment_requests.deleted_at')
                ->whereNull('payments.deleted_at')
                ->whereColumn('balances.currency', 'payments.currency')
                ->whereColumn('balances.payment', 'payments.amount')
                ->whereRaw('ABS(TIMESTAMPDIFF(SECOND, balances.created_at, payments.created_at)) <= 1');
        });
    }

    protected function partner(Builder $q): Builder
    {
        return $q->whereIn('currency', ['USD', 'EURO'])
            ->whereExists(fn($exists) => $exists
                ->select(DB::raw(1))
                ->from('payments')
                ->join('payment_payment_request', 'payments.id', '=', 'payment_payment_request.payment_id')
                ->join('payment_requests', 'payment_payment_request.payment_request_id', '=', 'payment_requests.id')
                ->whereColumn('balances.currency', 'payments.currency')
                ->whereColumn('balances.payment', 'payments.amount')
                ->whereRaw('ABS(TIMESTAMPDIFF(SECOND, balances.created_at, payments.created_at)) <= 1')
                ->whereNotNull('payment_requests.account_number')
                ->where('payment_requests.extra->paymentMethod', '<>', 'cash')
                ->whereNull('payments.deleted_at')
                ->whereNull('payment_requests.deleted_at')
            );
    }
}
