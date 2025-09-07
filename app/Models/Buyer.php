<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Buyer extends Model
{
    use HasFactory;
    use QueryCacheable;

    public $cacheFor = 86400;
    public $cacheDriver = 'file';
    public $cacheTags = ['buyers_table'];
    protected static $flushCacheOnUpdate = true;


    protected $fillable = ['name', 'description', 'user_id'];

    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
