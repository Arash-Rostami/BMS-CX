<?php

namespace App\Filament\Pages\Trait;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

trait AccountStatementStats
{
    protected function prepareAccountStatementStats(Builder $query): array
    {
        $cacheKey = 'account_statements_stats_' . md5($query->toSql() . serialize($query->getBindings()));

        $totals = Cache::remember($cacheKey, now()->addMinutes(15), fn () => [
            'debit' => (clone $query)->sum('debit'),
            'credit' => (clone $query)->sum('credit'),
            'accrued' => (clone $query)->sum('accrued_interest'),
        ]);

        $totalDebit = (float)$totals['debit'];
        $totalCredit = (float)$totals['credit'];
        $totalAccrued = (float)$totals['accrued'];
        $netMovement = $totalDebit - $totalCredit + $totalAccrued;

        $isNetPositive = $netMovement >= 0;

        $netBg = $isNetPositive
            ? 'light-dark(linear-gradient(135deg, #fef2f2 0%, #ffffff 100%), linear-gradient(135deg, #7f1d1d 0%, #450a0a 100%))'
            : 'light-dark(linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%), linear-gradient(135deg, #14532d 0%, #052e16 100%))';

        $netBorder = $isNetPositive ? 'light-dark(#fecaca, #dc2626)' : 'light-dark(#bbf7d0, #16a34a)';
        $netBorderBottom = $isNetPositive ? '#dc2626' : '#16a34a';
        $netShadow = $isNetPositive
            ? 'light-dark("0 10px 25px rgba(220,38,38,0.15)", "0 10px 25px rgba(220,38,38,0.25)")'
            : 'light-dark("0 10px 25px rgba(22,163,74,0.15)", "0 10px 25px rgba(22,163,74,0.25)")';

        return compact(
            'totalDebit',
            'totalCredit',
            'totalAccrued',
            'netMovement',
            'isNetPositive',
            'netBg',
            'netBorder',
            'netBorderBottom',
            'netShadow'
        );
    }
}
