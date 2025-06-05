<?php

namespace App\Models\Traits;

trait NameCache
{
    public static function getSortedNamesForModule(string $module): array
    {
        return self::where('module', $module)
            ->orderBy('title')
            ->pluck('title', 'title')
            ->toArray();
    }
}
