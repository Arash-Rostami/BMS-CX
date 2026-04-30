<?php

namespace App\Models;

use App\Models\Traits\ProductComputations;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use ProductComputations;

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
