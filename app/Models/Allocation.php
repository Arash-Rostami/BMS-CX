<?php

namespace App\Models;

use Egulias\EmailValidator\Result\Reason\Reason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Allocation extends Model
{
    use HasFactory;

    protected $fillable = ['reason', 'department', 'extra'];

    protected $casts = [
        'extra' => 'json',
    ];


    public static function reasonsForDepartment(string $department): array
    {
        $cacheKey = 'reasons_for_department_' . $department;

        return Cache::remember($cacheKey, 60, function () use ($department) {
            return self::where('department', $department)
                ->pluck('reason', 'id')
                ->toArray();
        });
    }

    public static function getUniqueReasonsForCPS(string $department): array
    {
        $cacheKey = 'unique_reasons_for_cps_' . $department;

        return Cache::remember($cacheKey, 60, function () use ($department) {
            return self::whereIn('department', [$department, 'all'])
                ->distinct()
                ->pluck('reason', 'id')
                ->toArray();
        });
    }

}
