<?php

namespace App\Models\Traits;

use App\Services\SmartCacheManager;

trait NameCache
{
    public static function getSortedNamesForModule(string $module): array
    {
        $filters = ['module' => $module, 'type' => 'sorted_titles'];

        return SmartCacheManager::remember('Name', $filters, 720, function () use ($module) {
            return self::where('module', $module)
                ->orderBy('title')
                ->pluck('title', 'title')
                ->all();
        });
    }
}
