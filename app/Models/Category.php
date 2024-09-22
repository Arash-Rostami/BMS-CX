<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'user_id'];


    protected static function booted()
    {
        static::creating(function ($cat) {
            $cat->user_id = auth()->id();
        });
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function orderRequests()
    {
        return $this->hasMany(OrderRequest::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
