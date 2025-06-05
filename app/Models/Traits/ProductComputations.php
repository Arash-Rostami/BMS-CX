<?php

namespace App\Models\Traits;

trait ProductComputations
{
    public static function scopeFilterCategory($query, $categoryIds)
    {
        if (!empty($categoryIds)) {
            return $query->where('category_id', $categoryIds);
        }

        return $query;
    }
}
