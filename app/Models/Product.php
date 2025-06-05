<?php

namespace App\Models;

use App\Models\Traits\ProductComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, ProductComputations;

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

    public function orders()
    {
        return $this->hasMany(Order::class);
    }


    public function grades()
    {
        return $this->hasMany(Grade::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
