<?php

namespace App\Models;

use App\Filament\Resources\Operational\PaymentResource\Pages\CreatePayment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
        static::creating(fn($payment) => $payment->user_id = auth()->id());
        static::saving(fn($payment) => $payment->cleanAttachments());
        static::updated(fn($payment) => $payment->handleUpdated());
        static::deleted(fn($payment) => $payment->handleDeleted());
        static::restored(fn($payment) => $payment->handleRestored());
    }

    public function getProcessStatusAttribute()
    {
        $rejectedProformaInvoicesCount = $this->paymentRequests()
            ->whereHas('associatedProformaInvoices', fn($q) => $q->where('status', 'rejected'))
            ->count();

        $processingPaymentRequestsCount = $this->paymentRequests()
            ->where('status', 'processing')
            ->count();

        if ($processingPaymentRequestsCount > 0) {
            return 'Processing (Insufficient Payment)';
        }

        if ($rejectedProformaInvoicesCount > 0) {
            return 'Cancelled (Refundable Payment)';
        }

        return 'Completed (Sufficient Payment)';
    }

    protected function cleanAttachments(): void
    {
        $this->attachments->each(function ($attachment) {
            if (empty($attachment->file_path) || empty($attachment->name)) {
                $attachment->delete();
            }
        });
    }

    protected function handleUpdated(): void
    {
        if ($this->wasChanged(['currency', 'amount'])) {
            $this->paymentRequests->each(fn($request) => $this->processPaymentRequest($request, 'update'));
        }
    }

    protected function handleDeleted(): void
    {
        $this->paymentRequests->each(fn($request) => $this->processPaymentRequest($request, 'delete'));
    }

    protected function handleRestored(): void
    {
        $this->paymentRequests->each(fn($request) => $this->processPaymentRequest($request, 'restore'));
    }

    protected function getCategories(PaymentRequest $paymentRequest): array
    {
        return [
            'payees' => $paymentRequest->payee_id,
            'suppliers' => $paymentRequest->supplier_id,
            'contractors' => $paymentRequest->contractor_id,
        ];
    }

    protected function processPaymentRequest(PaymentRequest $paymentRequest, string $action): void
    {
        $categories = $this->getCategories($paymentRequest);

        foreach ($categories as $category => $categoryId) {
            if (!$categoryId) {
                continue;
            }

            switch ($action) {
                case 'update':
                    $this->updateOrCreateBalance($category, $categoryId, $paymentRequest);
                    break;

                case 'delete':
                    $this->deleteBalance($category, $categoryId);
                    break;

                case 'restore':
                    $this->restoreBalance($category, $categoryId, $paymentRequest);
                    break;
            }
        }
    }

    protected function findBalance(string $currency, float $amount, string $category, int $categoryId)
    {
        return Balance::where('currency', $currency)
            ->where('payment', $amount)
            ->where('category', $category)
            ->where('category_id', $categoryId)
            ->orderByRaw("CASE
                        WHEN ABS(TIMESTAMPDIFF(SECOND, created_at, ?)) <= 1 THEN 0
                        ELSE 1
                      END", [$this->created_at])
            ->orderBy('created_at', 'desc')
            ->first();
    }

    protected function forceCreateBalance(string $category, int $categoryId, PaymentRequest $paymentRequest): void
    {
        Balance::forceCreate([
            'payment' => $this->amount,
            'currency' => $this->currency,
            'category' => $category,
            'category_id' => $categoryId,
            'department_id' => $paymentRequest->department_id,
            'extra' => ['currency' => $this->currency],
            'created_at' => Carbon::parse($this->created_at)->addMilliseconds(1),
            'updated_at' => Carbon::parse($this->created_at)->addMilliseconds(1),
        ]);
    }

    protected function updateOrCreateBalance(string $category, int $categoryId, PaymentRequest $paymentRequest): void
    {
        $originalCurrency = $this->getOriginal('currency');
        $originalAmount = $this->getOriginal('amount');

        $balance = $this->findBalance($originalCurrency, $originalAmount, $category, $categoryId);

        if ($balance) {
            $balance->update([
                'payment' => $this->amount,
                'currency' => $this->currency,
            ]);
        } else {
            $this->forceCreateBalance($category, $categoryId, $paymentRequest);
        }
    }

    protected function deleteBalance(string $category, int $categoryId): void
    {
        $balance = $this->findBalance($this->currency, $this->amount, $category, $categoryId);

        $balance?->delete();
    }

    protected function restoreBalance(string $category, int $categoryId, PaymentRequest $paymentRequest): void
    {
        $balance = $this->findBalance($this->currency, $this->amount, $category, $categoryId);

        if (!$balance) {
            $this->forceCreateBalance($category, $categoryId, $paymentRequest);
        }
    }

    public static function getCurrencyOptions(): array
    {
        return self::distinct('currency')
            ->pluck('currency', 'currency')
            ->toArray();
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


    // Computational Method
    public static function sumAmountsForCurrencies(array $currencies)
    {
        $user = auth()->user();
        $cacheKey = 'sum_amounts_for_currencies_' . implode('_', $currencies) . '_' . $user->id;

//        return Cache::remember($cacheKey, 60, function () use ($currencies, $user) {

        $query = self::query()
            ->whereIn('currency', $currencies)
            ->filterByUserPaymentRequests($user);

        return $query->get(['currency', 'amount'])
            ->groupBy('currency')
            ->map(function ($items, $currency) {
                return $items->sum('amount');
            })
            ->toArray();
//        });
    }

    public function scopeFilterByUserPaymentRequests(Builder $query, $user): Builder
    {
        $departmentId = $user->info['department'] ?? null;
        $position = $user->info['position'] ?? null;

        if ($user->role == 'accountant' && $position == 'jnr') {
            return $query->whereHas('paymentRequests', function ($subQuery) use ($departmentId) {
                $subQuery->where(function ($innerQuery) use ($departmentId) {
                    $innerQuery->where('department_id', 6)
                        ->orWhere('cost_center', 6)
                        ->orWhere('department_id', $departmentId)
                        ->orWhere('cost_center', $departmentId);
                });
            });
        }

        if (in_array($user->role, ['admin', 'manager', 'accountant'])) {
            return $query;
        }

        if ($position == 'jnr') {
            return $query->whereHas('paymentRequests', fn($q) => $q->where('user_id', $user->id));
        }

        return $query->whereHas('paymentRequests', function ($subQuery) use ($departmentId) {
            $subQuery->where(function ($innerQuery) use ($departmentId) {
                $innerQuery->whereIn('department_id', [$departmentId, 0])
                    ->orWhereIn('cost_center', [$departmentId, 0]);
            });
        });
    }


    public static function getTabCounts(array $specificDepartmentIds): array
    {
        $userId = auth()->id();
        return Cache::remember("payment_tab_counts_{$userId}", 60, function () use ($specificDepartmentIds) {
            $selectStatements = [
                DB::raw('COUNT(DISTINCT payments.id) as total'),
                DB::raw('COUNT(DISTINCT CASE WHEN currency = "Rial" THEN payments.id END) as rial_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN currency = "USD" THEN payments.id END) as usd_count'),
            ];

            foreach ($specificDepartmentIds as $id) {
                $selectStatements[] = DB::raw("COUNT(DISTINCT CASE WHEN EXISTS (SELECT 1 FROM payment_payment_request INNER JOIN payment_requests ON payment_requests.id = payment_payment_request.payment_request_id WHERE payment_payment_request.payment_id = payments.id AND payment_requests.department_id = $id) THEN payments.id END) as department_{$id}_count");
            }

            $selectStatements[] = DB::raw("COUNT(DISTINCT CASE WHEN NOT EXISTS (SELECT 1 FROM payment_payment_request INNER JOIN payment_requests ON payment_requests.id = payment_payment_request.payment_request_id WHERE payment_payment_request.payment_id = payments.id AND payment_requests.department_id IN (" . implode(',', $specificDepartmentIds) . ")) THEN payments.id END) as other_count");

            return self::query()
                ->filterByUserPaymentRequests(auth()->user())
                ->select($selectStatements)
                ->first()
                ->toArray();
        });
    }
}
