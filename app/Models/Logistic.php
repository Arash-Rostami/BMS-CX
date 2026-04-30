<?php

namespace App\Models;

use App\Models\Traits\LogisticComputations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Logistic extends Model
{
    use SoftDeletes;
    use LogisticComputations;

    public static bool $filamentDetection = false;

    protected $casts = [
        'loading_deadline' => 'date',
        'extra' => 'json',
    ];

    protected $fillable = [
        'loading_deadline',
        'change_of_destination',
        'number_of_containers',
        'full_container_load_type',
        'ocean_freight',
        'terminal_handling_charges',
        'FCL',
        'booking_number',
        'free_time_POD',
        'gross_weight',
        'net_weight',
        'extra',
        'user_id',
        'shipping_line_id',
        'shipping_line_sec_id',
        'port_of_delivery_id',
        'delivery_term_id',
        'packaging_id',
    ];

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'logistic_id');
    }


    public function deliveryTerm()
    {
        return $this->belongsTo(DeliveryTerm::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class, 'logistic_id');
    }

    public function packaging()
    {
        return $this->belongsTo(Packaging::class);
    }

    public function portOfDelivery()
    {
        return $this->belongsTo(PortOfDelivery::class);
    }

    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class, 'shipping_line_id');
    }

    public function shippingLineSec()
    {
        return $this->belongsTo(ShippingLine::class, 'shipping_line_sec_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::creating(function ($logistic) {
            $logistic->user_id = auth()->id();
        });
    }
}
