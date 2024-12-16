<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;

class BalanceSummarizer
{
    /**
     * Summarize balances grouped by currency.
     *
     * @param Builder $query
     * @return array
     */
    public static function summarizeByCurrency(Builder $query): array
    {
        return $query
            ->selectRaw('
                currency,
                SUM(COALESCE(base, 0)) as base_sum,
                SUM(COALESCE(payment, 0)) as payment_sum,
                SUM(COALESCE(base, 0) + COALESCE(payment, 0)) as total_sum
            ')
            ->groupBy('currency')
            ->get()
            ->map(fn($row) => [
                'currency' => $row->currency,
                'base_sum' => (float) $row->base_sum,
                'payment_sum' => (float) $row->payment_sum,
                'total_sum' => (float) $row->total_sum,
            ])
            ->toArray();
    }


    public static function formatSummaryOutput(array $summaries): string
    {
        return collect($summaries)
            ->map(function ($summary) {
                $base = number_format((float) $summary['base_sum'], 1);
                $payment = number_format((float) $summary['payment_sum'], 1);
                $total = number_format((float) $summary['total_sum'], 1);

                if ((float) $summary['base_sum'] == 0) {
                    return "{$summary['currency']}: $total";
                }
                return "{$summary['currency']} - Base: $base, Payment: $payment, Total: $total";
            })
            ->implode(' | ');
    }
}
