<?php

namespace App\Services;


use App\Services\traits\Calculator;
use App\Services\traits\Project;
use Illuminate\Support\Facades\Cache;


class OrderPaymentCalculationService
{
    use Calculator, Project;

    public static function processPaymentStub($get, $set, $record)
    {
        $part = $get('part');

        if (empty($part)) {
            self::addError("Please select the part before attempting calculations.");
            return;
        }


        if($get('orderDetail.extra.manualComputation')){
            self::addError("Manual computation enabled. Automatic calculations are off.");
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
            'allOrders' => $get('orderDetail.extra.allOrders') ?? false,
        ];

        self::processOrders($record, $get, $details, $set);
    }

    public static function addError($message)
    {
        $errors = Cache::get('errors', []);
        $errors[] = ['message' => $message];

        Cache::put('errors', $errors, 5);
    }
}
