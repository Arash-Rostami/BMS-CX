<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseStatus extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'user_id'];


    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }

    public function getEmoticonAttribute()
    {
        return explode(' ', $this->name)[0] ?? '';
    }

    public function getBareTitleAttribute()
    {
        $parts = explode(' ', $this->name);
        array_shift($parts);
        return implode(' ', $parts);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
