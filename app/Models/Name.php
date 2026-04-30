<?php

namespace App\Models;

use App\Models\Traits\NameCache;
use Illuminate\Database\Eloquent\Model;

class Name extends Model
{
    use NameCache;

    public static $filamentDetection = false;


    protected $fillable = ['name', 'title', 'module', 'extra', 'user_id'];

    protected $casts = [
        'extra' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::creating(function ($name) {
            $name->user_id = auth()->id();
        });
    }
}
