<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'type',
        'purpose',
        'status',
        'individual_amount',
        'total_amount',
        'deadline',
        'description',
        'beneficiary_name',
        'beneficiary_address',
        'bank_name',
        'bank_address',
        'account_number',
        'swift_code',
        'IBAN',
        'currency',
        'IFSC',
        'MICR',
        'extra',
        'user_id',
        'order_id',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'extra' => 'json',
    ];



    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'payment_request_id');
    }

    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }

    public static function getStatusCounts()
    {
        return static::select('status')
            ->selectRaw('count(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->map(fn($item) => $item->count);
    }

    /**
     * Get the user that owns the payment request.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with the payment request.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
