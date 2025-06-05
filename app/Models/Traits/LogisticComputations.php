<?php

namespace App\Models\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

trait LogisticComputations
{

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
    
    public static function countByPackagingType($year)
    {
        $cacheKey = 'orders_data_by_category_' . $year;

        return Cache::remember($cacheKey, 300, function () use ($year) {
            $query = self::query()
                ->with('packaging')
                ->selectRaw('packaging_id, count(*) as total')
                ->groupBy('packaging_id');

            if ($year !== 'all') {
                $query->whereHas('order', function ($subQuery) use ($year) {
                    $subQuery->whereYear('proforma_date', $year);
                });
            }

            return $query->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->packaging ? $item->packaging->name : 'Unknown',
                        'total' => $item->total
                    ];
                });
        });
    }
}
