<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class PortOfDelivery extends Model
{
    use HasFactory;
    use QueryCacheable;

    protected static $flushCacheOnUpdate = true;
    public $cacheFor = 86400;
    public $cacheDriver = 'file';
    public $cacheTags = ['port_of_deliveries_table'];
    protected $fillable = ['name', 'description', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }
}
