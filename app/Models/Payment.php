<?php

namespace App\Models;

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


    /**
     * Get the sum of amounts for specified currencies.
     *
     * @return array
     */
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
    public function order()
    {
        return $this->hasOneThrough(Order::class, PaymentRequest::class, 'id', 'invoice_number', 'payment_request_id', 'order_invoice_number');
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
        return $this->belongsTo(PaymentRequest::class, 'payment_request_id');
    }

    public function reason()
    {
        return $this->hasOneThrough(Allocation::class, PaymentRequest::class, 'id', 'id', 'payment_request_id', 'reason_for_payment');
    }


    /**
     * Get the user that owns the payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
