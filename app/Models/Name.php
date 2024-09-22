<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Name extends Model
{
    use HasFactory;

    public static $filamentDetection = false;


    protected $fillable = ['name', 'title', 'module', 'extra', 'user_id'];

    protected $casts = [
        'extra' => 'array'
    ];

    protected static function booted()
    {
        static::creating(function ($name) {
            $name->user_id = auth()->id();
        });
    }

    public static function getSortedNamesForModule(string $module): array
    {
        return self::where('module', $module)
            ->orderBy('title')
            ->pluck('title', 'title')
            ->toArray();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
