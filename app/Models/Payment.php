<?php

namespace App\Models;

use App\Models\Traits\PaymentComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rennokki\QueryCache\Traits\QueryCacheable;


class Payment extends Model
{
    use HasFactory;
    use PaymentComputations;
    use SoftDeletes;
    use QueryCacheable;

    protected static $flushCacheOnUpdate = true;
    public $cacheFor = 43200;
    public $cacheDriver = 'file';
    public $cacheTags = ['payments_table'];
    protected $fillable = [
        'reference_number',
        'payer',
        'amount',
        'currency',
        'transaction_id',
        'date',
        'notes',
        'extra',
        'exchange_rate',
        'equivalent_amount',
        'bank_charges_currency',
        'bank_charges',
        'user_id',
        'payment_request',
        'bypass_cash_ledger',
    ];

    protected $casts = [
        'extra' => 'json',
        'date' => 'datetime',
    ];

    public function approvedPaymentRequests()
    {
        return $this->belongsToMany(
            PaymentRequest::class,
            'payment_payment_request'
        )->whereIn('status', ['processing', 'approved', 'allowed']);
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

    public function reason()
    {
        return $this->hasOneThrough(Allocation::class, PaymentRequest::class, 'id', 'id', 'payment_request_id', 'reason_for_payment');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::creating(fn($payment) => $payment->user_id = auth()->id());
        static::saving(fn($payment) => $payment->cleanAttachments());
        static::updated(fn($payment) => $payment->handleUpdated());
        static::deleted(fn($payment) => $payment->handleDeleted());
        static::restored(fn($payment) => $payment->handleRestored());
    }
}
