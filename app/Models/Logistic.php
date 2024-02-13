<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Logistic extends Model
{
    use HasFactory;
    use SoftDeletes;

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
        'order_id',
        'shipping_line_id',
        'port_of_delivery_id',
        'delivery_term_id',
        'packaging_id',
    ];

    protected $casts = [
        'loading_deadline' => 'date',
        'extra' => 'json',
    ];

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'logistic_id');
    }

    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }

    /**
     * Get the user that owns the logistic.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with the logistic.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the packaging associated with the party.
     */
    public function packaging()
    {
        return $this->belongsTo(Packaging::class);
    }


    /**
     * Get the shipping line associated with the logistic.
     */
    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class);
    }

    /**
     * Get the port of delivery associated with the logistic.
     */
    public function portOfDelivery()
    {
        return $this->belongsTo(PortOfDelivery::class);
    }

    /**
     * Get the delivery term associated with the logistic.
     */
    public function deliveryTerm()
    {
        return $this->belongsTo(DeliveryTerm::class);
    }
}
