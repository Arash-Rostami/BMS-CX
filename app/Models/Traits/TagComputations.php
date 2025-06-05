<?php

namespace App\Models\Traits;


use Illuminate\Database\Eloquent\Builder;

trait TagComputations
{
    public function scopeFilteredForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($query) use ($userId) {
            $query->where('created_by', $userId)
                ->orWhere('updated_by', $userId);
        })
            ->where('module', 'Order')
            ->where('extra', '!=', '[]')
            ->whereNotNull('extra');
    }

    public static function deleteEmptyOrderTags($module)
    {
        self::where('module', $module)
            ->where(function ($query) {
                $query->where('extra', '[]')->orWhereNull('extra');
            })
            ->delete();
    }

    public static function formatTags($tags)
    {
        if ($tags->isEmpty()) {
            return 'No Tag Assigned';
        }
        return $tags->unique()->join(', ');
    }
}
