<?php

namespace App\Filament\Pages\Trait;


use App\Models\Target;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait ContractStats
{
    public function prepareContractStats(Builder $builder): array
    {
        $wheres = $this->extractWheres($builder);
        $dateFilter = $this->detectDateFilter($wheres);
        $period = $this->periodFromDateRange($dateFilter['from'] ?? null, $dateFilter['to'] ?? null);

        $detectedCategory = $this->detectCategoryFilter($wheres);
        $totalPiQuantity = (clone $builder)
            ->whereNotNull('pi_id')
            ->groupBy('pi_id')
            ->select(DB::raw('MAX(pi_quantity) as pi_quantity'))
            ->pluck('pi_quantity')
            ->sum();


        $agg = (clone $builder)
            ->select(
                DB::raw('COUNT(DISTINCT pi_id) as contract_count'),
                DB::raw($totalPiQuantity . ' as total_pi_quantity'),
                DB::raw('SUM(CASE WHEN purchase_status_id = 1 THEN order_detail_quantity ELSE 0 END) as pending_q'),
                DB::raw('COUNT(CASE WHEN purchase_status_id = 1 THEN 1 END) as pending_count'),
                DB::raw('SUM(CASE WHEN purchase_status_id IN (2,6) THEN order_detail_quantity ELSE 0 END) as released_shipped_q'),
                DB::raw('COUNT(CASE WHEN purchase_status_id IN (2,6) THEN 1 END) as released_shipped_count'),
                DB::raw('SUM(CASE WHEN purchase_status_id IN (3,4,5) THEN order_detail_quantity ELSE 0 END) as delivered_in_transit_custom_q'),
                DB::raw('COUNT(CASE WHEN purchase_status_id IN (3,4,5) THEN 1 END) as delivered_in_transit_custom_count'),
                DB::raw('GROUP_CONCAT(DISTINCT category_id) as category_list')
            )->first();


        $categoryParam = !empty($detectedCategory)
            ? array_values($detectedCategory)
            : (isset($agg->category_list) ? array_map('intval', explode(',', $agg->category_list)) : [1, 2, 3, 4]);

        $targetTotal = $this->sumTargetsForPeriod($period['months_by_year'] ?? [], $categoryParam);


        $metrics = [
            'contract_count' => (int)($agg->contract_count ?? 0),
            'total_pi_quantity' => (float)($agg->total_pi_quantity ?? 0.0),
            'pending_q' => (float)($agg->pending_q ?? 0.0),
            'pending_count' => (int)($agg->pending_count ?? 0),
            'released_shipped_q' => (float)($agg->released_shipped_q ?? 0.0),
            'released_shipped_count' => (int)($agg->released_shipped_count ?? 0),
            'delivered_in_transit_custom_q' => (float)($agg->delivered_in_transit_custom_q ?? 0.0),
            'delivered_in_transit_custom_count' => (int)($agg->delivered_in_transit_custom_count ?? 0),
        ];

        $rates = $this->computeRates($metrics, (float)$targetTotal);

        return [
            'period' => $period,
            'category' => $categoryParam,
            'target_total' => (float)$targetTotal,
            'metrics' => $metrics,
            'rates' => $rates,
        ];
    }

    protected function allMonthKeys(): array
    {
        return ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
    }

    protected function computeRates(array $metrics, float $targetTotal): array
    {
        $totalPi = $metrics['total_pi_quantity'] ?? 0.0;
        $released = $metrics['released_shipped_q'] ?? 0.0;

        $contractFulfillment = $totalPi > 0 ? ($released / $totalPi) * 100.0 : 0.0;
        $targetFulfillment = $targetTotal > 0 ? ($released / $targetTotal) * 100.0 : 0.0;
        $piVsTarget = $targetTotal > 0 ? ($totalPi / $targetTotal) * 100.0 : 0.0;

        return [
            'contract_fulfillment_rate' => $contractFulfillment,
            'target_fulfillment_rate' => $targetFulfillment,
            'pi_vs_target_rate' => $piVsTarget,
        ];
    }

    protected function detectCategoryFilter(array $wheres): array
    {
        foreach ($wheres as $w) {
            if (($w['type'] ?? '') === 'Basic' && ($w['column'] ?? '') === 'category_id') {
                $op = $w['operator'] ?? '=';
                $val = $w['value'] ?? null;
                if ($op === '=' && $val !== null) return [(int)$val];
                if ($op === 'IN' && is_array($val)) return array_map('intval', $val);
            }
            if (($w['type'] ?? '') === 'In' && ($w['column'] ?? '') === 'category_id') {
                return array_map('intval', $w['values'] ?? []);
            }
        }
        return [];
    }

    protected function detectDateFilter(array $wheres): array
    {
        $fields = ['pi_date', 'bl_date'];
        $found = [];
        foreach ($wheres as $w) {
            if (($w['type'] ?? '') === 'Basic' && in_array($w['column'] ?? '', $fields, true)) {
                $col = $w['column'];
                $op = $w['operator'] ?? '=';
                $val = $w['value'] ?? null;
                $found[$col] = $found[$col] ?? ['from' => null, 'to' => null];
                if ($op === '>=' || $op === '>') $found[$col]['from'] = $val;
                if ($op === '<=' || $op === '<') $found[$col]['to'] = $val;
                if ($op === '=' && $val) $found[$col] = ['from' => $val, 'to' => $val];
            }
        }
        if (!empty($found['pi_date'])) return array_merge(['field' => 'pi_date'], $found['pi_date']);
        if (!empty($found['bl_date'])) return array_merge(['field' => 'bl_date'], $found['bl_date']);
        return [];
    }

    protected function extractWheres(Builder $query): array
    {
        return $this->flattenWheres($query->getQuery()->wheres ?? []);
    }

    protected function flattenWheres(array $wheres): array
    {
        $out = [];
        foreach ($wheres as $w) {
            if (($w['type'] ?? null) === 'Nested' && isset($w['query'])) {
                $out = array_merge($out, $this->flattenWheres($w['query']->wheres ?? []));
                continue;
            }
            $out[] = $w;
        }
        return $out;
    }

    protected function periodFromDateRange(?string $from, ?string $to): array
    {
        if (!$from && !$to) {
            $y = now()->year;
            return ['years' => [$y], 'months_by_year' => [$y => $this->allMonthKeys()], 'from' => null, 'to' => null];
        }
        try {
            $start = $from ? Carbon::parse($from)->startOfMonth() : Carbon::parse($to)->startOfMonth();
            $end = $to ? Carbon::parse($to)->endOfMonth() : Carbon::parse($from)->endOfMonth();
        } catch (\Throwable $e) {
            $y = now()->year;
            return ['years' => [$y], 'months_by_year' => [$y => $this->allMonthKeys()], 'from' => null, 'to' => null];
        }
        $monthsByYear = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $monthsByYear[$cursor->year][] = strtolower($cursor->format('F'));
            $cursor->addMonth();
        }
        return [
            'years' => array_keys($monthsByYear),
            'months_by_year' => $monthsByYear,
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
        ];
    }

    protected function pluckCategoryFromBuilder(Builder $builder): array
    {
        return (array)$builder->pluck('category_id')->unique()->filter()->values()->all();
    }

    protected function sumTargetsForPeriod(array $monthsByYear, ?array $category): float
    {
        if (empty($monthsByYear)) return 0.0;
        $categoryParam = $category === [] ? null : $category;

        $result = Target::getTotalTargetsForPeriods($monthsByYear, $categoryParam);
        return (float)($result['grand_total'] ?? 0);
    }
}
