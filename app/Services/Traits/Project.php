<?php

namespace App\Services\Traits;

use App\Models\ProformaInvoice;

trait Project
{
    protected static function processOrders($record, $get, $details, $set)
    {
        $invoiceNumber = $record->invoice_number ?? null;

//       Getting total records of all Orders for this project optimally instead of => $orders = Order::findByProjectNumber($invoiceNumber);
        $orders = $record->proformaInvoice->activeOrders;
        if ($orders->isEmpty()) {
            self::addError("Please first save the record before attempting calculations. Make sure order(s) exist.");
            return;
        }
        $details = self::computeTotalQuantityAndCheckLastOrder($orders, $record, $details);


//       Getting total records of all Pre-Payment Requests optimally instead of => $paymentRequests = PaymentRequest::fetchPaymentDetails($record->proforma_number);
        $paymentRequests = $record->proformaInvoice->activeApprovedPaymentRequests;
        if ($paymentRequests->isEmpty()) {
            self::addError("No allowed or approved payment requests found for proforma number: {$record->proforma_number}. Please ensure the record exists without pending status before attempting further calculations.");
            return;
        }
        $details = self::computePaymentRequestDetails($paymentRequests, $details);


//       Getting total records of Pro forma Invoices sharing similar payment requests optimally instead of => $proformaInvoices = ProformaInvoice::fetchApprovedProformas($details['allProformaNumber']);
        $proformaInvoices = ProformaInvoice::fetchActiveApprovedProformas($paymentRequests);
        if ($proformaInvoices->isEmpty()) {
            self::addError("No approved proforma invoices found for proforma number: {$record->proforma_number}. Please check the status or existence of these invoices before proceeding.");
            return;
        }
        list($totalProforma, $details['initialProjectsPayment']) = self::computeTotalProformaInvoices($proformaInvoices, $record);


        // Getting associated Pro forma Invoice for finding prepayment's share / total
        $associatedProformaInvoice = self::computeAssociatedProformaInvoiceDetails($record);
        if ($associatedProformaInvoice == null || $associatedProformaInvoice == 0) {
            self::addError("Proforma invoice data is incomplete. Please check the record details and try again.");
            return;
        }

        $details['initialQuantity'] = (float)$record->proformaInvoice?->quantity;
        if ($details['initialQuantity'] == 0) {
            self::addError("Initial quantity is missing. Please ensure value is set for calculation.");
            return;
        }
        if ($details['provisionalQuantity'] == 0 || $details['provisionalPrice'] == 0) {
            self::addError("Provisional quantity or price is missing. Please ensure value is set for calculation.");
            return;
        }

        $details = self::computeAdjustedRatio($details, $associatedProformaInvoice, $totalProforma);
        if (!isset($details['pricePerUnit']) || $details['pricePerUnit'] <= 0 && !$details['hasUsedUpPrepayment']) {
            self::addError("Calculation Error: Price per unit is zero or negative. Check the initial payment and quantity values.");
            return;
        }
        if ($details['availableQuantity'] < 0) {
            self::addError("Calculation Error: Available quantity computed as negative. Review initial and total quantities.");
            return;
        }
        if ($details['payableQuantity'] < 0) {
            self::addError("Calculation Error: Payable quantity computed as negative. This should not happen.");
            return;
        }

        $details['initialPaymentForOrder'] = self::calculateInitialPaymentForOrder($details);
        $details['provisionalTotal'] = self::computeProvisionalTotal($details, $details['initialPaymentForOrder']);
        $details['finalTotal'] = self::computeFinalTotal($details);
        $details['cumulative'] = self::computeCumulative($details, $details['initialPaymentForOrder']);
        $details['total'] = self::computeFinal($details, $details['initialPaymentForOrder']);
        $details['remaining'] = $details['total'] - $details['cumulative'];

        $keys = [
            'initialPaymentForOrder' => 'initial_payment',
            'initialPayment' => 'initial_total',
            'provisionalTotal' => 'provisional_total',
            'finalTotal' => 'final_total',
            'cumulative' => 'payment',
            'remaining' => 'remaining',
            'total' => 'total',
            'availableQuantity' => 'payable_quantity'
        ];

        self::setOrderDetails($set, $details, $keys);
    }

    private static function setOrderDetails($set, $details, $keys)
    {
        foreach ($keys as $detailKey => $setKey) {
            if (isset($details[$detailKey])) {
                $set("orderDetail.$setKey", sprintf("%.2f", $details[$detailKey]));
            }
        }
        // list($details['initialCombined'], $details['provisionalCombined'], $details['finalCombined'], $details['quantityCombined'])= self::computeCombinedTotals($orders);
    }
}
