<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ProductComputations
{
    public function scopeFilterCategory(Builder $query, array|int|null|string $categoryIds): Builder
    {
        return $query->when($categoryIds, fn(Builder $q) => $q->whereIn('category_id', (array)$categoryIds));
    }
}
