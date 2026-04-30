<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SmartCacheManager
{
    private const CACHE_PREFIX = 'smart_v4_';
    private const VERSION_TTL  = 1440;

    public static function invalidate(string $model): void
    {
        $versionKey = self::versionKey($model);
        Cache::put($versionKey, now()->timestamp, now()->addMinutes(self::VERSION_TTL));
    }

    public static function remember(string $model, array $filters, int $minutes, callable $callback)
    {
        $version = Cache::get(self::versionKey($model), 0);
        $key     = self::generateKey($model, $filters, $version);

        return Cache::remember($key, now()->addMinutes($minutes), $callback);
    }

    private static function versionKey(string $model): string
    {
        return self::CACHE_PREFIX . strtolower($model) . '_version';
    }

    private static function generateKey(string $model, array $filters, mixed $version): string
    {
        $hash = md5(json_encode($filters) . $version);
        return self::CACHE_PREFIX . strtolower($model) . '_' . substr($hash, 0, 16);
    }
}
