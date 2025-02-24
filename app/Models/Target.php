<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Target extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'target_quantity',
        'modified_target_quantity',
        'category_id',
        'user_id',
        'extra',
    ];

    protected $casts = [
        'month' => 'array',
        'extra' => 'array',
    ];

    protected static function booted()
    {
        static::creating(fn($target) => $target->user_id = auth()->id());
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // DEPRECATED as they belonged to single filter QUERY
//    public static function getTotalTargetQuantityByYearCategoryAndMonth($year, $category_id = null, $month = null)
//    {
//        $monthCacheKey = is_array($month) ? implode('_', $month) : ($month ?? 'all');
//        $categoryCacheKey = is_array($category_id) ? implode('_', $category_id) : ($category_id ?? 'all');
//        $cacheKey = 'targets_total_quantity_' . $year . '_' . $categoryCacheKey . '_' . $monthCacheKey;
//
//        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($year, $category_id, $month) {
//            $query = self::where('year', $year)->when($category_id, function ($q) use ($category_id) {
//                if (is_array($category_id)) {
//                    $q->whereIn('category_id', $category_id);
//                } else {
//                    $q->where('category_id', $category_id);
//                }
//            });
//
//            if ($month && $month !== 'all') {
//                $results = $query->select('month')->get();
//                $totalQuantity = 0;
//
//                $monthsToProcess = is_array($month) ? $month : [$month];
//
//                foreach ($monthsToProcess as $monthValue) {
//                    $totalQuantity += $results->reduce(function ($carry, $row) use ($monthValue) {
//                        $monthlyTargets = is_array($row->month)
//                            ? $row->month
//                            : json_decode($row->month, true) ?? [];
//                        $carry += (int)($monthlyTargets[$monthValue] ?? 0);
//                        return $carry;
//                    }, 0);
//                }
//                return $totalQuantity;
//
//            } else {
//                return $query->selectRaw('COALESCE(SUM(modified_target_quantity), SUM(target_quantity)) AS total')->value('total') ?: 0;
//            }
//        });
//    }

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

    private static function generateCacheKey(string $prefix, $year, $category, $month): string
    {
        $categoryCacheKey = is_array($category) ? implode('_', $category) : ($category ?? 'all');
        $monthCacheKey = is_array($month) ? implode('_', $month) : ($month ?? 'all');
        return $prefix . '_' . $year . '_' . $categoryCacheKey . '_' . $monthCacheKey;
    }
}
