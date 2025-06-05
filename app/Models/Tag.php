<?php

namespace App\Models;

use App\Models\Traits\TagComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory, TagComputations;

    public static $filamentDetection = false;


    protected $fillable = [
        'name',
        'module',
        'extra',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'extra' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            $tag->created_by = auth()->user()->id ?? null;
        });

        static::updating(function ($tag) {
            $tag->updated_by = auth()->user()->id ?? null;
        });
    }

    public function order()
    {
        return $this->belongsToMany(Order::class, 'order_tag', 'tag_id', 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
