<?php

namespace App\Models\Traits;

use App\Models\Allocation;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait PaymentRequestComputations
{
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

    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->requested_amount;
    }

    public static function getStatusCounts()
    {
        $user = auth()->user();

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
