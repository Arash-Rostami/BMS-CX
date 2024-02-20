<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'payer',
        'amount',
        'currency',
        'account_number',
        'bank_name',
        'extra',
        'user_id',
        'payment_request_id',
        'order_id',
        'attachment_id',
    ];


    protected $casts = [
        'extra' => 'json',
    ];

    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }


    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

//
//    public function orders()
//    {
//        return $this->hasMany(Order::class, 'id', 'order_id');
//    }
//

    public function orderRequest()
    {

        return $this->belongsTo(OrderRequest::class, 'id', 'id' ?? null);

    }


    public function paymentRequests()
    {
        return $this->hasMany(PaymentRequest::class, 'id' ,'payment_request_id');
    }


    /**
     * Get the user that owns the payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
