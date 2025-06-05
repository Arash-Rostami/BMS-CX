<?php

namespace App\Models;

use App\Models\Traits\TargetComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Target extends Model
{
    use HasFactory, TargetComputations;

    protected $fillable = [
        'year',
        'month',
        'target_quantity',
        'modified_target_quantity',
        'category_id',
        'user_id',
        'extra',
    ];

    protected $casts = [
        'month' => 'array',
        'extra' => 'array',
    ];

    protected static function booted()
    {
        static::creating(fn($target) => $target->user_id = auth()->id());
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
