<?php

namespace App\Models;

use App\Models\Traits\DepartmentCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory, DepartmentCache;

    public static $filamentDetection = false;

    protected $fillable = [
        'name',
        'code',
        'description',
    ];


}
