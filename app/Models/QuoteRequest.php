<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuoteRequest extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'origin_port',
        'destination_port',
        'container_type',
        'requires_switch_bl',
        'commodity',
        'packing',
        'gross_weight',
        'quantity',
        'target_of_rate',
        'target_thc',
        'target_local_charges',
        'target_switch_bl_fee',
        'validity',
        'extra',
        'user_id',
    ];

    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }


    public function user()
    {
        return $this->belongsTo(User::class); // Assuming a User model for request owners
    }

    public function quoteTokens()
    {
        return $this->hasMany(QuoteToken::class);
    }

    public function quotes()
    {
        return $this->hasManyThrough(Quote::class, QuoteToken::class);
    }
}
