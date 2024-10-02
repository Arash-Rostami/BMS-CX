<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class OrderDetail extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'order_details';


    protected $fillable = [
        'buying_quantity',
        'initial_quantity',
        'provisional_quantity',
        'final_quantity',
        'buying_price',
        'initial_price',
        'provisional_price',
        'final_price',
        'extra',
        'user_id',
    ];

    protected $casts = [
        'extra' => 'json',
    ];

    protected $dates = ['deleted_at'];

    public static bool $filamentDetection = false;


    protected static function booted()
    {
        static::creating(function ($orderDetail) {
            $orderDetail->user_id = auth()->id();
        });
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    /**
     * Get the user that owns the stock.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    // Computational Methods
    public function isLastOrderTaken()
    {
        if (!$this->relationLoaded('order')) {
            $this->load('order.proformaInvoice.orders');
        }

        $relatedOrders = $this->order->proformaInvoice->orders ?? [];

        $otherOrders = collect($relatedOrders)->filter(function ($order) {
            return $order->id !== $this->order->id;
        });

        return $otherOrders->contains(function ($order) {
            return data_get($order, 'orderDetail.extra.lastOrder', false) === true;
        });
    }


    public function areALlOrdersTaken()
    {
        if (!$this->relationLoaded('order')) {
            $this->load('order.proformaInvoice.orders');
        }

        $relatedOrders = $this->order->proformaInvoice->orders ?? [];

        $otherOrders = collect($relatedOrders)->filter(function ($order) {
            return $order->id !== $this->order->id;
        });

        return $otherOrders->contains(function ($order) {
            return data_get($order, 'orderDetail.extra.allOrders', false) === true;
        });
    }


    protected static function baseQuery($year, array $with = [])
    {
        $query = static::query()
            ->with($with);

        if ($year !== 'all') {
            $query->whereHas('order', function ($q) use ($year) {
                $q->whereYear('proforma_date', $year);
            });
        }

        return $query;
    }


    public static function fetchChartDataByProduct($year = 'all')
    {
        $cacheKey = 'orders_data_by_product_' . $year;

        return Cache::remember($cacheKey, 5, function () use ($year) {
            $items = self::baseQuery($year, ['order.product'])
                ->get()
                ->groupBy('order.product_id');

            return self::mapQueryResultsWithSum($items, 'product');
        });
    }

    public static function fetchChartDataByCategory($year = 'all')
    {
//        $cacheKey = 'orders_data_by_category_' . $year;

//        return Cache::remember($cacheKey, 5, function () use ($year) {
        $items = self::baseQuery($year, ['order.category'])
            ->get()
            ->groupBy('order.category_id');

        return self::mapQueryResultsWithSum($items, 'category');
//        });
    }

    protected static function mapQueryResultsWithSum($items, $relationType)
    {
        return $items->map(function ($groupItems, $groupId) use ($relationType) {
            $firstItem = $groupItems->first();
            $relationName = optional(optional($firstItem->order)->{$relationType})->name ?? 'Unknown';

            $totalQuantity = $groupItems->sum(fn($item) => self::getQuantity($item));
            $totalPrice = $groupItems->sum(fn($item) => self::getTotalPrice($item));

            return [
                'name' => $relationName,
                'totalPrice' => $totalPrice,
                'totalQuantity' => $totalQuantity,
            ];
        });
    }

    protected static function getQuantity($item)
    {
        return self::getValidQuantity($item, ['final_quantity', 'provisional_quantity', 'buying_quantity']);
    }

    protected static function getTotalPrice($item)
    {
        $quantityFields = ['final_quantity', 'provisional_quantity', 'buying_quantity'];
        $priceFields = ['final_price', 'provisional_price', 'buying_price'];

        return self::getValidPrice($quantityFields, $priceFields, $item);
    }


    private static function getValidQuantity($item, $fields)
    {
        foreach ($fields as $field) {
            if (isset($item->{$field}) && $item->{$field} > 0) {
                return $item->{$field};
            }
        }
        return 0;
    }


    private static function getValidPrice(array $quantityFields, array $priceFields, $item): int|float
    {
        foreach ($quantityFields as $index => $quantityField) {
            $priceField = $priceFields[$index];
            if (self::isQuantityAndPriceSet($quantityField, $item, $priceField)) {
                return $item->{$quantityField} * $item->{$priceField};
            }
        }

        return 0;
    }

    private static function isQuantityAndPriceSet(string $quantityField, $item, string $priceField): bool
    {
        return isset($item->{$quantityField}) && $item->{$quantityField} > 0 && isset($item->{$priceField}) && $item->{$priceField} > 0;
    }


    public function hasApprovedRelatedRequests()
    {
        if ($this->order && $this->order->paymentRequests && !$this->order->paymentRequests->isEmpty()) {
            return $this->order->paymentRequests->contains(function ($paymentRequest) {
                return in_array($paymentRequest->status, ['approved', 'allowed', 'processing', 'completed']);
            });
        }
        return false;
    }
}
