<?php

namespace App\Models\Traits;


use Illuminate\Database\Eloquent\Builder;

trait TagComputations
{
    public static function deleteEmptyOrderTags($module)
    {
        self::where('module', $module)
            ->where(fn($query) => $query->where('extra', '[]')->orWhereNull('extra'))
            ->delete();
    }

    public static function formatTags($tags)
    {
        return $tags
            ->unique()
            ->whenEmpty(fn() => collect(['No Tag Assigned']))
            ->join(', ');
    }

    public function scopeFilteredForUser(Builder $query, int $userId): Builder
    {
        return $query
            ->where(function ($query) use ($userId) {
                $query->where('created_by', $userId)
                    ->orWhere('updated_by', $userId);
            })
            ->where(function ($query) {
                $query->where('extra', '!=', '[]')
                    ->whereNotNull('extra');
            })
            ->where('module', 'Order');
    }
}
