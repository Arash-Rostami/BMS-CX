<?php

namespace App\Models;

use App\Models\Traits\OrderDetailComputations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rennokki\QueryCache\Traits\QueryCacheable;

class OrderDetail extends Model
{
    use SoftDeletes;
    use OrderDetailComputations;


    public static bool $filamentDetection = false;

    protected $table = 'order_details';

    protected $fillable = [
        'buying_quantity',
        'provisional_quantity',
        'final_quantity',
        'buying_price',
        'provisional_price',
        'final_price',
        'currency',
        'remaining',
        'payment',
        'initial_payment',
        'provisional_payment',
        'total',
        'initial_total',
        'provisional_total',
        'final_total',
        'payable_quantity',
        'extra',
        'user_id',
    ];
    protected $casts = [
        'extra' => 'json',
    ];
    protected $dates = ['deleted_at'];

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::creating(function ($orderDetail) {
            $orderDetail->user_id = auth()->id();
        });
    }
}
