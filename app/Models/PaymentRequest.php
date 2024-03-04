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
        'recipient_name',
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

    public static array $typesOfPayment = [
        'Order' => 'Order',
        'ContainerDemurrage' => 'Container Demurrage',
        'CustomsAndPortFees' => 'Customs & Port Fees',
        'ContainerAcceptance' => 'Container Acceptance',
        'ShrinkWrap' => 'Shrink Wrap',
        'ContainerLashing' => 'Container Lashing',
        'SgsReport' => 'SGS Report',
        'JumboBoxPallet' => 'Jumbo/Box/Pallet',
        'DrumPackaging' => 'Drum Packaging',
        'Trucking' => 'Trucking',
        'Other' => 'Other',
    ];

    public static array $status = [
        'pending' => '🕒 Pending',
        'allowed' => '✔️ Allow',
        'approved' => '✔️✔️ Approve',
        'rejected' => '🚫 Deny',
        'processing' => '⏳ Processing',
        'completed' => '☑️ Completed',
        'cancelled' => '❌ Called off',
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

    public static function showApproved($orderId)
    {
        return self::whereNotIn('status', ['cancelled', 'rejected', 'completed'])
            ->where('order_id', $orderId)
            ->pluck('type', 'id')
            ->map(function ($type) {
                return self::$typesOfPayment[$type] ?? $type;
            });
    }

    /**
     * Get the order associated with the payment request.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }


    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the user that owns the payment request.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
