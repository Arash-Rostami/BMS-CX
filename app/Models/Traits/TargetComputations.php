<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait TargetComputations
{
    public static function generateCacheKey(string $prefix, $year, $category, $month): string
    {
        $categoryCacheKey = is_array($category) ? implode('_', $category) : ($category ?? 'all');
        $monthCacheKey = is_array($month) ? implode('_', $month) : ($month ?? 'all');
        return $prefix . '_' . $year . '_' . $categoryCacheKey . '_' . $monthCacheKey;
    }

    public static function getTotalTargetQuantityByYearCategoryAndMonth($year, $category_id = null, $month = null): int
    {
        $cacheKey = self::generateCacheKey('targets_total_quantity', $year, $category_id, $month);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($year, $category_id, $month) {
            $query = "SELECT ";
            $bindings = [$year];

            if ($month && $month !== 'all') {
                $monthsToProcess = is_array($month) ? $month : [$month];
                $sumExpressions = [];
                foreach ($monthsToProcess as $monthValue) {
                    $sumExpressions[] = "SUM(JSON_UNQUOTE(JSON_EXTRACT(`month`, '$." . $monthValue . "')))";
                }
                $query .= implode(' + ', $sumExpressions) . " AS total ";
            } else {
                $query .= "COALESCE(SUM(modified_target_quantity), SUM(target_quantity)) AS total ";
            }

            $query .= "FROM targets WHERE year = ?";

            if ($category_id) {
                if (is_array($category_id)) {
                    $placeholders = implode(',', array_fill(0, count($category_id), '?'));
                    $query .= " AND category_id IN ($placeholders)";
                    $bindings = array_merge($bindings, $category_id);
                } else {
                    $query .= " AND category_id = ?";
                    $bindings[] = $category_id;
                }
            }

            $result = DB::selectOne($query, $bindings);
            return $result->total ?? 0;
        });
    }
}
