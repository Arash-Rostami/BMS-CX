<?php

namespace App\Models;

use App\Models\Traits\PaymentRequestComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rennokki\QueryCache\Traits\QueryCacheable;

class PaymentRequest extends Model
{
    use HasFactory;
    use PaymentRequestComputations;
    use SoftDeletes;
    use QueryCacheable;

    public static array $typesOfPayment = [
        'advance' => 'Advance (First Installment)',
        'partial' => 'Partial (Next Installment)',
        'balance' => 'Balance (Final Installment)',
        'full' => 'Full (One-Time Only)',
        'other' => 'Other'
    ];
    public static array $typesOfPaymentInFarsi = [
        'advance' => '💼 Advance (پیش پرداخت)',
        'partial' => '📉 Partial (اقساط)',
        'balance' => '⚖️ Balance (تسویه)',
        'full' => '✅ Full (یکجا/کامل)',
        'other' => '🔄 Other (سایر)'
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
    protected static $flushCacheOnUpdate = true;
    public $cacheFor = 43200;
    public $cacheDriver = 'file';
    public $cacheTags = ['payment_requests_table'];
    protected $casts = [
        'deadline' => 'datetime',
        'extra' => 'json',
    ];
    protected $fillable = [
        'reference_number',
        'reason_for_payment',
        'type_of_payment',
        'cost_center',
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
        'proforma_invoice_number',
        'part',
        'user_id',
        'order_id',
        'supplier_id',
        'contractor_id',
        'payee_id',
        'department_id',
        'case_number',
        'sequential_id',
    ];

    public function activeApprovedProformaInvoices()
    {
        return $this->belongsToMany(
            ProformaInvoice::class,
            'payment_request_proforma_invoice',
            'payment_request_id',
            'proforma_invoice_id'
        )
            ->where('status', 'approved')
            ->whereNull('deleted_at');
    }

    public function associatedProformaInvoices()
    {
        return $this->belongsToMany(
            ProformaInvoice::class,
            'payment_request_proforma_invoice',
            'payment_request_id',
            'proforma_invoice_id'
        );
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'payment_request_id');
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class, 'payee_id');
    }

    public function chats()
    {
        return $this->morphMany(Chat::class, 'record');
    }

    public function contractor()
    {
        return $this->belongsTo(Contractor::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(Department::class, 'cost_center');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function notificationSubscriptions()
    {
        return $this->morphMany(NotificationSubscription::class, 'notifiable');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function payments()
    {
        return $this->belongsToMany(
            Payment::class,
            'payment_payment_request'
        );
    }

    public function proformaInvoice()
    {
        return $this->hasOne(ProformaInvoice::class, 'proforma_number', 'proforma_invoice_number');
    }

    public function proformaInvoices()
    {
        return $this->hasMany(ProformaInvoice::class, 'proforma_number', 'proforma_invoice_number');
    }

    public function reason()
    {
        return $this->belongsTo(Allocation::class, 'reason_for_payment');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierSummaries()
    {
        return $this->hasMany(SupplierSummary::class, 'supplier_id', 'supplier_id')
            ->where('currency', $this->currency);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::creating(function ($paymentRequest) {
            $paymentRequest->user_id = auth()->id();
            $paymentRequest->sequential_id = self::getNextReferenceNumberForCurrency($paymentRequest->currency);
        });

        static::updating(function ($paymentRequest) {
            if ($paymentRequest->isDirty('currency')) {
                $paymentRequest->sequential_id = self::getNextReferenceNumberForCurrency($paymentRequest->currency, $paymentRequest->id);
            }
        });

        static::saved(function ($paymentRequest) {
            $paymentRequest->loadMissing('attachments');
            $paymentRequest->attachments->each(function ($attachment) {
                if (empty($attachment->file_path) || empty($attachment->name)) {
                    $attachment->delete();
                }
            });
        });
    }
}
