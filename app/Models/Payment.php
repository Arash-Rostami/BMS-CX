<?php

namespace App\Models;

use App\Filament\Resources\Operational\PaymentResource\Pages\CreatePayment;
use App\Models\Traits\PaymentComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Payment extends Model
{
    use HasFactory, SoftDeletes, PaymentComputations;

    protected $fillable = [
        'reference_number',
        'payer',
        'amount',
        'currency',
        'transaction_id',
        'date',
        'notes',
        'extra',
        'user_id',
        'payment_request',
        'order_id',
    ];

    protected $casts = [
        'extra' => 'json',
        'date' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(fn($payment) => $payment->user_id = auth()->id());
        static::saving(fn($payment) => $payment->cleanAttachments());
        static::updated(fn($payment) => $payment->handleUpdated());
        static::deleted(fn($payment) => $payment->handleDeleted());
        static::restored(fn($payment) => $payment->handleRestored());
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function names()
    {
        return $this->hasMany(Name::class);
    }

    public function notificationSubscriptions()
    {
        return $this->morphMany(NotificationSubscription::class, 'notifiable');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }


    public function paymentRequests()
    {
        return $this->belongsToMany(
            PaymentRequest::class,
            'payment_payment_request'
        );
    }


    public function approvedPaymentRequests()
    {
        return $this->belongsToMany(
            PaymentRequest::class,
            'payment_payment_request'
        )->whereIn('status', ['processing', 'approved', 'allowed']);
    }


    public function reason()
    {
        return $this->hasOneThrough(Allocation::class, PaymentRequest::class, 'id', 'id', 'payment_request_id', 'reason_for_payment');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
