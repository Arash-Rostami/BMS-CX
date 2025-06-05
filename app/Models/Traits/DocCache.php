<?php

namespace App\Models\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

trait DocCache
{
    public static function getLatestBLDate(): ?string
    {
        return Cache::remember('latest_bl_date', now()->addMinutes(15), function () {
            $latestBLDate = self::whereNotNull('BL_date')
                ->orderByDesc('BL_date')
                ->value('BL_date');

            return $latestBLDate ? Carbon::parse($latestBLDate)->format('j F Y') : 'N/A';
        });
    }
}
