<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;


trait OrderedStage
{

    protected static function getSortedOrderList(): array
    {
        return defined(static::class . '::SORTED_ORDER')
            ? static::SORTED_ORDER
            : [];
    }


    public function scopeOrdered(Builder $query, string $column = 'name'): Builder
    {
        $orderList = static::getSortedOrderList();
        if (empty($orderList)) {
            return $query;
        }

        // Quote and implode statuses
        $quoted = array_map(fn ($value) => "'{$value}'", $orderList);
        $list = implode(',', $quoted);

        return $query->orderByRaw("FIELD({$column}, {$list})");
    }


    public function scopeOrderedFallback(Builder $query, string $column = 'name'): Builder
    {
        $orderList = static::getSortedOrderList();
        if (empty($orderList)) {
            return $query;
        }

        $cases = [];
        foreach ($orderList as $index => $value) {
            $position = $index + 1;
            $cases[] = "WHEN {$column} = '" . $value . "' THEN {$position}";
        }

        $caseSql = 'CASE ' . implode(' ', $cases) . ' ELSE ' . (count($orderList) + 1) . ' END';
        return $query->orderByRaw($caseSql);
    }
}
