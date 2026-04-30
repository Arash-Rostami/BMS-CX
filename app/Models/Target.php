<?php

namespace App\Models;

use App\Models\Traits\TargetComputations;
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;


class Target extends Model
{
    use TargetComputations;


    protected $fillable = [
        'year',
        'month',
        'target_quantity',
        'modified_target_quantity',
        'category_id',
        'user_id',
        'extra',
    ];

    protected $with = ['category', 'user'];


    protected $casts = [
        'month' => 'array',
        'extra' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::creating(fn($target) => $target->user_id = auth()->id());
    }
}
