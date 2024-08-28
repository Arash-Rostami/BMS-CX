<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;


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

    public function order()
    {
        return $this->belongsToMany(Order::class, 'order_tag', 'tag_id', 'order_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = auth()->user()->id ?? null;
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->user()->id ?? null;
        });
    }
}
