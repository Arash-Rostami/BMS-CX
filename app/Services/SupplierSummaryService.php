<?php

namespace App\Services;

use App\Models\ProformaInvoice;
use App\Models\SupplierSummary;

class SupplierSummaryService
{
    /**
     * Completely rebuild all SupplierSummary rows for one ProformaInvoice.
     *
     */
    public function rebuild(int $proformaId): void
    {
        /** @var ProformaInvoice $pi */
        $pi = ProformaInvoice::with([
            'associatedPaymentRequests.payments.paymentRequests',
            'orders.paymentRequests.payments',
            'orders.orderDetail',
        ])->findOrFail($proformaId);

        $expected = $this->calculateExpectedPayments($pi);
        $paid = $this->calculatePaidPayments($pi);

        $rows = [];
        $currencies = array_unique(array_merge(array_keys($expected), array_keys($paid)));
        foreach ($currencies as $currency) {
            $e = $expected[$currency] ?? 0.0;
            $p = $paid    [$currency] ?? 0.0;
            $d = $p - $e;
            $status = $d > 0
                ? 'Overpaid'
                : ($d < 0 ? 'Underpaid' : 'Settled');

            $rows[] = [
                'proforma_invoice_id' => $pi->id,
                'supplier_id' => $pi->supplier_id,
                'contract_number' => $pi->contract_number ?? $pi->proforma_number,
                'type' => 'proforma',
                'currency' => $currency,
                'paid' => round($p, 2),
                'expected' => round($e, 2),
                'diff' => round($d, 2),
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        SupplierSummary::where('proforma_invoice_id', $pi->id)
            ->where('type', 'proforma')
            ->delete();

        if (!empty($rows)) {
            SupplierSummary::insert($rows);
        }
    }

    /**
     * Calculate expected payments by currency for a proforma
     */
    public function calculateExpectedPayments(ProformaInvoice $proforma): array
    {
        $expected = [];

        // If no orders, sum all advance payment requests directly
        if ($proforma->orders->isEmpty()) {
            return $this->sumUpAdvances($proforma, $expected);
        }

        // Otherwise, calculate based on each order
        foreach ($proforma->orders as $order) {
            $orderDetail = $order->orderDetail;
            if (!$orderDetail) continue;

            $expected = $this->sumUpBalances($orderDetail, $expected, $order);
        }

        return $expected;
    }

    /**
     * Calculate paid payments by currency for a ProformaInvoice.
     *
     */
    public function calculatePaidPayments(ProformaInvoice $proforma): array
    {
        $totals = [];

        // Pre-calculate values to avoid repeated calculations
        $proformaWeight = $this->getProformaWeight($proforma);
        $nonMatchingAmount = $this->calculateNonMatchingAmount($proforma);

        // Process advance payments
        $this->processProformaPayments($proforma, $totals, $proformaWeight, $nonMatchingAmount);

        // Process order payments
        $this->processOrderPayments($proforma, $totals);

        return $totals;
    }

    /**
     * Calculate non-matching requested amount more efficiently
     */
    public function calculateNonMatchingAmount(ProformaInvoice $pi): float
    {
        return (float)$pi->associatedPaymentRequests
            ->where('proforma_invoice_number', '!=', trim($pi->proforma_number))
            ->flatMap->payments
            ->flatMap->paymentRequests
            ->sum('requested_amount');
    }


    protected function sumUpAdvances(ProformaInvoice $proforma, array $expected): array
    {
        $proformaWeight = $this->getProformaWeight($proforma);
        $allRequests = ($proforma->associatedPaymentRequests ?? collect([]))
            ->where('status', 'completed')->whereNull('deleted_at');

        //  Sum requested_amount of those shared across >1 proforma
        $sharedRequests = $allRequests->filter(fn($r) => $r->associatedProformaInvoices?->count() > 1);
        $totalSharedRequested = $sharedRequests->sum('requested_amount');

        foreach ($allRequests as $request) {
            $currency = $request->currency;
            $amt = $request->requested_amount;

            if ($sharedRequests->contains($request) && $totalSharedRequested > 0) {
                $amt = $amt * ($proformaWeight / $totalSharedRequested);
            }

            if ($amt > 0) {
                $expected[$currency] = ($expected[$currency] ?? 0) + $amt;
            }
        }

        return $expected;
    }


    protected function sumUpBalances($orderDetail, array $expected, mixed $order): array
    {
        $currency = $orderDetail->currency ?? 'USD';

        // Calculate total amount using null coalescing and explicit sum
        $amount = ($orderDetail->initial_payment ?? 0) +
            ($orderDetail->provisional_total ?? 0) +
            ($orderDetail->final_total ?? 0);

        // Add to expected payments if amount is positive
        if ($amount > 0) {
            $expected[$currency] = ($expected[$currency] ?? 0) + $amount;
        }

        // Handle payment requests with different currencies
        foreach ($order->paymentRequests as $paymentRequest) {
            if ($paymentRequest->status !== 'completed') {
                continue;
            }
            if ($paymentRequest->currency === $currency) {
                continue;
            }

            $expected[$paymentRequest->currency] = ($expected[$paymentRequest->currency] ?? 0) + $paymentRequest->total_amount;
        }
        return $expected;
    }

    /**
     * Process advance payments with optimizations
     */
    protected function processProformaPayments($proforma, array &$totals, float $proformaWeight, float &$nonMatchingAmount): void
    {
        foreach ($proforma->associatedPaymentRequests as $request) {
            if ($request->status !== 'completed') {
                continue;
            }

            foreach ($request->payments->whereNull('deleted_at') as $payment) {
                // Skip if payment is for different supplier
                $firstRequest = $payment->paymentRequests->first();
                if ($firstRequest?->supplier_id !== $proforma->supplier_id) {
                    continue;
                }


                $currency = $payment->currency;
                $amount = $payment->amount;

                if ($payment->paymentRequests->contains(fn($pr) => $pr->associatedProformaInvoices?->count() > 1)) {
                    $totalSum = $payment->paymentRequests->sum('requested_amount');
                    if ($totalSum > 0) {
                        $amount = $payment->amount * ($proformaWeight / $totalSum);
                    }
                }


                // Handle non-matching amounts
                if ($nonMatchingAmount > 0) {
                    $amount -= $nonMatchingAmount;
                    $nonMatchingAmount = 0;
                }


                $totals[$currency] = ($totals[$currency] ?? 0) + $amount;
            }
        }
    }

    /**
     * Process order payments
     */
    protected function processOrderPayments($proforma, array &$totals): void
    {
        foreach ($proforma->orders as $order) {
            foreach ($order->paymentRequests as $request) {
                if ($request->status !== 'completed') {
                    continue;
                }

                foreach ($request->payments->whereNull('deleted_at') as $payment) {
                    $currency = $payment->currency;
                    $totals[$currency] = ($totals[$currency] ?? 0) + $payment->amount;
                }
            }
        }
    }

    protected function getProformaWeight(ProformaInvoice $proforma): int|float
    {
        return $proforma->price * $proforma->quantity * ($proforma->percentage / 100);
    }
}
