<?php

namespace App\Models;

use App\Models\Traits\DepartmentCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;


class Department extends Model
{
    use DepartmentCache;

    public static $filamentDetection = false;
    public $timestamps = false;
    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    public static function getTabsConfiguration(array $departmentIds, array $counts): Collection
    {
        $departments = Cache::remember('department_list_for_tabs', now()->addHours(24), function () use ($departmentIds) {
            return self::query()
                ->whereIn('id', $departmentIds)
                ->get(['id', 'name'])
                ->map(fn($dept) => tap($dept, function ($d) {
                    $d->simplified_name = match (true) {
                        str_contains($d->name, 'Commercial Import Operation') => 'Import',
                        str_contains($d->name, 'Commercial Export Operation') => 'Export',
                        str_contains($d->name, 'BAZORG (Sales Platform)') => 'BAZORG',
                        default => $d->name,
                    };
                }));
        });

        return $departments->mapWithKeys(fn(self $department): array => [
            $department->name => [
                'label' => $department->simplified_name,
                'query' => fn(Builder $query) => $query->whereRelation('paymentRequests', 'department_id', $department->id),
                'badge' => $counts["department_{$department->id}_count"] ?? 0,
                'icon' => 'heroicon-o-building-office',
            ]
        ]);
    }
}
