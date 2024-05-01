<?php

namespace App\Models;

use Egulias\EmailValidator\Result\Reason\Reason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allocation extends Model
{
    use HasFactory;

    protected $fillable = ['reason', 'department', 'extra'];

    protected $casts = [
        'extra' => 'json',
    ];


    public static function reasonsForDepartment(string $department): array
    {
        return self::where('department', $department)
            ->pluck('reason', 'id')
            ->toArray();
    }


    public static function getUniqueReasonsForCPS(string $department): array
    {
        return self::whereIn('department', [$department, 'all'])
            ->distinct()
            ->pluck('reason', 'id')
            ->toArray();
    }

}
