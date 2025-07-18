<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

trait LogisticComputations
{

    public static function countByPackagingType(int|string $year): Collection
    {
        $cacheKey = "logistic_packaging_counts_{$year}";

        return Cache::remember($cacheKey, 300, function () use ($year) {
            return self::query()
                ->selectRaw(
                    "coalesce(packagings.name, 'Unknown') as name, count(logistics.id) as total"
                )
                ->leftJoin(
                    "packagings",
                    "packagings.id",
                    '=',
                    "logistics.packaging_id"
                )
                ->leftJoin(
                    "orders",
                    "orders.logistic_id",
                    '=',
                    "logistics.id"
                )
                ->when(
                    $year != 'all',
                    fn(Builder $q) => $q->whereYear("orders.proforma_date", $year)
                )
                ->groupBy("coalesce(packagings.name, 'Unknown')")
                ->get()
                ->map(fn($item) => (array)$item);
        });
    }

    public function getLoadingStartlineAttribute()
    {
        return isset($this->extra['loading_startline'])
            ? Carbon::parse($this->extra['loading_startline'])
            : null;
    }

    public function getEtaAttribute()
    {
        return isset($this->extra['eta'])
            ? Carbon::parse($this->extra['eta'])
            : null;
    }

    public function getEtdAttribute()
    {
        return isset($this->extra['etd'])
            ? Carbon::parse($this->extra['etd'])
            : null;
    }
}
