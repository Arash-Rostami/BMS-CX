<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Cache;

trait OrderDetailComputations
{

    public static function fetchChartDataByCategory(string|int $year = 'all')
    {
        return self::fetchChartData($year, 'category', 'category_id');
    }

    protected static function fetchChartData(string|int $year, $relation, $groupColumn)
    {
        $cacheKey = "orders_data_by_{$relation}_{$year}";

        return Cache::remember($cacheKey, 20,
            fn() => self::baseQuery($year, ["order.{$relation}"])
                ->get()
                ->groupBy("order.{$groupColumn}")
                ->pipe(fn($items) => self::mapQueryResultsWithSum($items, $relation))
        );
    }

    protected static function baseQuery($year, array $with = [])
    {
        return static::query()
            ->with($with)
            ->when($year !== 'all', fn($q) => $q->whereHas('order', fn($q2) => $q2->whereYear('proforma_date', $year)));
    }

    protected static function mapQueryResultsWithSum($items, string $relationType)
    {
        return $items->map(fn($group, $key) => [
            'name' => optional($group->first()->order->{$relationType})->name ?? 'Unknown',
            'totalQuantity' => $group->sum(fn($item) => self::getQuantity($item)),
            'totalPrice' => $group->sum(fn($item) => self::getTotalPrice($item)),
        ]);
    }

    protected static function getQuantity($item)
    {
        return self::getValidQuantity($item, ['final_quantity', 'provisional_quantity', 'buying_quantity']);
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

    protected static function getTotalPrice($item)
    {
        $quantityFields = ['final_quantity', 'provisional_quantity', 'buying_quantity'];
        $priceFields = ['final_price', 'provisional_price', 'buying_price'];

        return self::getValidPrice($quantityFields, $priceFields, $item);
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

    public static function fetchChartDataByProduct(string|int $year = 'all')
    {
        return self::fetchChartData($year, 'product', 'product_id');
    }

    public function isLastOrderTaken(): bool
    {
        return $this->checkOrderFlag('lastOrder');
    }

    protected function checkOrderFlag(string $key): bool
    {
        return $this->getOtherOrders()
            ->contains(fn($order) => data_get($order, "orderDetail.extra.{$key}", false) === true);
    }

    protected function getOtherOrders()
    {
        $this->loadMissing('order.proformaInvoice.orders');

        return collect($this->order
            ->proformaInvoice
            ->orders ?? [])
            ->where('id', '!=', $this->order->id);
    }

    public function areAllOrdersTaken(): bool
    {
        return $this->checkOrderFlag('allOrders');
    }


    public function hasApprovedRelatedRequests()
    {
        return $this->order
            && $this->order->paymentRequests
            && $this->order->paymentRequests->contains(fn($pr) => in_array($pr->status, ['approved', 'allowed', 'processing', 'completed'])
                && $pr->currency === $this->order->currency
            );
    }
}
