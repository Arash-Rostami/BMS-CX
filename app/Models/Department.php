<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Department extends Model
{
    use HasFactory;

    public static $filamentDetection = false;

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

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
            $department = self::where('code', $code)->first();
            return $department ? $department->description : null;
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
        return self::get()
            ->sortBy('name')
            ->partition(fn($item) => $item->id === 0)
            ->flatMap(fn($items) => $items)
            ->pluck('name', 'id')
            ->toArray();
        });
    }

    public static function getAllDepartmentCodes()
    {
//        $cacheKey = 'all_department_names';
//
//        return Cache::remember($cacheKey, 60, function () {
            return self::get()
                ->sortBy('name')
                ->map(function ($item) {
                    $item->code = $item->id == 0 ? 'all' : $item->code;
                    return $item;
                })
                ->partition(fn($item) => $item->id === 0)
                ->flatMap(fn($items) => $items)
                ->pluck('name', 'code')
                ->toArray();
//        });
    }
}
