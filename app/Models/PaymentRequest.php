<?php

namespace App\Models;

use Egulias\EmailValidator\Result\Reason\Reason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class PaymentRequest extends Model
{
    use HasFactory;
    use SoftDeletes;


    protected $casts = [
        'deadline' => 'datetime',
        'extra' => 'json',
    ];


    protected $fillable = [
        'reason_for_payment',
        'type_of_payment',
        'departments',
        'purpose',
        'status',
        'currency',
        'requested_amount',
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
        'IFSC',
        'MICR',
        'extra',
        'order_invoice_number',
        'part',
        'user_id',
        'supplier_id',
        'contractor_id',
        'payee_id',
        'department_id',
    ];

    public static array $typesOfPayment = [
        'advance' => 'Advance (First Installment)',
        'partial' => 'Partial (Next Installment)',
        'balance' => 'Balance (Outstanding)',
        'full' => 'Full (One-Time Only)',
        'check' => 'Check',
        'credit' => 'Credit',
        'in_kind' => 'In Kind',
        'lc' => 'LC (Letter of Credit)',
        'cod' => 'COD (Cash on Delivery)',
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


//
//    /**
//     * Get the attachment associated with the payment request (nullable).
//     */
//    public function attachment()
//    {
//        return $this->belongsTo(Attachment::class, 'attachment_id');
//    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }


    /**
     * Get the user that owns the payment request.
     */
    public
    function contractor()
    {
        return $this->belongsTo(Contractor::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }


    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }

    public static function getStatusCounts()
    {
        $cacheKey = 'payment_request_status_counts';

        return Cache::remember($cacheKey, 60, function () {
            return static::select('status')
                ->selectRaw('count(*) as count')
                ->groupBy('status')
                ->get()
                ->keyBy('status')
                ->map(fn($item) => $item->count);
        });
    }

    public static function showApproved($orderId)
    {
        $cacheKey = 'approved_payment_requests_' . $orderId;

        return Cache::remember($cacheKey, 60, function () use ($orderId) {
            return self::whereNotIn('status', ['cancelled', 'rejected', 'completed'])
                ->where('order_id', $orderId)
                ->pluck('type_of_payment', 'id')
                ->map(function ($type) {
                    return self::$typesOfPayment[$type] ?? $type;
                });
        });
    }


    public function getCustomizedDisplayName(): string
    {
        $invoiceNumber = $this->order_invoice_number;
        $partInfo = !is_null($this->part) ? ' (part ' . $this->part . ')' : '';
        $formattedDate = $this->deadline->format('Y-m-d');

        $displayName = $invoiceNumber ?? self::showAmongAllReasons($this->reason_for_payment);
        $displayName .= $partInfo . ' ┆ 📅 ' . $formattedDate;

        return $displayName;
    }

    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->requested_amount;
    }


    public static function showAmongAllReasons($reason)
    {
        return Allocation::find($reason)->reason;
    }


    public function order()
    {
        return $this->belongsTo(Order::class, 'order_invoice_number', 'invoice_number');
    }

    public function orderPart()
    {
        return $this->belongsTo(Order::class, 'part');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }


    /**
     * Get the payee associated with the payment request (nullable).
     */
    public function payee()
    {
        return $this->belongsTo(Payee::class, 'payee_id');
    }

    public function reason()
    {
        return $this->belongsTo(Allocation::class, 'reason_for_payment');
    }


    /**
     * Get the user that owns the payment request.
     */
    public
    function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user that owns the payment request.
     */
    public
    function user()
    {
        return $this->belongsTo(User::class);
    }
}
