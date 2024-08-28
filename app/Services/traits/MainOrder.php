<?php

namespace App\Services\traits;

use App\Models\Order;

trait MainOrder
{
    protected static function processMainOrder($record, $details, $set)
    {
        if ($record === null) {
            self::addError("Please create the record before attempting calculations.");
            return;
        }

        $invoiceNumber = optional($record)->invoice_number;
        $orders = Order::findByProjectNumber($invoiceNumber, false);

        if (!$orders) {
            self::addError("No orders were found for this record.");
            return;
        }

        list($details['initialCombined'], $details['provisionalCombined'], $details['finalCombined'], $details['quantityCombined']) =
            self::calculateCombinedTotals($orders);


        $details['initialTotal'] = $details['buyingPrice'] * $details['buyingQuantity'];
        $details['initialPayment'] = $details['percentage'] * $details['initialTotal'] / 100;
        $details['remainingQuantity'] = $details['buyingQuantity'] - $details['quantityCombined'];

        $details['initialPayment'] = ($details['initialTotal'] == 0) ? $details['initialCombined'] : $details['initialPayment'];
        $details['provisionalTotal'] = ($details['provisionalPrice'] == 0 || $details['provisionalQuantity'] == 0)
            ? $details['provisionalCombined']
            : self::computeProvisionalTotal($details, $details['initialPayment']);
        $details['finalTotal'] = ($details['finalPrice'] == 0 || $details['finalQuantity'] == 0)
            ? $details['finalCombined']
            : self::computeFinalTotal($details);

        $details['cumulative'] = self::computeCumulative($details, $details['initialPayment']);
        $details['total'] = self::computeFinal($details, $details['initialPayment'], $details['initialTotal']);

        $details['remaining'] = (($details['provisionalPrice'] == 0 || $details['provisionalQuantity'] == 0) && ($details['finalPrice'] == 0 || $details['finalQuantity'] == 0))
            ? 0
            : $details['total'] - $details['cumulative'];

        $keys = [
            'initialPayment' => 'initialPayment',
            'initialTotal' => 'initialTotal',
            'provisionalTotal' => 'provisionalTotal',
            'finalTotal' => 'finalTotal',
            'cumulative' => 'payment',
            'remaining' => 'remaining',
            'total' => 'total',
            'remainingQuantity' => 'payableQuantity'
        ];

        self::setOrderDetails($set, $details, $keys);
    }

}
