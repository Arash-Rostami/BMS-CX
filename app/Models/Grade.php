<?php

namespace App\Models;

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

    public static function scopeFilterCategory($query, $productId)
    {
        if (!empty($productId)) {
            return $query->where('product_id', $productId);
        }

        return $query;
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
