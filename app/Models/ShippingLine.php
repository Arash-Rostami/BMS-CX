<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingLine extends Model
{
    use HasFactory;


    protected $fillable = ['name', 'description', 'user_id'];

    protected static function booted()
    {
        static::creating(function ($shippingLine) {
            $shippingLine->user_id = auth()->id();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
