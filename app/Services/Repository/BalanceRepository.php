<?php

namespace App\Services\Repository;

use App\Models\Balance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class BalanceRepository
{
    public Builder $query;

    public function __construct()
    {
        $this->query = Balance::query();
    }

    public function filterByAttributes(array $filters): self
    {
        $query = clone $this->query;

        if (!empty($filters['department']) && $filters['department'] != 0) {
            $query->where('department_id', $filters['department']);
        }
        if (!empty($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        $this->query = $query;
        return $this;
    }

    public function fetchCategoryShareByCurrency(): array
    {
        $filters = $this->query->getQuery()->wheres;
        $cacheKey = $this->generateCacheKey('category_share_by_currency', $filters);

//        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($currency) {
        $query = clone $this->query;

        return $query->selectRaw('category, currency, SUM(COALESCE(base + payment, payment)) as total_amount')
            ->groupBy('category', 'currency')
            ->get()
            ->groupBy('currency')
            ->map(function ($items) {
                return $items->mapWithKeys(fn($item) => [
                    $item->category => ['total' => (float)$item->total_amount],
                ]);
            })
            ->toArray();
//        });
    }

    public function fetchDetailedBreakdown(): array
    {
        $filters = $this->query->getQuery()->wheres;
        $cacheKey = $this->generateCacheKey('detailed_breakdown', $filters);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $query = clone $this->query;

            return $query->selectRaw('category, category_id, currency, SUM(COALESCE(base + payment, payment)) as total_amount')
                ->with(['supplier:id,name', 'contractor:id,name', 'beneficiary:id,name'])
                ->groupBy('category', 'category_id', 'currency')
                ->get()
                ->map(function ($item) {
                    $name = match ($item->category) {
                        'suppliers' => $item->supplier->name ?? 'Unknown',
                        'contractors' => $item->contractor->name ?? 'Unknown',
                        'beneficiaries' => $item->beneficiary->name ?? 'Unknown',
                        default => 'Unknown',
                    };

                    return [
                        'name' => $name,
                        'total' => $item->total_amount,
                        'currency' => $item->currency,
                    ];
                })
                ->toArray();
        });
    }

    public function filterByTimePeriod(string $period): self
    {
        $query = clone $this->query;

        match ($period) {
            'daily' => $query->whereBetween('created_at', [now()->subDays(7), now()]),
            'weekly' => $query->whereBetween('created_at', [now()->subWeeks(2), now()]),
            'monthly' => $query->whereMonth('created_at', now()->subDays(31))
                ->whereYear('created_at', now()->year),
            default => $query,
        };

        $this->query = $query;
        return $this;
    }

    private function generateCacheKey(string $type, array $filters = []): string
    {
        $filterString = collect($filters)
            ->map(fn($value, $key) => is_array($value) ? "$key:" . json_encode($value) : "$key:$value")
            ->sortKeys()
            ->join('|');

        return 'balance:' . $type . ($filterString ? ":$filterString" : '');
    }
}
