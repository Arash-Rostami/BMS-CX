<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{


    protected $fillable = ['name', 'description', 'user_id', 'product_id'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function proformaInvoices()
    {
        return $this->hasMany(ProformaInvoice::class);
    }

    public function scopeFilterByProduct(Builder $query, array|int|null|string $productId = null): Builder
    {
        return $query->when($productId, fn(Builder $q) => $q->whereIn('product_id', (array)$productId));
    }

    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }
}
