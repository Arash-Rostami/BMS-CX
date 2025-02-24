<?php

namespace App\Services\Repository;

use App\Models\PaymentRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class PaymentRequestRepository
{
    public Builder $query;

    public function __construct()
    {
        $this->query = PaymentRequest::query()->authorizedForUser(auth()->user());
    }

    public function filterByAttributes(array $filters): self
    {
        $query = clone $this->query;

        if (!empty($filters['department']) && $filters['department'] != 0) {
            $dep = (int)$filters['department'];
            $query->where('department_id', $dep);
        }
        if (!empty($filters['payment_type'])) {
            $query->where('type_of_payment', $filters['payment_type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        $this->query = $query;
        return $this;
    }

    public function fetchDailyStatistics(): array
    {
        $query = clone $this->query;

        return $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }

    public function fetchWeeklyStatistics(): array
    {
        $query = clone $this->query;

        return $query->selectRaw('WEEK(created_at) as week, YEAR(created_at) as year, COUNT(*) as count')
            ->groupBy('week', 'year')
            ->orderBy('year')
            ->orderBy('week')
            ->get()
            ->mapWithKeys(fn($item) => [$item->year . '-W:' . $item->week => $item->count])
            ->toArray();
    }

    public function fetchMonthlyStatistics(): array
    {
        $query = clone $this->query;

        return $query->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
            ->groupBy('month', 'year')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(fn($item) => [
                Carbon::createFromDate($item->year, $item->month, 1)->format('Y-m') => $item->count,
            ])
            ->toArray();
    }

    public function fetchStatisticsByPeriod(string $period): array
    {
        $filters = $this->query->getQuery()->wheres;
        $cacheKey = $this->generateCacheKey("statistics_by_period_{$period}", $filters);

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($period) {
            return match ($period) {
                'daily' => $this->fetchDailyStatistics(),
                'weekly' => $this->fetchWeeklyStatistics(),
                default => $this->fetchMonthlyStatistics(),
            };
        });
    }

    public function retrieveFilteredSummary(): array
    {
        $filters = $this->query->getQuery()->wheres;
        $cacheKey = $this->generateCacheKey('filtered_summary', $filters);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $query = clone $this->query;
            return [
                'total_requested_amount' => $query->sum('requested_amount'),
                'total_beneficiaries' => $query->distinct('payee_id')->count('payee_id'),
                'total_contractors' => $query->distinct('contractor_id')->count('contractor_id'),
                'total_suppliers' => $query->distinct('supplier_id')->count('supplier_id'),
            ];
        });
    }

    public function filterByTimePeriod(string $period): self
    {
        $filters = $this->query->getQuery()->wheres;
        $cacheKey = $this->generateCacheKey("filtered_data_by_{$period}", $filters);

        Cache::remember($cacheKey, now()->addMinutes(2), function () use ($period) {
            $query = clone $this->query;
            match ($period) {
                'daily' => $query->whereBetween('created_at', [now()->subDays(4), now()]),
                'weekly' => $query->whereBetween('created_at', [now()->subWeeks(2), now()]),
                'monthly' => $query->where(function ($q) {
                    $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                })->orWhere(function ($q) {
                    $q->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year);
                }),
                default => $query,
            };
            $this->query = $query;
        });

        return $this;
    }


    private function generateCacheKey(string $type, array $filters = []): string
    {
        $filterString = collect($filters)
            ->map(fn($value, $key) => is_array($value) ? "$key:" . json_encode($value) : "$key:$value")
            ->sortKeys()
            ->join('|');

        return 'payment_requests:' . $type . ($filterString ? ":$filterString" : '');
    }
}
