<?php

namespace App\Services\Authorities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PaymentQueryService
{
    public function apply(Builder $query, User $user): Builder
    {
        return $query->whereHas(
            'paymentRequests',
            fn(Builder $subQuery) => app(PaymentRequestQueryService::class)->apply($subQuery, $user)
        );
    }
}
