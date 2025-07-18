<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;


trait OrderedStage
{

    public function scopeOrdered(Builder $query, string $column = 'name'): Builder
    {
        $order = static::getSortedOrderList();

        return $query->when(
            $order,
            fn(Builder $q) => $q->orderByRaw(
                'FIELD(' . $column . ', ' . implode(',', array_fill(0, count($order), '?')) . ')',
                $order
            )
        );
    }

    protected static function getSortedOrderList(): array
    {
        return defined(static::class . '::SORTED_ORDER')
            ? static::SORTED_ORDER
            : [];
    }


    public function scopeOrderedFallback(Builder $query, string $column = 'name'): Builder
    {
        $order = static::getSortedOrderList();

        return $query->when(
            $order,
            fn(Builder $q) => $q->orderByRaw(
                'CASE ' .
                implode(' ', array_map(
                    fn($value, $idx) => "WHEN {$column} = ? THEN " . ($idx + 1),
                    $order,
                    array_keys($order)
                )) .
                ' ELSE ' . (count($order) + 1) . ' END',
                $order
            )
        );
    }
}
