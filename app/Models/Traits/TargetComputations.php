<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait TargetComputations
{

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

    public static function getTotalTargetsForPeriods(array $monthsByYear, $category_id = null): array
    {
        if (empty($monthsByYear)) return ['by_year' => [], 'grand_total' => 0];
        $allowedMonths = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];

        $normalized = [];
        foreach ($monthsByYear as $y => $months) {
            $year = (int)$y;
            $mList = is_array($months) ? $months : [$months];
            $mClean = [];
            foreach ($mList as $m) {
                $m = strtolower((string)$m);
                if (in_array($m, $allowedMonths, true)) {
                    $mClean[] = $m;
                }
            }
            if (!empty($mClean)) $normalized[$year] = array_values(array_unique($mClean));
        }
        if (empty($normalized)) return ['by_year' => [], 'grand_total' => 0];


        $cacheKey = self::generateCacheKey('targets_total_quantity_multi', $normalized, $category_id);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($normalized, $category_id) {
            $sqlParts = [];
            $bindings = [];

            foreach ($normalized as $year => $months) {
                $sumParts = [];
                foreach ($months as $m) {
                    $sumParts[] = "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`month`, '$.$m'))+0, 0)";
                }
                $expr = implode(' + ', $sumParts);

                $part = "SELECT ? AS year, COALESCE(SUM( ($expr) ), 0) AS total FROM targets WHERE year = ?";
                $bindings[] = $year;
                $bindings[] = $year;

                if ($category_id !== null) {
                    if (is_array($category_id) && count($category_id) > 0) {
                        $placeholders = implode(',', array_fill(0, count($category_id), '?'));
                        $part .= " AND category_id IN ($placeholders)";
                        foreach ($category_id as $c) $bindings[] = $c;
                    } elseif (!is_array($category_id)) {
                        $part .= " AND category_id = ?";
                        $bindings[] = $category_id;
                    }
                }

                $sqlParts[] = $part;
            }


            $rows = DB::select(implode(' UNION ALL ', $sqlParts), $bindings);

            $byYear = [];
            $grand = 0;
            foreach ($rows as $r) {
                $y = (int)$r->year;
                $t = (int)($r->total ?? 0);
                $byYear[$y] = $t;
                $grand += $t;
            }

            foreach (array_keys($normalized) as $y) {
                if (!isset($byYear[$y])) $byYear[$y] = 0;
            }
            ksort($byYear);

            return ['by_year' => $byYear, 'grand_total' => (int)$grand];
        });
    }

    private static function generateCacheKey(string $prefix, $yearOrYears, $category, $month = null): string
    {
        $categoryCacheKey = is_array($category) ? implode('_', $category) : ($category ?? 'all');

        if (!is_array($yearOrYears)) {
            $monthCacheKey = is_array($month) ? implode('_', $month) : ($month ?? 'all');
            return $prefix . '_' . $yearOrYears . '_' . $categoryCacheKey . '_' . $monthCacheKey;
        }

        $yearsCacheKey = md5(json_encode($yearOrYears));
        return $prefix . '_multi_' . $yearsCacheKey . '_' . $categoryCacheKey;
    }
}
