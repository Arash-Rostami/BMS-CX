<?php

namespace App\Models;

use Egulias\EmailValidator\Result\Reason\Reason;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PaymentRequest extends Model
{
    use HasFactory;
    use SoftDeletes;


    protected $casts = [
        'deadline' => 'datetime',
        'extra' => 'json',
    ];


    public static array $typesOfPayment = [
        'advance' => 'Advance (First Installment)',
        'partial' => 'Partial (Next Installment)',
        'balance' => 'Balance (Final Installment)',
        'full' => 'Full (One-Time Only)',
        'other' => 'Other'
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


    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'payment_request_id');
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
            $paymentRequest->sequential_id = self::getNextReferenceNumberForCurrency($paymentRequest->currency);
        });

        static::updating(function ($paymentRequest) {
            if ($paymentRequest->isDirty('currency')) {
                $paymentRequest->sequential_id = self::getNextReferenceNumberForCurrency($paymentRequest->currency, $paymentRequest->id);
            }
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

    public static function searchBeneficiaries($query, $search): void
    {
        $query->whereHas('contractor', function ($contractorQuery) use ($search) {
            $contractorQuery->where('name', 'like', '%' . $search . '%');
        })
            ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                $supplierQuery->where('name', 'like', '%' . $search . '%');
            })
            ->orWhereHas('beneficiary', function ($beneficiaryQuery) use ($search) {
                $beneficiaryQuery->where('name', 'like', '%' . $search . '%');
            });
    }

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
            "Ref: %s  ┆ 📅  %s 💢  %s ",
            $this->reference_number,
            $formattedDate,
            $proformaInvoiceNo,
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

        if ($user->role == 'accountant' && $position == 'jnr') {
            return $query->where(function ($subQuery) use ($department) {
                $subQuery->where('department_id', 6)
                    ->orWhere('cost_center', 6)
                    ->orWhere('department_id', $department)
                    ->orWhere('cost_center', $department);
            });
        }

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


    public static function getMadeByOptions(): array
    {
        return Cache::remember('payment_request_made_by_options', 60, function () {
            return self::query()
                ->select('extra')
                ->distinct()
                ->get()
                ->pluck('extra')
                ->filter(fn($extra) => is_array($extra) && array_key_exists('made_by', $extra))
                ->pluck('made_by', 'made_by')
                ->toArray();
        });
    }

    public static function getTabCounts(): array
    {
        $userId = auth()->id();

        return Cache::remember("payment_request_tab_counts_{$userId}", 60, function () use ($userId) {
            return self::select(
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_count'),
                DB::raw('COUNT(CASE WHEN status = "processing" THEN 1 END) as processing_count'),
                DB::raw('COUNT(CASE WHEN status = "allowed" THEN 1 END) as allowed_count'),
                DB::raw('COUNT(CASE WHEN status = "approved" THEN 1 END) as approved_count'),
                DB::raw('COUNT(CASE WHEN status = "rejected" THEN 1 END) as rejected_count'),
                DB::raw('COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_count'),
//                DB::raw('COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_count'),
                DB::raw('COUNT(CASE WHEN currency = "Rial" THEN 1 END) as rial_count'),
                DB::raw('COUNT(CASE WHEN currency = "USD" THEN 1 END) as usd_count'),
                DB::raw('COUNT(CASE WHEN type_of_payment = "advance" THEN 1 END) as advance_count'),
                DB::raw('COUNT(CASE WHEN type_of_payment = "balance" THEN 1 END) as balance_count'),
                DB::raw('COUNT(CASE WHEN type_of_payment = "other" THEN 1 END) as other_count'),
            )
                ->authorizedForUser(auth()->user())
                ->first()
                ->toArray();
        });
    }

    public static function getLastPaymentDetails(string $recipientName, string $paymentMethod, string $currency)
    {
        return self::query()
            ->where('recipient_name', $recipientName)
            ->where('extra->paymentMethod', $paymentMethod)
            ->where('currency', $currency)
            ->latest('created_at')
            ->first();
    }

    public static function getNextReferenceNumberForCurrency(string $currency, $excludeId = null): string
    {
        $year = now()->format('Y');

        if ($currency === 'Rial') {
            $prefix = "Rial-{$year}-";
            $query = PaymentRequest::where('currency', 'Rial');
        } else {
            $prefix = "{$currency}-{$year}-";
            $query = PaymentRequest::where('currency', '!=', 'Rial');
        }

        $query->whereYear('created_at', $year);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $maxSequentialId = $query
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(sequential_id, '-', -1) AS UNSIGNED)) as max_id")
            ->value('max_id') ?: 0;

        $nextSequentialId = $maxSequentialId + 1;
        return $prefix . sprintf('%05d', $nextSequentialId);
    }
}
