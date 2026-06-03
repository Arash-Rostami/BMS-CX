<?php

namespace App\Filament\Pages\Trait;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

trait LoanSummaryStats
{
    protected function prepareLoanSummaryStats(Builder $query): array
    {
        $cacheKey = 'loan_summary_stats_' . md5($query->toSql() . serialize($query->getBindings()));

        $totals = Cache::remember($cacheKey, now()->addMinutes(15), fn () => [
            'total_disbursed'     => (clone $query)->where('entry_type', 'disbursement')->sum('total_disbursed_base'),
            'applied_credit'      => (clone $query)->sum('applied_credit_amount'),
            'remaining'           => (clone $query)->sum('remaining_principal'),
            'avg_duration'        => (clone $query)->where('entry_type', 'disbursement')->avg('avg_duration_days'),
            'avg_settlement'      => (clone $query)->where('entry_type', 'disbursement')->avg('avg_settlement_amount'),
            'total_settled'       => (clone $query)->where('entry_type', 'disbursement')->where('is_settled', true)->count(),
            'total_unsettled'     => (clone $query)->where('entry_type', 'disbursement')->where('is_settled', false)->count(),
            'total_disbursements' => (clone $query)->where('entry_type', 'disbursement')->count(),
        ]);

        $totalDisbursed     = (float) $totals['total_disbursed'];
        $appliedCredit      = (float) $totals['applied_credit'];
        $remainingPrincipal = (float) $totals['remaining'];
        $avgDuration        = (float) $totals['avg_duration'];
        $avgSettlement      = (float) $totals['avg_settlement'];
        $totalSettled       = (int) $totals['total_settled'];
        $totalUnsettled     = (int) $totals['total_unsettled'];
        $totalDisbursements = (int) $totals['total_disbursements'];

        $settlementRate = $totalDisbursements > 0
            ? round(($totalSettled / $totalDisbursements) * 100, 1)
            : 0;

        $hasOutstanding = $remainingPrincipal > 0;

        $outstandingBg = $hasOutstanding
            ? 'light-dark(linear-gradient(135deg, #fffbeb 0%, #ffffff 100%), linear-gradient(135deg, #78350f 0%, #451a03 100%))'
            : 'light-dark(linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%), linear-gradient(135deg, #14532d 0%, #052e16 100%))';

        $outstandingBorder = $hasOutstanding
            ? 'light-dark(#fde68a, #d97706)'
            : 'light-dark(#bbf7d0, #16a34a)';

        $outstandingBorderBottom = $hasOutstanding ? '#d97706' : '#16a34a';
        $outstandingColor = $hasOutstanding ? 'warning' : 'success';
        $outstandingIcon = $hasOutstanding ? 'heroicon-m-clock' : 'heroicon-m-check-circle';

        return compact(
            'totalDisbursed',
            'appliedCredit',
            'remainingPrincipal',
            'avgDuration',
            'avgSettlement',
            'totalSettled',
            'totalUnsettled',
            'totalDisbursements',
            'settlementRate',
            'hasOutstanding',
            'outstandingBg',
            'outstandingBorder',
            'outstandingBorderBottom',
            'outstandingColor',
            'outstandingIcon'
        );
    }
}
