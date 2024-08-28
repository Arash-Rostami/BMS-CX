<?php

namespace App\Services;

use App\Services\traits\MainOrder;
use App\Services\traits\SubOrders;
use Illuminate\Support\Facades\Cache;


class OrderPaymentCalculationService
{
    use MainOrder, SubOrders;

    public static function processPaymentStub($get, $set, $record)
    {
        $part = $get('part');

        if (empty($part) || $part == 'main') {
            self::addError("Please select the part before attempting calculations.");
            return;
        }

        $details = [
            'percentage' => (float)$get('orderDetail.extra.percentage') ?? 0,
            'buyingPrice' => (float)$get('orderDetail.buying_price') ?? 0,
            'buyingQuantity' => (float)$get('orderDetail.buying_quantity') ?? 0,
            'provisionalPrice' => (float)$get('orderDetail.provisional_price') ?? 0,
            'provisionalQuantity' => (float)$get('orderDetail.provisional_quantity') ?? 0,
            'finalPrice' => (float)$get('orderDetail.final_price') ?? 0,
            'finalQuantity' => (float)$get('orderDetail.final_quantity') ?? 0,
            'lastOrder' => $get('orderDetail.extra.lastOrder') ?? false,
        ];

        if ($part == 1) {
            self::processMainOrder($record, $details, $set);
        }

        if ($part != 1) {
            self::processSubOrders($record, $get, $details, $set);
        }
    }

    private static function calculateCombinedTotals($orders)
    {
        $totals = $orders->map(function ($order) {
            return [
                'initialPayment' => (float)($order->orderDetail->extra['initialPayment'] ?? 0),
                'provisionalTotal' => (float)($order->orderDetail->extra['provisionalTotal'] ?? 0),
                'finalTotal' => (float)($order->orderDetail->extra['finalTotal'] ?? 0),
                'quantityTotal' => (float)($order->orderDetail->provisional_quantity ?? 0)
            ];
        });

        return [
            $totals->sum('initialPayment'),
            $totals->sum('provisionalTotal'),
            $totals->sum('finalTotal'),
            $totals->sum('quantityTotal')
        ];
    }

    private static function setOrderDetails($set, $details, $keys)
    {
        foreach ($keys as $detailKey => $setKey) {
            if (isset($details[$detailKey])) {
                $set("orderDetail.extra.$setKey", sprintf("%.2f", $details[$detailKey]));
            }
        }
    }

    private static function computeProvisionalTotal($details, $initialPayment)
    {
        return ($details['provisionalPrice'] > 0 && $details['provisionalQuantity'] > 0)
            ? (($details['provisionalPrice'] * $details['provisionalQuantity']) - $initialPayment)
            : 0;
    }

    private static function computeFinalTotal($details)
    {
        return ($details['finalPrice'] > 0 && $details['finalQuantity'] > 0)
            ? ($details['finalPrice'] * $details['finalQuantity']) - ($details['provisionalPrice'] * $details['provisionalQuantity'])
            : 0;
    }

    private static function computeCumulative($details, $initialPayment)
    {
        return ($details['finalTotal'] > 0)
            ? ($details['finalTotal'] + $details['provisionalTotal'] + $initialPayment)
            : (($details['provisionalTotal'] > 0) ? ($details['provisionalTotal'] + $initialPayment) : $initialPayment);
    }

    private static function computeFinal($details, $initialPayment, $initialTotal)
    {
        return ($details['finalTotal'] > 0)
            ? ($details['finalTotal'] + $details['provisionalTotal'] + $initialPayment)
            : (($details['provisionalTotal'] > 0) ? ($details['provisionalPrice'] * $details['provisionalQuantity']) : $initialTotal);
    }

    public static function addError($message)
    {
        $errors = Cache::get('errors', []);
        $errors[] = ['message' => $message];

        Cache::put('errors', $errors, 5);
    }
}
