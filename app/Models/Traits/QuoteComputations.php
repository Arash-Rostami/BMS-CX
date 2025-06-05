<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Cache;

trait QuoteComputations
{
    public static function countNum($id)
    {
        $cacheKey = 'quote_count_' . $id;

        return Cache::remember($cacheKey, 60, function () use ($id) {
            return self::where('quote_request_id', $id)->count();
        });
    }

    public function scopeLowestCosts($query)
    {
        return $query->orderBy('offered_rate', 'asc')
            ->orderBy('local_charges', 'asc')
            ->orderBy('switch_bl_fee', 'asc');
    }
}
