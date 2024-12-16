<?php

namespace App\Services;


use Illuminate\Database\Query\Builder;

class PaymentSummarizer
{

    public static function calculateTotalPaymentCount(Builder $query): int
    {
        return $query
            ->join('payment_payment_request', 'payment_requests.id', '=', 'payment_payment_request.payment_request_id')
            ->join('payments', 'payment_payment_request.payment_id', '=', 'payments.id')
            ->whereNull('payment_requests.deleted_at')
            ->whereNull('payments.deleted_at')
            ->count('payments.id');
    }

    public static function calculateTotalsByCurrency(Builder $query): string
    {
        $totals = $query
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->get()
            ->map(fn($row) => "{$row->currency}: " . number_format($row->total, 1))
            ->implode(' | ');

        return $totals ?: 'No amounts available';
    }
}
