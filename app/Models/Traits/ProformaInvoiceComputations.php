<?php

namespace App\Models\Traits;

use App\Models\ProformaInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait ProformaInvoiceComputations
{
    public static function fetchProformasByID($id)
    {
        return self::find($id)
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->get(['quantity', 'price', 'percentage']);
    }

    public static function fetchApprovedProformas($proformaNumbers)
    {
        return self::whereIn('proforma_number', $proformaNumbers)
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->with(['orders.orderDetail'])
            ->get();
    }


    public static function fetchActiveApprovedProformas($paymentRequests)
    {
        $relatedProformaInvoices = collect();

        foreach ($paymentRequests as $paymentRequest) {
            $proformaInvoices = $paymentRequest->activeApprovedProformaInvoices;
            $relatedProformaInvoices = $relatedProformaInvoices->merge($proformaInvoices);
        }

        return $relatedProformaInvoices->unique('id');
    }

    public static function hasMatchingProformaNumber(string $search)
    {
        return self::where('proforma_number', 'like', "%{$search}%")
            ->exists();
    }

    public function getDaysPassedAttribute()
    {
        return Carbon::parse($this->proforma_date)->diffInDays(now());
    }

    public static function getDistinctProformaNumbers()
    {
        return static::distinct('proforma_number')
            ->pluck('proforma_number')
            ->mapWithKeys(function ($item) {
                return [$item => $item];
            });
    }


    public static function getFormattedProformaNumbers()
    {
        return static::all()
            ->mapWithKeys(function ($item) {
                $product = optional($item->product)->name ?? 'Undefined Product';
                $grade = optional($item->grade)->name ?? 'Undefined Grade';
                return [$item->id => "$item->proforma_number ($product - $grade)  💢 Ref: $item->reference_number"];
            });
    }

    public static function getProformaInvoicesCached()
    {
        $key = 'proforma_invoices_list';

        if (Cache::has($key)) {
            $proformaInvoices = Cache::get($key);
        } else {
            $proformaInvoices = ProformaInvoice::pluck('reference_number', 'id')->toArray();
            Cache::put($key, $proformaInvoices, 5);
        }

        return $proformaInvoices;
    }


    public static function getProformaInvoicesWithSearch(string $search)
    {
        return self::where('proforma_number', 'like', "%{$search}%")
            ->with(['product:id,name'])
            ->select('id', 'proforma_number', 'reference_number', 'product_id', 'grade_id')
            ->limit(20)
            ->get();
    }

    public static function getStatusCounts()
    {
        $cacheKey = 'proforma_invoice_status_counts';

        return Cache::remember($cacheKey, 60, function () {
            return static::select('status')
                ->selectRaw('count(*) as count')
                ->groupBy('status')
                ->get()
                ->keyBy('status')
                ->map(fn($item) => $item->count);
        });
    }

    public static function getApproved()
    {
        $cacheKey = 'approved_proforma_invoices';

        Cache::forget('approved_proforma_invoices');
        return Cache::remember($cacheKey, 60, function () {
            return static::where('status', 'approved')
                ->with('product', 'category', 'buyer')
                ->orderBy('id', 'desc')
                ->get()
                ->pluck('formatted_value', 'id');
        });
    }

    public function getFormattedValueAttribute()
    {
        $proformaInvoice = $this->proforma_number ?? '';
        $referenceNumber = $this->reference_number ?? sprintf('PI-%s%04d', $this->created_at->format('y'), $this->id);

        if ($proformaInvoice) {
            return sprintf('%s 💢 Ref: %s', $proformaInvoice, $referenceNumber);
        }

        return sprintf(
            '%s - %s (%s) 💢 Ref: %s',
            $this->buyer->name,
            $this->product->name,
            $this->category->name,
            $referenceNumber
        );
    }

    public function setExtraAttribute($value)
    {
        if (is_array($value) && isset($value['port'])) {
            $value['port'] = array_map('strtoupper', $value['port']);
        }
        $this->attributes['extra'] = json_encode($value);
    }

    public function showSearchResult()
    {
        return sprintf(
            "%s (%s - %s) 💢 Ref: %s",
            $this->proforma_number ?? 'N/A',
            optional($this->product)->name ?? 'N/A',
            optional($this->grade)->name ?? 'N/A',
            $this->reference_number ?? 'N/A'
        );
    }

    public static function getTabCounts(): array
    {
        $userId = auth()->id();
        return Cache::remember("proforma_invoice_tab_counts_{$userId}", 60, function () use ($userId) {
            return self::select(
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(CASE WHEN status = "approved" THEN 1 END) as approved_count'),
                DB::raw('COUNT(CASE WHEN status = "rejected" THEN 1 END) as rejected_count'),
                DB::raw('COUNT(CASE WHEN status = "fulfilled" THEN 1 END) as fulfilled_count'),
                DB::raw('COUNT(CASE WHEN category_id = "1" THEN 1 END) as mineral_count'),
                DB::raw('COUNT(CASE WHEN category_id = "2" THEN 1 END) as polymers_count'),
                DB::raw('COUNT(CASE WHEN category_id = "3" THEN 1 END) as chemicals_count'),
                DB::raw('COUNT(CASE WHEN category_id = "4" THEN 1 END) as petro_count'),
                DB::raw('COUNT(CASE WHEN buyer_id = "5" THEN 1 END) as persore_count'),
                DB::raw('COUNT(CASE WHEN buyer_id = "2" THEN 1 END) as paidon_count'),
                DB::raw('COUNT(CASE WHEN buyer_id = "3" THEN 1 END) as zhuo_count'),
                DB::raw('COUNT(CASE WHEN buyer_id = "4" THEN 1 END) as solsun_count'),
            )
                ->first()
                ->toArray();
        });
    }

    public function hasCompletedBalancePayment()
    {
        $proformaInvoiceId = $this->id;
        $cacheKey = "hasCompletedBalancePayment_" . $proformaInvoiceId;

        return Cache::remember($cacheKey, 300, function () use ($proformaInvoiceId) {
            $sql = "
            SELECT 1
            FROM proforma_invoices pi
            INNER JOIN orders o ON pi.id = o.proforma_invoice_id
            WHERE EXISTS (
                SELECT 1
                FROM payment_requests pr
                JOIN payment_payment_request ppr ON pr.id = ppr.payment_request_id
                JOIN payments p ON ppr.payment_id = p.id
                WHERE pr.status = 'completed'
                AND pr.type_of_payment = 'balance'
                AND pr.deleted_at IS NULL
                AND p.deleted_at IS NULL
                AND p.date < CURDATE() - INTERVAL 3 DAY
                AND o.id = pr.order_id
                GROUP BY pr.id, pr.requested_amount
                HAVING SUM(p.amount) >= pr.requested_amount
            )
            AND NOT EXISTS (
                SELECT 1
                FROM attachments a
                WHERE a.order_id = o.id
                AND LOWER(a.name) LIKE '%telex-release%'
            )
            AND pi.id = ?
            LIMIT 1
        ";

            $result = DB::select($sql, [$proformaInvoiceId]);

            return !empty($result);
        });
    }

    public static function getLatestProformaDate(): ?string
    {
        return Cache::remember('latest_proforma_date', now()->addMinutes(15), function () {
            $query = "SELECT proforma_date FROM proforma_invoices WHERE proforma_date IS NOT NULL ORDER BY proforma_date DESC LIMIT 1";

            $result = DB::selectOne($query);

            return $result && $result->proforma_date
                ? Carbon::parse($result->proforma_date)->format('j F Y')
                : 'N/A';
        });
    }

    public static function getTotalQuantityByYearAndCategoryAndMonth($year, $category_id = null, $month = null): int
    {
        if (!$year || $year === 'all') {
            return 0;
        }

        $cacheKey = self::generateCacheKey('pi_total_quantity_', $month, $category_id, $year);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($year, $category_id, $month) {
            $query = "SELECT SUM(quantity) AS total_quantity FROM proforma_invoices WHERE deleted_at IS NULL AND YEAR(proforma_date) = ?";
            $bindings = [$year];

            if ($category_id) {
                $query .= is_array($category_id) ? " AND category_id IN (" . implode(',', array_fill(0, count($category_id), '?')) . ")" : " AND category_id = ?";
                $bindings = array_merge($bindings, (array)$category_id);
            }

            if ($month && $month !== 'all') {
                if (is_array($month)) {
                    $query .= " AND MONTH(proforma_date) IN (" . implode(',', array_fill(0, count($month), '?')) . ")";
                    $bindings = array_merge($bindings, $month);
                } else {
                    $query .= " AND MONTH(proforma_date) = ?";
                    $bindings[] = $month;
                }
            }

            $result = DB::selectOne($query, $bindings);
            return $result->total_quantity ?? 0;
        });
    }

    public static function getTotalQuantityWithBLDateByFilters($year, $category_id = null, $month = null): int
    {
        if (!$year || $year === 'all') {
            return 0;
        }

        // Generate unique cache key
        $cacheKey = self::generateCacheKey('pi_total_quantity_with_bl_', $month, $category_id, $year);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($year, $category_id, $month) {
            $query = "
            SELECT SUM(pi.quantity) AS total_quantity
            FROM proforma_invoices pi
            INNER JOIN orders o ON pi.id = o.proforma_invoice_id
            INNER JOIN docs d ON o.doc_id = d.id
            WHERE pi.deleted_at IS NULL
              AND d.BL_date IS NOT NULL
        ";

            $bindings = [];
            if ($year && $year !== 'all') {
                $query .= " AND YEAR(d.BL_date) = ?";
                $bindings[] = $year;
            }

            if ($month && $month !== 'all') {
                if (is_array($month)) {
                    $placeholders = implode(',', array_fill(0, count($month), '?'));
                    $query .= " AND MONTH(d.BL_date) IN ($placeholders)";
                    $bindings = array_merge($bindings, $month);
                } else {
                    $query .= " AND MONTH(d.BL_date) = ?";
                    $bindings[] = $month;
                }
            }

            if ($category_id) {
                if (is_array($category_id)) {
                    $placeholders = implode(',', array_fill(0, count($category_id), '?'));
                    $query .= " AND pi.category_id IN ($placeholders)";
                    $bindings = array_merge($bindings, $category_id);
                } else {
                    $query .= " AND pi.category_id = ?";
                    $bindings[] = $category_id;
                }
            }

            $result = DB::selectOne($query, $bindings);
            return $result->total_quantity ?? 0;
        });
    }

    public static function generateCacheKey($prefix, $month, $category_id, $year): string
    {
        $monthCacheKey = is_array($month) ? implode('_', $month) : ($month ?? 'all');
        $categoryCacheKey = is_array($category_id) ? implode('_', $category_id) : ($category_id ?? 'all');
        return $prefix . $year . '_' . $categoryCacheKey . '_' . $monthCacheKey;
    }
}
