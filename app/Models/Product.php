<?php

namespace App\Models;

use App\Models\Traits\ProductComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Product extends Model
{
    use HasFactory;
    use ProductComputations;
    use QueryCacheable;

    public $cacheFor = 86400;
    public $cacheDriver = 'file';
    public $cacheTags = ['products_table'];
    protected static $flushCacheOnUpdate = true;

    protected $fillable = ['name', 'description', 'user_id', 'category_id'];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

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
