<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderDetail extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'order_details';


    protected $fillable = [
        'buying_quantity',
        'initial_quantity',
        'provisional_quantity',
        'final_quantity',
        'buying_price',
        'initial_price',
        'provisional_price',
        'final_price',
        'extra',
        'user_id',
        'order_id',
    ];

    protected $casts = [
        'extra' => 'json',
    ];

    protected $dates = ['deleted_at'];

    public static bool $filamentDetection = false;


    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }

    /**
     * Get the user that owns the stock.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with the stock.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
