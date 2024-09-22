<?php

namespace App\Models;

use App\Filament\Resources\Operational\PaymentResource\Pages\CreatePayment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Payment extends Model
{
    use HasFactory;
    use SoftDeletes;

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
        static::creating(function ($payment) {
            $payment->user_id = auth()->id();
        });

        static::saving(function ($payment) {
            $payment->attachments->each(function ($attachment) {
                if (empty($attachment->file_path) || empty($attachment->name)) {
                    $attachment->delete();
                }
            });
        });

    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function names()
    {
        return $this->hasMany(Name::class);
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


    // Computational Method
    public static function sumAmountsForCurrencies(array $currencies)
    {
        $cacheKey = 'sum_amounts_for_currencies_' . implode('_', $currencies);

        return Cache::remember($cacheKey, 60, function () use ($currencies) {
            return self::whereIn('currency', $currencies)
                ->get(['currency', 'amount'])
                ->groupBy('currency')
                ->map(function ($items, $currency) {
                    return $items->sum('amount');
                })
                ->toArray();
        });
    }
}
