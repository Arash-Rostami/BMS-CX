<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    use HasFactory;

    protected $fillable = ['initial', 'amount', 'category', 'category_id', 'extra', 'user_id'];

    protected $casts = [
        'extra' => 'json',
    ];

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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'category_id');
    }

    public function contractor()
    {
        return $this->belongsTo(Contractor::class, 'category_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'category_id');
    }

    public function payee()
    {
        return $this->belongsTo(Payee::class, 'category_id');
    }

    public function getCurrencyAttribute()
    {
        return $this->extra['currency'] ?? null;
    }
}
