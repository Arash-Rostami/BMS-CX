<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'user_id', 'category_id'];

    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public static function scopeFilterCategory($query, $categoryIds)
    {
        if (!empty($categoryIds)) {
            return $query->where('category_id', $categoryIds);
        }

        return $query;
    }


    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function orderRequests()
    {
        return $this->hasMany(OrderRequest::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
