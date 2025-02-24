<?php

namespace App\Services\traits;

use App\Models\Order;
use App\Models\PaymentRequest;

trait Calculator
{
    public static function computeTotalQuantityAndCheckLastOrder($orders, $record, $details): mixed
    {
        $filteredOrders = $orders->reject(function ($each) use ($record) {
            return $record !== null && $each->id === $record->id;
        });

        $details['initialProjectPayment'] = $filteredOrders->sum(function ($order) {
            return (float)($order->orderDetail->initial_payment ?? 0);
        });

        $details['totalQuantity'] = $filteredOrders->sum(function ($order) {
            return (float)($order->orderDetail->provisional_quantity ?? 0);
        });

        $details['hasUsedUpPrepayment'] = $filteredOrders->contains(function ($order) {
            return !empty($order->orderDetail?->extra) && (
                    ($order->orderDetail->extra['lastOrder'] ?? false) || ($order->orderDetail->extra['allOrders'] ?? false)
                );
        });

        return $details;
    }

    public static function computePaymentRequestDetails($paymentRequests, $details)
    {
        $details['initialPayment'] = $paymentRequests->sum('requested_amount');
        $details['initialTotal'] = $paymentRequests->sum('total_amount');
        $details['allProformaNumber'] = $paymentRequests->pluck('proforma_invoice_number')->map(fn($item) => explode(',', $item))
            ->flatten()->map(fn($item) => trim($item))->unique()->values()->toArray();
        return $details;
    }

    public static function computeTotalProformaInvoices($proformaInvoices, $record): mixed
    {

        $details['initialProjectsPayment'] = $proformaInvoices->sum(function ($eachProformaInvoice) use ($record) {
            // For each proforma invoice, reject the specific order that matches the $record
            return (float)($eachProformaInvoice->orders->reject(function ($eachOrder) use ($record) {
                // Reject the order if $record is not null and the order ID matches $record->id
                return $record !== null && $eachOrder->id === $record->id;
            })->sum(function ($order) {
                // Sum the initial payment for the remaining orders
                return (float)(optional($order->orderDetail)->initial_payment ?? 0);
            }));
        });


        $details['totalProforma'] = $proformaInvoices->map(function ($each) {
            return (float)$each->quantity * (float)$each->price * ((float)$each->percentage / 100);
        })->sum();

        return [$details['totalProforma'], $details['initialProjectsPayment']];
    }

    public static function computeAssociatedProformaInvoiceDetails($record): float
    {
        $proformaInvoice = optional($record->proformaInvoice);
        $price = (float)$proformaInvoice->price ?? 0;
        $quantity = (float)$proformaInvoice->quantity ?? 0;
        $percentage = (float)$proformaInvoice->percentage ?? 100;

        return max(0, $price * $quantity * ($percentage / 100));
    }

    public static function computeAdjustedRatio($details, float $associatedProformaInvoice, mixed $totalProforma)
    {
        $shareOfProforma = !$details['allOrders'] ? ($associatedProformaInvoice / $totalProforma) : 1;
        $details['projectLimit'] = $details['initialPayment'] * ($associatedProformaInvoice / $totalProforma);
        $details['initialPayment'] = $details['initialPayment'] * $shareOfProforma;

        $details['pricePerUnit'] = $details['hasUsedUpPrepayment'] ? 0.0 : $details['initialPayment'] / $details['initialQuantity'];
        $details['availableQuantity'] = max(0, $details['initialQuantity'] - $details['totalQuantity']);
        $details['payableQuantity'] = min($details['availableQuantity'], $details['provisionalQuantity']);
        $details['remainderInitial'] = $details['allOrders']
            ? ($totalProforma - $details['initialProjectsPayment'])
            : $details['initialPayment'] - $details['initialProjectPayment'];

        return $details;
    }


    public static function
    calculateInitialPaymentForOrder($details)
    {
        $orderTypeKey = ($details['lastOrder'] ? '1' : '0') . ($details['allOrders'] ? '1' : '0');

        $availableProductCost = $details['availableQuantity'] * $details['pricePerUnit'];

        switch ($orderTypeKey) {
            case '11':  // Last order and all proforma invoices
                $adjustedPayment = $details['initialPayment'] - $details['initialProjectPayment'];
                return ($adjustedPayment > $availableProductCost) ? $details['remainderInitial'] : $availableProductCost;

            case '10':  // Last order but not all proforma invoices
                return ($details['remainderInitial'] > $availableProductCost) ? $details['remainderInitial'] : $availableProductCost;

            case '01':  // Not last order but all proforma invoices
                $adjustedPayment = $details['initialPayment'] - $details['initialProjectPayment'];
                $excess = $details['projectLimit'] - $details['initialProjectPayment'];
                return ($adjustedPayment > $availableProductCost) ? $details['remainderInitial'] - $excess : $availableProductCost;

            default:    // Not last order and not all proforma invoices
                return $details['payableQuantity'] * $details['pricePerUnit'];
        }
    }


    public static function computeProvisionalTotal($details, $initialPayment)
    {
        return ($details['provisionalPrice'] > 0 && $details['provisionalQuantity'] > 0)
            ? (($details['provisionalPrice'] * $details['provisionalQuantity']) - $initialPayment)
            : 0;
    }

    public static function computeFinalTotal($details)
    {
        return ($details['finalPrice'] > 0 && $details['finalQuantity'] > 0)
            ? ($details['finalPrice'] * $details['finalQuantity']) - ($details['provisionalPrice'] * $details['provisionalQuantity'])
            : 0;
    }

    public static function computeCumulative($details, $initialPayment)
    {
        return ($details['finalTotal'] > 0)
            ? ($details['finalTotal'] + $details['provisionalTotal'] + $initialPayment)
            : (($details['provisionalTotal'] > 0) ? ($details['provisionalTotal'] + $initialPayment) : $initialPayment);
    }

    public static function computeFinal($details, $initialPayment)
    {
        return ($details['finalTotal'] > 0)
            ? ($details['finalTotal'] + $details['provisionalTotal'] + $initialPayment)
            : (($details['provisionalTotal'] > 0) ? ($details['provisionalPrice'] * $details['provisionalQuantity']) : $initialPayment);
    }
}
