<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class ShippingLine extends Model
{
    use HasFactory;
    use QueryCacheable;

    public $cacheFor = 86400;
    public $cacheDriver = 'file';
    public $cacheTags = ['shipping_lines_table'];
    protected static $flushCacheOnUpdate = true;


    protected $fillable = ['name', 'description', 'user_id'];

    protected static function booted()
    {
        static::creating(function ($shippingLine) {
            $shippingLine->user_id = auth()->id();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
