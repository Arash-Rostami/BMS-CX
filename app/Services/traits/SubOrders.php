<?php

namespace App\Services\traits;

use App\Models\Order;

trait SubOrders
{
    protected static function processSubOrders($record, $get, $details, $set)
    {
        $invoiceNumber = $record->invoice_number ?? $get('extra.manual_invoice_number');
        $order = Order::findByProjectNumber($invoiceNumber);
        $orders = Order::findByProjectNumber($invoiceNumber, false);


        if (!$order || $order->orderDetail->buying_quantity == 0) {
            self::addError("Main order not found! Please create the record before attempting calculations.");
            return;
        }

        if ($order->orderDetail->buying_quantity == 0) {
            self::addError("Main order's buying quantity not found! Please fill quantity before attempting calculations.");
            return;
        }

        $totalQuantity = $orders->reject(function ($suborder) use ($record) {
            return $record !== null && $suborder->id === $record->id;
        })->map(function ($suborder) {
            return (float)$suborder->orderDetail->provisional_quantity ?? 0;
        })->sum();


        $details['initialPayment'] = (float)$order->orderDetail?->extra['initialPayment'];
        $details['initialQuantity'] = $order->orderDetail?->buying_quantity;

        if ($details['initialPayment'] == 0 || $details['initialQuantity'] == 0) {
            self::addError("Initial payment or quantity is missing. Please ensure both values are set for this or the main (⭐) order.");
            return;
        }

        $details['pricePerUnit'] = $details['initialPayment'] / $details['initialQuantity'];

        $details['availableQuantity'] = max(0, $details['initialQuantity'] - $totalQuantity);
        $details['payableQuantity'] = min($details['availableQuantity'], $details['provisionalQuantity']);
        $details['initialPaymentForSubOrder'] = $details['lastOrder']
            ? $details['availableQuantity'] * $details['pricePerUnit']
            : $details['payableQuantity'] * $details['pricePerUnit'];

        $details['provisionalTotal'] = self::computeProvisionalTotal($details, $details['initialPaymentForSubOrder']);
        $details['finalTotal'] = self::computeFinalTotal($details);
        $details['cumulative'] = self::computeCumulative($details, $details['initialPaymentForSubOrder']);
        $details['total'] = self::computeFinal($details, $details['initialPaymentForSubOrder'], $details['initialPaymentForSubOrder']);

        $details['remaining'] = $details['total'] - $details['cumulative'];

        $keys = [
            'initialPaymentForSubOrder' => 'initialPayment',
            'initialPayment' => 'initialTotal',
            'provisionalTotal' => 'provisionalTotal',
            'finalTotal' => 'finalTotal',
            'cumulative' => 'payment',
            'remaining' => 'remaining',
            'total' => 'total',
            'availableQuantity' => 'payableQuantity'
        ];
        self::setOrderDetails($set, $details, $keys);
    }
}
