<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


class ProformaInvoice extends Model
{
    use HasFactory, Notifiable;
    use SoftDeletes;

    protected $table = 'proforma_invoices';


    protected $fillable = [
        'grade_id',
        'quantity',
        'price',
        'details',
        'status',
        'extra',
        'proforma_number',
        'proforma_date',
        'contract_number',
        'user_id',
        'assignee_id',
        'category_id',
        'product_id',
        'buyer_id',
        'supplier_id',
        'percentage',
        'reference_number',
        'part'
    ];

    protected $casts = [
        'details' => 'json',
        'extra' => 'json',
        'proforma_status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'proforma_date' => 'date',
    ];

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    protected static function booted()
    {
        static::creating(function ($proformaInvoice) {
            $proformaInvoice->user_id = auth()->id();
        });


        static::saving(function ($proformaInvoice) {
            $proformaInvoice->attachments->each(function ($attachment) {
                if (empty($attachment->file_path) || empty($attachment->name)) {
                    $attachment->delete();
                }
            });
        });
    }


    public function buyer()
    {
        return $this->belongsTo(Buyer::class, 'buyer_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function names()
    {
        return $this->hasMany(Name::class);
    }

    public function notificationSubscriptions()
    {
        return $this->morphMany(NotificationSubscription::class, 'notifiable');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'proforma_invoice_id');
    }

    public function activeOrders()
    {
        return $this->hasMany(Order::class, 'proforma_invoice_id')
            ->whereNull('deleted_at');
    }

    public function paymentRequests()
    {
        return $this->hasOneThrough(PaymentRequest::class, Order::class, 'proforma_invoice_id', 'order_id');
    }

    public function associatedPaymentRequests()
    {
        return $this->belongsToMany(
            PaymentRequest::class,
            'payment_request_proforma_invoice',
            'proforma_invoice_id',
            'payment_request_id'
        );
    }


    public function activeApprovedPaymentRequests()
    {
        return $this->belongsToMany(
            PaymentRequest::class,
            'payment_request_proforma_invoice',
            'proforma_invoice_id',
            'payment_request_id'
        )
            ->whereNull('order_id')
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['pending', 'cancelled', 'rejected']);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    // Computational Methods
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

    public static function hasMatchingProformaNumber(string $search)
    {
        return self::where('proforma_number', 'like', "%{$search}%")
            ->exists();
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

    // DEPRECATED as they belonged to single filter QUERY
//    public static function getLatestProformaDate(): ?string
//    {
//        return Cache::remember('latest_proforma_date', now()->addMinutes(15), function () {
//            $latestProformaDate = self::whereNotNull('proforma_date')
//                ->orderByDesc('proforma_date')
//                ->value('proforma_date');
//
//            return $latestProformaDate ? Carbon::parse($latestProformaDate)->format('j F Y') : 'N/A';
//        });
//    }
//    public static function getTotalQuantityByYearAndCategoryAndMonth($year, $category_id = null, $month = null): int
//    {
//        if (!$year || $year === 'all') {
//            return 0;
//        }
//
//        $monthCacheKey = is_array($month) ? implode('_', $month) : ($month ?? 'all');
//        $categoryCacheKey = is_array($category_id) ? implode('_', $category_id) : ($category_id ?? 'all');
//        $cacheKey = 'proforma_invoices_total_quantity_' . $year . '_' . $categoryCacheKey . '_' . $monthCacheKey;
//
//        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($year, $category_id, $month) {
//            $query = "SELECT SUM(quantity) AS total_quantity FROM proforma_invoices WHERE deleted_at IS NULL AND YEAR(proforma_date) = ?";
//            $bindings = [$year];
//
//            if ($category_id) {
//                $query .= is_array($category_id) ? " AND category_id IN (" . implode(',', array_fill(0, count($category_id), '?')) . ")" : " AND category_id = ?";
//                $bindings = array_merge($bindings, (array)$category_id);
//            }
//
//            if ($month && $month !== 'all') {
//                if (is_array($month)) {
//                    $query .= " AND MONTH(proforma_date) IN (" . implode(',', array_fill(0, count($month), '?')) . ")";
//                    $bindings = array_merge($bindings, $month);
//                } else {
//                    $query .= " AND MONTH(proforma_date) = ?";
//                    $bindings[] = $month;
//                }
//            }
//
//            $result = DB::selectOne($query, $bindings);
//            return $result->total_quantity ?? 0;
//        });
//    }
//    public static function getTotalQuantityWithBLDateByFilters($year, $category_id = null, $month = null): int
//    {
//        if (!$year || $year === 'all') {
//            return 0;
//        }
//
//        $cacheKey = 'proforma_invoices_total_quantity_with_bl_' . $year . '_' . ($category_id ?? 'all') . '_' . ($month ?? 'all');
//
//        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($year, $category_id, $month) {
//        return self::whereNull('deleted_at')
//            ->whereHas('orders', function ($query) use ($year, $month) {
//                $query->whereHas('doc', function ($subQuery) use ($year, $month) {
//                    $subQuery->whereNotNull('BL_date')
//                        ->when($year && $year !== 'all', fn($query) => $query->whereYear('BL_date', $year))
//                        ->when($month && $month !== 'all', fn($query) => $query->whereMonth('BL_date', $month));
//                });
//            })
//            ->when($category_id, fn($query) => $query->where('category_id', $category_id))
//            ->sum('quantity') ?: 0;
//        });
//    }
//    public static function getTotalQuantityWithBLDateByFilters($year, $category_id = null, $month = null): int
//    {
//        if (!$year || $year === 'all') {
//            return 0;
//        }
//
//        $monthCacheKey = is_array($month) ? implode('_', $month) : ($month ?? 'all');
//        $categoryCacheKey = is_array($category_id) ? implode('_', $category_id) : ($category_id ?? 'all');
//        $cacheKey = 'proforma_invoices_total_quantity_with_bl_' . $year . '_' . $categoryCacheKey . '_' . $monthCacheKey;
//
//
//        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($year, $category_id, $month) {
//            return self::whereNull('deleted_at')
//                ->whereHas('orders', function ($query) use ($year, $month) {
//                    $query->whereHas('doc', function ($subQuery) use ($year, $month) {
//                        $subQuery->whereNotNull('BL_date')
//                            ->when($year && $year !== 'all', fn($query) => $query->whereYear('BL_date', $year))
//                            ->when($month && $month !== 'all', function ($query) use ($month) {
//                                if (is_array($month)) {
//                                    $query->whereMonth('BL_date', $month[0]);
//                                    foreach (array_slice($month, 1) as $monthValue) {
//                                        $query->orWhereMonth('BL_date', $monthValue);
//                                    }
//                                } else {
//                                    $query->whereMonth('BL_date', $month);
//                                }
//                            });
//                    });
//                })
//                ->when($category_id, function ($query) use ($category_id) {
//                    if (is_array($category_id)) {
//                        $query->whereIn('category_id', $category_id);
//                    } else {
//                        $query->where('category_id', $category_id);
//                    }
//                })
//                ->sum('quantity') ?: 0;
//        });
//    }

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


    private static function generateCacheKey($prefix, $month, $category_id, $year): string
    {
        $monthCacheKey = is_array($month) ? implode('_', $month) : ($month ?? 'all');
        $categoryCacheKey = is_array($category_id) ? implode('_', $category_id) : ($category_id ?? 'all');
        return $prefix . $year . '_' . $categoryCacheKey . '_' . $monthCacheKey;
    }
}
