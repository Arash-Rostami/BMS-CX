<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'reference_number',
        'invoice_number',
        'part',
        'grade_id',
        'proforma_number',
        'proforma_date',
        'order_status',
        'extra',
        'proforma_invoice_id',
        'user_id',
        'purchase_status_id',
        'category_id',
        'product_id',
        'order_detail_id',
        'party_id',
        'logistic_id',
        'doc_id',
    ];

    protected $casts = [
        'extra' => 'json',
        'proforma_date' => 'date',
    ];

    protected $table = 'orders';


    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }


    protected static function booted()
    {
        static::creating(function ($order) {
            $order->user_id = auth()->id();
            $order->order_number = self::makeOrderNumber($order);
        });

        static::saving(function ($order) {
            $order->attachments->each(function ($attachment) {
                if (empty($attachment->file_path) || empty($attachment->name)) {
                    $attachment->delete();
                }
            });
        });

        static::updating(function ($order) {
            $order->order_number = self::makeOrderNumber($order);
        });
    }


    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function doc()
    {
        return $this->belongsTo(Doc::class, 'doc_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }


    public function logistic()
    {
        return $this->belongsTo(Logistic::class, 'logistic_id');
    }


    public function names()
    {
        return $this->hasMany(Name::class);
    }

    public function notificationSubscriptions()
    {
        return $this->morphMany(NotificationSubscription::class, 'notifiable');
    }


    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id');
    }


    public function proformaInvoice()
    {
        return $this->belongsTo(ProformaInvoice::class, 'proforma_invoice_id', 'id');
    }


    public function party()
    {
        return $this->belongsTo(Party::class,);
    }


    public function payments()
    {
        return $this->belongsToMany(
            Payment::class,
            'payment_payment_request',
            'payment_request_id',
            'payment_id'
        );
    }

    public function paymentRequests()
    {
        return $this->hasMany(PaymentRequest::class, 'order_id');
    }

    public function associatedPaymentRequests()
    {
        return $this->proformaInvoice->associatedPaymentRequests();
    }


    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }


    public function purchaseStatus()
    {
        return $this->belongsTo(PurchaseStatus::class, 'purchase_status_id');
    }


    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'order_tag', 'order_id', 'tag_id');
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    // Computational Methods //
    public static function aggregateOrderGroupTotals($record)
    {
        $orders = $record->getRelatedOrdersByInvoiceNumber();

        list($payment, $quantity) = self::getTotalPaymentRequestedAmountAndQuantity($orders);

        list($paymentRequestBalance, $quantityBalance) = self::getPaymentRequestBalance($record, $orders);


        $totalPaymentRequests = $orders->sum(fn($order) => $order->paymentRequests->count());

        $totalPayments = $orders->sum(function ($order) {
            return $order->paymentRequests->sum(fn($paymentRequest) => $paymentRequest->payments->count());
        });

        $totalOfOtherPaymentRequests = $orders->flatMap(fn($order) => $order->paymentRequests->filter(fn($paymentRequest) => !in_array($paymentRequest->type_of_payment, ['balance', 'full', 'advance'])))
            ->groupBy(fn($paymentRequest) => $paymentRequest->currency)->map(fn($paymentsByCurrency) => $paymentsByCurrency->sum('requested_amount'));

        $formattedTotalOfOtherPaymentRequests = $totalOfOtherPaymentRequests->map(
            fn($amount, $currency) => getCurrencySymbols($currency) . number_format($amount, 2)
        )->join(', ');

        return [
            'payment' => number_format($payment, 2),
            'quantity' => number_format($quantity, 2),
            'totalQuantity' => number_format(optional($record->proformaInvoice)->quantity ?? 0, 2) ?? 'Undefined',
            'totalPayment' => numberify(optional($record->proformaInvoice)->price ?? 1 * optional($record->proformaInvoice)->quantity) ?? 'Undefined',
            'shipmentPart' => optional($record->proformaInvoice)->part ?? 'Undefined',
            'daysPassed' => optional($record->proformaInvoice)->daysPassed ?? 'Undefined',
            'totalPaymentRequests' => $totalPaymentRequests,
            'totalPayments' => $totalPayments,
            'quantityBalance' => $quantityBalance,
            'paymentRequestBalance' => $paymentRequestBalance,
            'totalOfOtherPaymentRequests' => $formattedTotalOfOtherPaymentRequests
        ];
    }

//    protected static function getTotalPaymentRequestedAmountAndQuantity($orders): array
//    {
//        $payment = $orders->sum(function ($order) {
//            return
//                data_get($order, 'orderDetail.initial_payment', 0) +
//                data_get($order, 'orderDetail.provisional_total', 0) +
//                data_get($order, 'orderDetail.final_total', 0);
//        });
//
//        $quantity = $orders->sum(function ($order) {
//            return data_get($order, 'orderDetail.final_quantity', 0)
//                ?: data_get($order, 'orderDetail.provisional_quantity', 0);
//        });
//        return array($payment, $quantity);
//    }

    protected static function getTotalPaymentRequestedAmountAndQuantity($orders): array
    {
        $payment = $orders->sum(function ($order) {
            if ($order->orderDetail) {
                return
                    (float)($order->orderDetail->initial_payment ?? 0) +
                    (float)($order->orderDetail->provisional_total ?? 0) +
                    (float)($order->orderDetail->final_total ?? 0);
            }
            return 0;
        });

        $quantity = $orders->sum(function ($order) {
            if ($order->orderDetail) {
                return (float)($order->orderDetail->final_quantity ?? $order->orderDetail->provisional_quantity ?? 0);
            }
            return 0;
        });

        return [$payment, $quantity];
    }

    protected static function getPaymentRequestBalance($record, $orders): array
    {
        list($payment, $quantity) = self::getTotalPaymentRequestedAmountAndQuantity($orders);

        $expectedPayment = optional($record->proformaInvoice)->price * optional($record->proformaInvoice)->quantity ?? 0;
        $expectedQuantity = optional($record->proformaInvoice)->quantity ?? 0;


        $paymentBalance = ($payment > $expectedPayment)
            ? '🔺'
            : ($payment < $expectedPayment ? '🔻' : '✅');

        $quantityBalance = ($quantity > $expectedQuantity)
            ? '🔺'
            : ($quantity < $expectedQuantity ? '🔻' : '✅');

        return [$paymentBalance, $quantityBalance];
    }


    public static function countOrdersByStatus($year)
    {
        $cacheKey = 'orders_count_by_status_' . $year;

        return Cache::remember($cacheKey, 300, function () use ($year) {
            $query = static::query();

            if ($year !== 'all') {
                $query->whereYear('proforma_date', $year);
            }

            return $query->selectRaw('order_status, COUNT(*) as total')
                ->groupBy('order_status')
                ->get()
                ->pluck('total', 'order_status');
        });
    }


    public static function countOrdersByMonth($year)
    {
        $cacheKey = 'orders_count_by_month_' . $year;

        return Cache::remember($cacheKey, 300, function () use ($year) {
            $query = static::query();

            if ($year !== 'all') {
                $query->whereYear('proforma_date', $year);
            }

            return $query->selectRaw('MONTH(proforma_date) as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'asc')
                ->get()
                ->pluck('count', 'month');
        });
    }

    public static function countOrdersByBuyer($year)
    {
        $cacheKey = 'orders_count_by_Buyer_' . $year;

        return Cache::remember($cacheKey, 300, function () use ($year) {
            $query = static::query();

            if ($year !== 'all') {
                $query->whereYear('proforma_date', $year);
            }

            return $query->with('party.buyer')
                ->get()
                ->groupBy(function ($order) {
                    return $order->party && $order->party->buyer ? $order->party->buyer->name : 'Unknown';
                })
                ->mapWithKeys(function ($group, $key) {
                    return [$key => count($group)];
                });
        });
    }

    public static function countOrdersBySupplier($year)
    {
        $cacheKey = 'orders_count_by_Supplier_' . $year;

        return Cache::remember($cacheKey, 300, function () use ($year) {
            $query = static::query();

            if ($year !== 'all') {
                $query->whereYear('proforma_date', $year);
            }

            return $query->with('party.supplier')
                ->get()
                ->groupBy(function ($order) {
                    return $order->party && $order->party->supplier ? $order->party->supplier->name : 'Unknown';
                })
                ->mapWithKeys(function ($group, $key) {
                    return [$key => count($group)];
                });
        });
    }

    public static function countOrdersByCategory($year)
    {
        $cacheKey = 'orders_count_by_category_' . $year;

        return Cache::remember($cacheKey, 300, function () use ($year) {
            $query = static::query()->with('category');

            if ($year !== 'all') {
                $query->whereYear('proforma_date', $year);
            }

            return $query->get()
                ->groupBy('category_id')
                ->map(function ($orders) {
                    $category = $orders->first()->category;
                    return [
                        'name' => $category ? $category->name : 'Unknown',
                        'count' => $orders->count(),
                    ];
                });
        });
    }

    public static function countOrdersByProduct($year)
    {
        $cacheKey = 'orders_count_by_product_' . $year;

        return Cache::remember($cacheKey, 300, function () use ($year) {
            $query = static::query()->with('product');

            if ($year !== 'all') {
                $query->whereYear('proforma_date', $year);
            }

            return $query->get()
                ->groupBy('product_id')
                ->map(function ($orders) {
                    $product = $orders->first()->product;
                    return [
                        'name' => $product ? $product->name : 'Unknown',
                        'count' => $orders->count(),
                    ];
                });
        });
    }

    public static function getOrdersCached()
    {
        $key = 'orders_list';

        if (Cache::has($key)) {
            $orders = Cache::get($key);
        } else {
            $orders = self::pluck('reference_number', 'id')->toArray();
            Cache::put($key, $orders, 5);
        }

        return $orders;
    }

    public function getInvoiceNumberWithPartAttribute()
    {
        $firstIdentifier = $this->logistic->booking_number ?? 'N/A';
        $secondIdentifier = $this->reference_number ?? 'N/A';

        return "Booking# {$firstIdentifier} 💢Ref: {$secondIdentifier}";
    }

    public function getInvoiceNumberWithReferenceNumberAttribute()
    {
        $prjNum = $this->invoice_number;
        $refNum = $this->reference_number ?? 'No Ref. No.';

        return "{$prjNum} (Ref: {$refNum})";
    }

    public static function getStatusCounts()
    {
        return static::select('purchase_status_id')
            ->selectRaw('count(*) as count')
            ->groupBy('purchase_status_id')
            ->get()
            ->keyBy('purchase_status_id')
            ->map(fn($item) => $item->count);
    }


    public function getAllPaymentRequests()
    {
        return $this->hasMany(PaymentRequest::class)
            ->whereNull('deleted_at')
            ->orWhere(function ($query) {
//                $query->whereRaw("FIND_IN_SET(?, proforma_invoice_number) > 0", [$this->proforma_number])
                $query->whereRaw("proforma_invoice_number REGEXP CONCAT('(^|,\\s)', ?, '(\\s,|$)')", [addslashes($this->proforma_number)])
                    ->whereNull('deleted_at')
                    ->whereNull('order_id');
            });
    }

    public function getRelatedOrdersByInvoiceNumber()
    {
        $cacheKey = 'related_orders_' . $this->invoice_number ?? $this->proforma_invoice_id;

        return Cache::remember($cacheKey, 120, function () {
            return $this->query()
                ->with('orderDetail')
                ->where('invoice_number', $this->invoice_number)
                ->where('proforma_invoice_id', $this->proforma_invoice_id)
                ->whereNull('deleted_at')
                ->get();
        });
    }


    public static function findByProformaInvoiceId($proformaInvoiceId)
    {
        return self::where('proforma_invoice_id', $proformaInvoiceId)
            ->whereNull('deleted_at')
            ->get();
    }

    public static function findByProjectNumber($proformaInvoiceId)
    {
        return self::where('invoice_number', $proformaInvoiceId)
            ->whereNull('deleted_at')
            ->get();
    }


    public static function makeOrderNumber($post): string
    {
        $category = "C" . $post->category_id;
        $product = "-P" . $post->product_id;
        $proforma = "-PR" . $post->proforma_number;
        $party = "-PA" . $post->party_id;
        $orderDetail = "-OD" . $post->order_detail_id;
        $logistic = "-L" . $post->logistic_id;
        $doc = "-D" . $post->doc_id;

        return $category . $product . $proforma . $party . $orderDetail . $logistic . $doc;
    }


    public static function getTabCounts(): array
    {
        $userId = auth()->id();
        return Cache::remember("order_tab_counts_{$userId}", 60, function () use ($userId) {
            return self::select(
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(CASE WHEN purchase_status_id = 1 THEN 1 END) as pending_count'),
                DB::raw('COUNT(CASE WHEN purchase_status_id = 3 THEN 1 END) as in_transit_count'),
                DB::raw('COUNT(CASE WHEN purchase_status_id = 4 THEN 1 END) as customs_count'),
                DB::raw('COUNT(CASE WHEN purchase_status_id = 5 THEN 1 END) as delivered_count'),
                DB::raw('COUNT(CASE WHEN purchase_status_id = 6 THEN 1 END) as shipped_count'),
                DB::raw('COUNT(CASE WHEN purchase_status_id = 2 THEN 1 END) as released_count'),
            )
                ->first()
                ->toArray();
        });
    }

    public function hasCompletedBalancePayment()
    {
        $orderId = $this->id;
        $cacheKey = "hasCompletedBalancePayment_" . $orderId;

        return Cache::remember($cacheKey, 5 * 60, function () use ($orderId) {
            $sql = "
            SELECT 1
            FROM payment_requests pr
            INNER JOIN orders o ON pr.order_id = o.id
            INNER JOIN payment_payment_request ppr ON pr.id = ppr.payment_request_id
            INNER JOIN payments p ON ppr.payment_id = p.id
            WHERE pr.status = 'completed'
              AND pr.type_of_payment = 'balance'
              AND pr.deleted_at IS NULL
              AND p.deleted_at IS NULL
              AND p.date < CURDATE() - INTERVAL 3 DAY
              AND NOT EXISTS (
                  SELECT 1
                  FROM attachments a
                  WHERE a.order_id = o.id
                    AND LOWER(a.name) LIKE '%telex-release%'
              )
              AND o.id = ?
            GROUP BY pr.id, pr.requested_amount
            HAVING SUM(p.amount) >= pr.requested_amount
            LIMIT 1
        ";

            $results = DB::select($sql, [$orderId]);

            return !empty($results);
        });
    }
}
