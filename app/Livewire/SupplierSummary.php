<?php

namespace App\Livewire;

use App\Models\ProformaInvoice;
use Livewire\Component;

class SupplierSummary extends Component
{
    public int $supplierId;
    public mixed $proformasForSupplier;
    public float $supplierBalance = 0;
    public mixed $supplierPaymentSummaryTable;
    public array $currencyDiffBalances = [];

    protected $listeners = ['refreshSupplierSummary' => 'refreshData'];


    public function mount(?int $supplierId = null)
    {
        $this->supplierId = $supplierId;
        $this->refreshData();
    }

    /**
     * Load supplier related data and business insights.
     */
    public function loadSupplierData()
    {
        $proformas = ProformaInvoice::where('supplier_id', $this->supplierId)
            ->with([
                'supplier',
                'associatedPaymentRequests.payments',
                'orders.paymentRequests.payments',
                'orders.orderDetail',
                'orders.logistic',
                'orders.doc',
            ])
            ->get();

        $this->proformasForSupplier = $proformas;
    }

    public function calculateSupplierBalance()
    {
        $totalPaidToSupplier = 0;
        $totalExpectedFromSupplier = 0;

        foreach ($this->proformasForSupplier as $proforma) {
            $expectedPayments = $this->calculateExpectedPaymentByCurrencyForSupplier($proforma);
            $paidPayments = $this->calculateTotalPaymentsForSupplier($proforma);

            foreach ($expectedPayments as $currency => $expectedAmount) {
                $paidAmount = $paidPayments[$currency] ?? 0;
                $totalExpectedFromSupplier += $expectedAmount;
                $totalPaidToSupplier += $paidAmount;
            }
        }

        $this->supplierBalance = $totalPaidToSupplier - $totalExpectedFromSupplier;
    }

    public function generateSupplierPaymentSummaryTable()
    {
        $paymentSummaryTable = [];
        $currencyDiffBalances = [];

        foreach ($this->proformasForSupplier as $proforma) {
            $expectedPayments = $this->calculateExpectedPaymentByCurrencyForSupplier($proforma);
            $paidPayments = $this->calculateTotalPaymentsForSupplier($proforma);

            $currencies = array_unique(array_merge(array_keys($expectedPayments), array_keys($paidPayments)));
            $summaryRows = [];

            foreach ($currencies as $currency) {
                $expectedAmount = $expectedPayments[$currency] ?? 0;
                $paidAmount = $paidPayments[$currency] ?? 0;
                $diff = $paidAmount - $expectedAmount;

                $currencyDiffBalances[$currency] = ($currencyDiffBalances[$currency] ?? 0) + $diff; // Combined initialization and update

                $summaryRows[] = [
                    'currency' => $currency,
                    'paid_amount' => number_format($paidAmount, 2),
                    'expected_amount' => number_format($expectedAmount, 2),
                    'diff' => number_format($diff, 2),
                    'diff_status' => $diff > 0 ? 'Overpaid' : ($diff < 0 ? 'Underpaid' : 'Settled'), // Condensed diff_status
                ];
            }

            foreach ($summaryRows as $row) {
                $paymentSummaryTable[] = [
                    'id' => $proforma->id ?? 1,
                    'contract_number' => $proforma->contract_number ?? $proforma->proforma_number,
                    'reference_number' => $proforma->reference_number ?? 'N/A',
                    'paid_amount' => $row['paid_amount'],
                    'paid_currency' => $row['currency'],
                    'expected_amount' => $row['expected_amount'],
                    'diff' => $row['diff'],
                    'diff_status' => $row['diff_status'],
                ];
            }
        }

        $this->supplierPaymentSummaryTable = $paymentSummaryTable;
        $this->currencyDiffBalances = $currencyDiffBalances;
    }

    /**
     * Calculates total paid payment amounts grouped by currency for supplier context.
     */
    private function calculateTotalPaymentsForSupplier($proforma)
    {
        $totals = [];

        $aggregatePayments = function ($payments) use (&$totals, $proforma) {
            $payments->whereNull('deleted_at')->each(function ($payment) use (&$totals, $proforma) {
                $paymentRequest = $payment->paymentRequests->first();
                if ($paymentRequest && $paymentRequest->supplier_id != $proforma->supplier_id) {
                    return;
                }
                $currency = $payment->currency;
                $amount = $payment->amount;
                if (!isset($totals[$currency])) {
                    $totals[$currency] = 0;
                }
                $totals[$currency] += $amount;
            });
        };

        $aggregatePayments($proforma->associatedPaymentRequests->flatMap->payments);
        $aggregatePayments($proforma->orders->flatMap->paymentRequests->flatMap->payments);

        return $totals;
    }


    /**
     * Calculates the expected payment amount by summing the initial payment,
     * provisional total, and final total for each order, grouped by currency for supplier context.
     */
    private function calculateExpectedPaymentByCurrencyForSupplier($proforma): array
    {
        return $proforma->orders()
            ->whereNull('deleted_at')
            ->whereHas('orderDetail')
            ->with(['orderDetail', 'paymentRequests' => function ($query) use ($proforma) {
                $query->where('supplier_id', $proforma->supplier_id);
            }])
            ->get()
            ->reduce(function ($expectedPayments, $order) {
                $orderDetail = $order->orderDetail;
                $currency = $orderDetail->currency ?? 'USD';

                $amount = ($orderDetail->initial_payment ?? 0) + ($orderDetail->provisional_total ?? 0) + ($orderDetail->final_total ?? 0);

                if ($amount > 0) {
                    $expectedPayments[$currency] = ($expectedPayments[$currency] ?? 0) + $amount;
                }

                // Process payment requests with different currencies
                $order->paymentRequests
                    ->where('currency', '!=', $currency)
                    ->each(function ($paymentRequest) use (&$expectedPayments) {
                        $expectedPayments[$paymentRequest->currency] =
                            ($expectedPayments[$paymentRequest->currency] ?? 0) +
                            $paymentRequest->total_amount;
                    });

                return $expectedPayments;
            }, []);
    }

    public function refreshData()
    {
        if ($this->supplierId) {
            $this->loadSupplierData();
            $this->calculateSupplierBalance();
            $this->generateSupplierPaymentSummaryTable();
        }
    }

    public function render()
    {
        return view('livewire.supplier-summary');
    }

    public function index()
    {
        return view('components.Summary.main');
    }
}
