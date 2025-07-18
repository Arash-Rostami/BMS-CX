<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'user_id', 'product_id'];

    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }

    public function scopeFilterByProduct(Builder $query, array|int|null|string $productId = null): Builder
    {
        return $query->when($productId, fn(Builder $q) => $q->whereIn('product_id', (array)$productId));
    }

    public function proformaInvoices()
    {
        return $this->hasMany(ProformaInvoice::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }


    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
