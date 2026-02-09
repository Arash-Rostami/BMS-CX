<?php

namespace App\Services;

use App\Models\ProformaInvoice;
use App\Models\SupplierSummary;
use Illuminate\Support\Collection;


class SupplierSummaryService
{

    public function calculateExpectedPayments(ProformaInvoice $proforma): array
    {
        $expected = [];

        // If no orders, sum all advance payment requests directly
        if ($proforma->orders->isEmpty()) {
            return $this->sumUpAdvances($proforma, $expected);
        }

        // Otherwise, calculate based on each order
        foreach ($proforma->orders->whereIn('order_status', ['closed', 'accounting_approved']) as $order) {
            $bl = $order->doc?->BL_date ?? null;
            if (!empty($bl) && $order->orderDetail) {
                $expected = $this->sumUpBalances($order->orderDetail, $expected, $order);
            }
        }

        return $expected;
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

    /**
     * Calculate paid payments by currency for a ProformaInvoice.
     *
     */
    public function calculatePaidPayments(ProformaInvoice $proforma, $includeNonFinalized = false): array
    {
        $totals = [];

        // Pre-calculate values to avoid repeated calculations
        $proformaWeight = $this->getProformaWeight($proforma);
        $nonMatchingAmount = $this->calculateNonMatchingAmount($proforma);

        // Process advance payments
        $this->processProformaPayments($proforma, $totals, $proformaWeight, $nonMatchingAmount);
        // Process order payments
        $this->processOrderPayments($proforma, $totals, $includeNonFinalized);

        return $totals;
    }

    /**
     * Completely rebuild all SupplierSummary rows for one ProformaInvoice.
     *
     */
    public function rebuild(int $proformaId): void
    {
        /** @var ProformaInvoice $proforma */
        $proforma = $this->getProformasWithRelations()->findOrFail($proformaId);

        $expected = $this->calculateExpectedPayments($proforma);
        $paid = $this->calculatePaidPayments($proforma);

        $rows = [];
        $currencies = array_unique(array_merge(array_keys($expected), array_keys($paid)));
        foreach ($currencies as $currency) {
            $e = $expected[$currency] ?? 0.0;
            $p = $paid    [$currency] ?? 0.0;
            $v = $p - $e;
            $d = ($currency !== 'Rial' && $e == 0) ? ($v - $paid) : $v;

            $status = $d > 0
                ? 'Overpaid'
                : ($d < 0 ? 'Underpaid' : 'Settled');

            $rows[] = [
                'proforma_invoice_id' => $proforma->id,
                'supplier_id' => $proforma->supplier_id,
                'contract_number' => $proforma->contract_number ?? $proforma->proforma_number,
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

        $this->syncSummaries([$proforma->id], $rows);
    }

    /**
     * NEW: Rebuild summaries for multiple ProformaInvoices in a single batch.
     */
    public function rebuildMany(array $proformaIds): void
    {
        if (empty($proformaIds)) return;


        /** @var Collection|ProformaInvoice[] $proformas */
        $proformas = $this->getProformasWithRelations()->find($proformaIds);

        $allRows = [];
        $now = now();

        foreach ($proformas as $pi) {
            $expected = $this->calculateExpectedPayments($pi);
            $paid = $this->calculatePaidPayments($pi);

            $currencies = array_unique(array_merge(array_keys($expected), array_keys($paid)));

            foreach ($currencies as $currency) {
                $e = $expected[$currency] ?? 0.0;
                $p = $paid[$currency] ?? 0.0;
                $d = $p - $e;
                $status = $d > 0
                    ? 'Overpaid'
                    : ($d < 0 ? 'Underpaid' : 'Settled');

                $allRows[] = [
                    'proforma_invoice_id' => $pi->id,
                    'supplier_id' => $pi->supplier_id,
                    'contract_number' => $pi->contract_number ?? $pi->proforma_number,
                    'type' => 'proforma',
                    'currency' => $currency,
                    'paid' => round($p, 2),
                    'expected' => round($e, 2),
                    'diff' => round($d, 2),
                    'status' => $status,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $this->syncSummaries($proformaIds, $allRows);
    }

    protected function getAdjustmentFactor($proforma): mixed
    {
        $totalProformaQuantity = (float)$proforma->quantity;

        if ($totalProformaQuantity <= 0) {
            return 1.0;
        }

        $closedOrders = $proforma->orders
            ->whereIn('order_status', ['accounting_approved', 'closed']);

        $getOrderQuantity = fn($order) => $order->logistic?->net_weight
            ?? $order->orderDetail?->final_quantity
            ?? $order->orderDetail?->provisional_quantity
            ?? $order->orderDetail?->buying_quantity
            ?? 0;

        $isLastOrder = fn($order) => (is_string($order->orderDetail?->extra)
            ? json_decode($order->orderDetail->extra, true)
            : $order->orderDetail?->extra)['lastOrder'] ?? false;

        list($lastOrders, $regularOrders) = $closedOrders->partition($isLastOrder);

        $closedOrderQuantity = $regularOrders->sum($getOrderQuantity);

        if ($lastOrders->isNotEmpty()) {
            $sumOfLastOrders = $lastOrders->sum($getOrderQuantity);
            $remainder = $totalProformaQuantity - ($closedOrderQuantity + $sumOfLastOrders);
            $closedOrderQuantity += $sumOfLastOrders + $remainder;
        }

        return $closedOrderQuantity > 0
            ? min(1.0, $closedOrderQuantity / $totalProformaQuantity)
            : 1.0;
    }


    protected function getProformaWeight(ProformaInvoice $proforma): int|float
    {
        return $proforma->price * $proforma->quantity * ($proforma->percentage / 100);
    }


    protected function processOrderPayments($proforma, array &$totals, bool $includeNonFinalized): void
    {
        $orders = $proforma->orders
            ->when(!$includeNonFinalized, fn($col) => $col->whereIn('order_status', ['closed', 'accounting_approved'])
                ->filter(fn($order) => !empty($order->doc?->BL_date))
            );

        foreach ($orders as $order) {
            foreach ($order->paymentRequests->where('status', 'completed') as $request) {
                foreach ($request->payments->whereNull('deleted_at') as $payment) {
                    $currency = $payment->currency;
                    $totals[$currency] = ($totals[$currency] ?? 0) + $payment->amount;
                }
            }
        }
    }

    protected function processProformaPayments($proforma, array &$totals, float $proformaWeight, float &$nonMatchingAmount): void
    {
        $adjustmentFactor = $this->getAdjustmentFactor($proforma);

        if ($adjustmentFactor <= 0) {
            return;
        }

        foreach ($proforma->associatedPaymentRequests->where('status', 'completed') as $request) {
            foreach ($request->payments->whereNull('deleted_at') as $payment) {
                // Skip if payment is for different supplier
                $firstRequest = $payment->paymentRequests->first();
                if ($firstRequest?->supplier_id !== $proforma->supplier_id) {
                    continue;
                }

                $currency = $payment->currency;
                $amount = $payment->amount;

                if ($payment->paymentRequests->contains(fn($pr) => $pr->associatedProformaInvoices->count() > 1)) {
                    $totalSum = $payment->paymentRequests->sum('requested_amount');
                    if ($totalSum > 0) {
                        $amount = $payment->amount * ($proformaWeight / $totalSum);
                    }
                }

                $amount *= $adjustmentFactor;

                // Handle non-matching amounts
                if ($nonMatchingAmount > 0) {
                    $amount -= $nonMatchingAmount;
                    $nonMatchingAmount = 0;
                }

                $totals[$currency] = ($totals[$currency] ?? 0) + $amount;
            }
        }
    }

    protected function sumUpAdvances(ProformaInvoice $proforma, array $expected): array
    {
        $proformaWeight = $this->getProformaWeight($proforma);
        $allRequests = ($proforma->associatedPaymentRequests ?? collect([]))
            ->where('status', 'completed')->whereNull('deleted_at');

        //  Sum requested_amount of those shared across >1 proforma
        $sharedRequests = $allRequests->filter(fn($r) => $r->associatedProformaInvoices->count() > 1);
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

    private function getProformasWithRelations()
    {
        return ProformaInvoice::with([
            'orders' => fn($q) => $q->whereNull('deleted_at'),
            'orders.orderDetail',
            'orders.paymentRequests' => fn($q) => $q->whereNull('deleted_at'),
            'orders.paymentRequests.payments' => fn($q) => $q->whereNull('deleted_at'),
            'orders.paymentRequests.payments.paymentRequests' => fn($q) => $q->whereNull('deleted_at')->withCount('associatedProformaInvoices'),
            'associatedPaymentRequests' => fn($q) => $q->whereNull('deleted_at')->withCount('associatedProformaInvoices'),
            'associatedPaymentRequests.payments' => fn($q) => $q->whereNull('deleted_at'),
            'associatedPaymentRequests.payments.paymentRequests' => fn($q) => $q->whereNull('deleted_at')->withCount('associatedProformaInvoices'),
        ]);
    }

    private function syncSummaries(array $proformaIds, array $rowsToInsert): void
    {
        if (empty($proformaIds)) {
            return;
        }

        SupplierSummary::whereIn('proforma_invoice_id', $proformaIds)
            ->where('type', 'proforma')
            ->delete();

        if (!empty($rowsToInsert)) {
            SupplierSummary::insert($rowsToInsert);
        }
    }
}
