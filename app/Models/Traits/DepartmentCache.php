<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Cache;

trait DepartmentCache
{
    public static function getByName($id)
    {
        $cacheKey = 'department_name_' . $id;

        return Cache::remember($cacheKey, 60, function () use ($id) {
            return self::find($id)->name;
        });
    }

    public static function getByCode($id)
    {
        $cacheKey = 'department_code_' . $id;

        return Cache::remember($cacheKey, 60, function () use ($id) {
            return self::find($id)->code;
        });
    }

    public static function getDescriptionByCode($code)
    {
        $cacheKey = 'department_description_' . $code;

        return Cache::remember($cacheKey, 60, function () use ($code) {
            return optional(self::where('code', $code)->first())->description;
        });
    }

    public static function getAllDepartments()
    {
        $cacheKey = 'all_departments';

        return Cache::remember($cacheKey, 60, function () {
            return self::pluck('code')->toArray();
        });
    }

    public static function getAllDepartmentNames()
    {
        $cacheKey = 'all_department_names';

        return Cache::remember($cacheKey, 60, function () {
            return self::query()
                ->orderByRaw('CASE WHEN id = 0 THEN 0 ELSE 1 END, name ASC')
                ->get(['id', 'name'])
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    public static function getAllDepartmentCodes()
    {
        $cacheKey = 'all_department_names';

        return Cache::remember($cacheKey, 60, function () {
            return self::query()
                ->orderByRaw('CASE WHEN id = 0 THEN 0 ELSE 1 END, name ASC')
                ->get(['id', 'code', 'name'])
                ->mapWithKeys(fn($item) => [($item->id == 0) ? 'all' : $item->code => $item->name])
                ->toArray();
        });
    }

    public static function getSimplifiedDepartments()
    {
        return Cache::remember('simplified_departments', 60,
            fn() => self::orderBy('name')->get()->map(fn($dept) => tap($dept, function ($d) {
                $d->simplified_name = match (true) {
                    str_contains($d->name, 'Commercial Import Operation') => 'Import',
                    str_contains($d->name, 'Commercial Export Operation') => 'Export',
                    str_contains($d->name, 'BAZORG (Sales Platform)') => 'BAZORG',
                    default => $d->name,
                };
            }))
        );
    }
}
