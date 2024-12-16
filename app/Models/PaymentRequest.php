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
    ];

    public static array $typesOfPayment = [
        'advance' => 'Advance (First Installment)',
        'partial' => 'Partial (Next Installment)',
        'balance' => 'Balance (Final Installment)',
        'full' => 'Full (One-Time Only)',
        'other' => 'Other'
//        'check' => 'Check',
//        'credit' => 'Credit',
//        'in_kind' => 'In Kind',
//        'lc' => 'LC (Letter of Credit)',
//        'cod' => 'COD (Cash on Delivery)',
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
        return $this->hasMany(Attachment::class);
    }

    public function contractor()
    {
        return $this->belongsTo(Contractor::class);
    }

    public function chats()
    {
        return $this->morphMany(Chat::class, 'record');
    }

    public function costCenter()
    {
        return $this->belongsTo(Department::class, 'cost_center');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    protected static function booted()
    {
        static::creating(function ($paymentRequest) {
            $paymentRequest->user_id = auth()->id();
        });

        static::saving(function ($paymentRequest) {
            $paymentRequest->attachments->each(function ($attachment) {
                if (empty($attachment->file_path) || empty($attachment->name)) {
                    $attachment->delete();
                }
            });
        });
    }

    public function notificationSubscriptions()
    {
        return $this->morphMany(NotificationSubscription::class, 'notifiable');
    }


    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
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

    public function proformaInvoices()
    {
        return $this->hasMany(ProformaInvoice::class, 'proforma_number', 'proforma_invoice_number');
    }

    public function proformaInvoice()
    {
        return $this->hasOne(ProformaInvoice::class, 'proforma_number', 'proforma_invoice_number');
    }


    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class, 'payee_id');
    }

    public function payments()
    {
        return $this->belongsToMany(
            Payment::class,
            'payment_payment_request'
        );
    }


    public function reason()
    {
        return $this->belongsTo(Allocation::class, 'reason_for_payment');
    }


    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    // Computational Methods
    public static function getStatusCounts()
    {
        $user = auth()->user();
        $cacheKey = 'payment_request_status_counts';


//        return Cache::remember($cacheKey, 60, function ($user) use ($department) {
        $query = static::query()->authorizedForUser($user);

        $countsByStatus = $query
            ->select('status')
            ->selectRaw('count(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->map(fn($item) => $item->count);

        $countsByStatus->put('total', $query->count());

        return $countsByStatus;
//        });
    }

    public static function getAllPaymentRequests($operation)
    {
        $query = self::orderBy('deadline', 'asc');

        if ($operation == 'create') {
            $query->whereIn('status', ['processing', 'approved', 'allowed']);
        }
        return $query->get()->mapWithKeys(fn($paymentRequest) => [$paymentRequest->id => $paymentRequest->getCustomizedDisplayName()])->toArray();
    }


    public function getCustomizedDisplayName(): string
    {
        $proformaInvoiceNo = $this->proforma_invoice_number ?? self::showAmongAllReasons($this->reason_for_payment);
        $formattedDate = optional($this->deadline)->format('Y-m-d') ?? 'No Deadline';

        return sprintf(
            " %s 💢 Ref: %s  ┆ 📅  %s",
            $proformaInvoiceNo,
            $this->reference_number,
            $formattedDate
        );
    }

    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->requested_amount;
    }

    public static function fetchPaymentDetails($proformaInvoiceNumber)
    {
        $escapedProformaInvoiceNumber = addslashes($proformaInvoiceNumber);

        return self::whereRaw("proforma_invoice_number REGEXP ?", ["(^|,\\s){$escapedProformaInvoiceNumber}(\\s,|$)"])
//            ->orWhere('proforma_invoice_number', 'LIKE', "%{$proformaInvoiceNumber}%")
            ->whereNull('order_id')
            ->where('status', '<>', 'pending')
            ->whereNull('deleted_at')
            ->get(['requested_amount', 'total_amount', 'proforma_invoice_number']);
    }

    public static function showAmongAllReasons($reason)
    {
        return Allocation::find($reason)->reason;
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

    public function scopeAuthorizedForUser($query, User $user)
    {
        $department = $user->info['department'] ?? 0;
        $position = $user->info['position'] ?? null;

        if (in_array($user->role, ['admin', 'manager', 'accountant'])) {
            return $query;
        }

        if ($position == 'jnr') {
            return $query->where('user_id', $user->id);
        }

        return $query->where(function ($subQuery) use ($department) {
            $subQuery->whereIn('department_id', [$department, 0])
                ->orWhereIn('cost_center', [$department, 0]);
        });
    }
}
