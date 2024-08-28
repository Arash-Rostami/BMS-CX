<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;


class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'invoice_number',
        'part',
        'grade',
        'proforma_number',
        'proforma_date',
        'order_status',
        'extra',
        'order_request_id',
        'user_id',
        'purchase_status_id',
        'category_id',
        'product_id',
        'order_detail_id',
        'party_id',
        'logistic_id',
        'doc_id',
        'attachment_id',
    ];

    protected $casts = [
        'extra' => 'json',
        'proforma_date' => 'date',
        'extra.docs_received' => 'json'
    ];

    protected $table = 'orders';


    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function docAttachment()
    {
        return $this->hasOne(Attachment::class);
    }


    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
            $post->order_number = self::makeOrderNumber($post);
        });

        static::updating(function ($post) {
            $post->order_number = self::makeOrderNumber($post);
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

    /**
     * Get the category associated with the order.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
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


    /**
     * Get the doc associated with the order.
     */
    public function doc()
    {
        return $this->belongsTo(Doc::class, 'doc_id');
    }


    public function getInvoiceNumberWithPartAttribute()
    {
        $firstIdentifier = $this->logistic->booking_number ?? 'N/A';
        $secondIdentifier = $this->extra['reference_number'] ?? 'N/A';
        return "Booking# {$firstIdentifier} 💢Ref: {$secondIdentifier}";
    }

    public function getInvoiceNumberWithReferenceNumberAttribute()
    {
        $prjNum = $this->invoice_number;
        $refNum = $this->extra['reference_number'] ?? 'No Ref. No.';

        return "{$prjNum} (Ref: {$refNum})";
    }

    public static function findByOrderRequestId($orderRequestId)
    {
        return self::where('order_request_id', $orderRequestId)
            ->where('part', 1)
            ->get();
    }

    public static function findByProjectNumber($orderRequestId, $mainPart = true)
    {
        $query = self::where('invoice_number', $orderRequestId);

        return ($mainPart)
            ? $query->where('part', 1)->first()
            : $query->where('part', '!=', 1)->get();
    }

    /**
     * Get the logistic associated with the order.
     */
    public
    function logistic()
    {
        return $this->belongsTo(Logistic::class, 'logistic_id');
    }


    public
    static function getStatusCounts()
    {
        return static::select('purchase_status_id')
            ->selectRaw('count(*) as count')
            ->groupBy('purchase_status_id')
            ->get()
            ->keyBy('purchase_status_id')
            ->map(fn($item) => $item->count);
    }


    public
    static function makeOrderNumber($post): string
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

    public
    function names()
    {
        return $this->hasMany(Name::class);
    }


    /**
     * Get the stock associated with the order.
     */
    public
    function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id');
    }

    /**
     * Get the stock associated with the order.
     */
    public function orderRequest()
    {
        return $this->belongsTo(OrderRequest::class, 'order_request_id');
    }

    public function suborders()
    {
        return $this->hasMany(Order::class, 'invoice_number', 'invoice_number')
            ->where('part', '<>', 1);
    }

    // Order.php
    public
    function aggregateSuborderDetails()
    {
        return $this->suborders()->get()->reduce(function ($carry, $item) {
            $data = json_decode($item->extra);
            $carry->totalInitialPayment += $data->initialPayment ?? 0;
            $carry->totalProvisionalPayment += $data->provisionalPayment ?? 0;
            $carry->totalFinalPayment += $data->finalPayment ?? 0;
            $carry->totalInitialQuantity += $data->initialTotal ?? 0;
            $carry->totalProvisionalQuantity += $data->provisionalTotal ?? 0;
            $carry->totalFinalQuantity += $data->finalTotal ?? 0;
            $carry->totalCumulative += $data->payment ?? 0;
            $carry->totalAmount += $data->total ?? 0;
            $carry->totalRemaining += $data->remaining ?? 0;
            return $carry;
        }, (object)[
            'totalInitialPayment' => 0,
            'totalProvisionalPayment' => 0,
            'totalFinalPayment' => 0,
            'totalInitialQuantity' => 0,
            'totalProvisionalQuantity' => 0,
            'totalFinalQuantity' => 0,
            'totalCumulative' => 0,
            'totalAmount' => 0,
            'totalRemaining' => 0,
        ]);
    }


    /**
     * Get the party associated with the order.
     */
    public
    function party()
    {
        return $this->belongsTo(Party::class,);
    }

    /**
     * Get the product associated with the order.
     */
    public
    function payments()
    {
        return $this->hasManyThrough(Payment::class, PaymentRequest::class, 'order_invoice_number', 'payment_request_id', 'invoice_number', 'id');

    }

    /**
     * Get the product associated with the order.
     */
    public function paymentRequests()
    {
        return $this->hasMany(PaymentRequest::class, 'order_invoice_number', 'invoice_number');
    }


    /**
     * Get the product associated with the order.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the Status associated with the order.
     */
    public
    function purchaseStatus()
    {
        return $this->belongsTo(PurchaseStatus::class, 'purchase_status_id');
    }

    public
    function scopeUniqueInvoiceNumber(Builder $query)
    {
        return $query->where('order_request_id', '<>', null)
            ->where('order_status', '<>', 'closed')
            ->where('part','=',1)
            ->get()
            ->sortBy('invoice_number')
            ->filter(function ($order) {
                static $seenRequestIds = [];
                return !isset($seenRequestIds[$order->invoice_number]) &&
                    $seenRequestIds[$order->invoice_number] = true;
            });
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'order_tag', 'order_id', 'tag_id');
    }

    /**
     * @param $post
     */
    /**
     * Get the user that owns the order.
     * //     */
    public
    function user()
    {
        return $this->belongsTo(User::class);
    }
}
