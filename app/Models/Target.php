<?php

namespace App\Models;

use App\Models\Traits\TargetComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;


class Target extends Model
{
    use HasFactory;
    use TargetComputations;
    use QueryCacheable;

    protected static $flushCacheOnUpdate = true;
    public $cacheFor = 86400;
    public $cacheDriver = 'file';
    public $cacheTags = ['target_table'];


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
