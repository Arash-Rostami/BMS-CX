<?php

namespace App\Models\Traits;

use App\Models\Balance;
use App\Models\PaymentRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


trait PaymentComputations
{

    public static function getCurrencyOptions(): array
    {
        return self::distinct('currency')
            ->pluck('currency', 'currency')
            ->toArray();
    }

    public static function sumAmountsForCurrencies(array $currencies)
    {
        return self::whereIn('currency', $currencies)
            ->filterByUserPaymentRequests(auth()->user())
            ->get(['currency', 'amount'])
            ->groupBy('currency')
            ->map(fn($items) => $items->sum('amount'))
            ->toArray();
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

    public function getProcessStatusAttribute()
    {
        $rejected = $this->paymentRequests()
            ->whereHas('associatedProformaInvoices', fn($q) => $q->where('status', 'rejected'))
            ->exists();

        if ($this->paymentRequests()->where('status', 'processing')->exists()) {
            return 'Processing (Insufficient Payment)';
        }

        if ($rejected) {
            return 'Cancelled (Refundable Payment)';
        }

        return 'Completed (Sufficient Payment)';
    }

    public function scopeFilterByUserPaymentRequests(Builder $query, $user): Builder
    {
        $departmentId = $user->info['department'] ?? null;
        $position = $user->info['position'] ?? null;

        if ($user->role === 'partner') {
            return $query->whereHas('paymentRequests', function ($q) {
                $q->whereIn('currency', ['USD', 'EURO'])
                    ->whereNotNull('account_number')
                    ->where('extra->paymentMethod', '<>', 'cash');
            });
        }

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

    protected function processPaymentRequest(PaymentRequest $req, string $action): void
    {
        foreach (['payees' => $req->payee_id, 'suppliers' => $req->supplier_id, 'contractors' => $req->contractor_id] as $cat => $id) {
            if (!$id) continue;
            match ($action) {
                'update' => $this->updateOrCreateBalance($cat, $id, $req),
                'delete' => $this->deleteBalance($cat, $id),
                'restore' => $this->restoreBalance($cat, $id, $req),
            };
        }
    }

    protected function updateOrCreateBalance(string $category, int $categoryId, PaymentRequest $paymentRequest): void
    {
        $originalCurrency = $this->getOriginal('currency');
        $originalAmount = $this->getOriginal('amount');

        $balance = $this->findBalance($originalCurrency, $originalAmount, $category, $categoryId);

        $balance
            ? $balance->update(['payment' => $this->amount, 'currency' => $this->currency])
            : $this->forceCreateBalance($category, $categoryId, $paymentRequest);
    }

    protected function findBalance(string $currency, float $amount, string $category, int $categoryId)
    {
        return Balance::where('currency', $currency)
            ->where('payment', $amount)
            ->where('category', $category)
            ->where('category_id', $categoryId)
            ->orderByRaw(
                "CASE WHEN ABS(TIMESTAMPDIFF(SECOND, created_at, ?)) <= 1 THEN 0 ELSE 1 END",
                [$this->created_at]
            )
            ->orderByDesc('created_at')
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

    protected function deleteBalance(string $category, int $categoryId): void
    {
        $this->findBalance($this->currency, $this->amount, $category, $categoryId)?->delete();
    }

    protected function restoreBalance(string $category, int $categoryId, PaymentRequest $paymentRequest): void
    {
        $balance = $this->findBalance($this->currency, $this->amount, $category, $categoryId);

        if (!$balance) {
            $this->forceCreateBalance($category, $categoryId, $paymentRequest);
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
}
