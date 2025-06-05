<?php

namespace App\Livewire\CaseSummary;

use App\Models\ProformaInvoice;
use App\Services\SupplierSummaryService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\SupplierSummary as Adjustment;

class SupplierSummary extends Component
{
    public int $supplierId;
    public mixed $proformasForSupplier;
    public float $supplierBalance = 0;
    public mixed $supplierPaymentSummaryTable;
    public int $currentPage = 1;
    public int $perPage = 15;
    public array $currencyDiffBalances = [];

    protected $listeners = ['refreshSupplierSummary' => 'refreshData'];

    private ?array $cachedPaymentData = null;
    private SupplierSummaryService $supplierSummaryService;

    public function boot(SupplierSummaryService $supplierSummaryService)
    {
        $this->supplierSummaryService = $supplierSummaryService;
    }

    public function mount(?int $supplierId = null)
    {
        $this->supplierId = $supplierId;
        $this->refreshData();
    }

    public function loadSupplierData()
    {
        $this->proformasForSupplier = ProformaInvoice::where('supplier_id', $this->supplierId)
            ->with([
                'supplier',
                'associatedPaymentRequests.payments.paymentRequests',
                'orders',
                'orders.paymentRequests' => function ($query) {
                    $query->where('supplier_id', $this->supplierId);
                },
                'orders.paymentRequests.payments',
                'orders.orderDetail',
                'orders.logistic',
                'orders.doc',
            ])
            ->get();

        $this->cachedPaymentData = null;
    }

    private function getSupplierAdjustments()
    {
        return Adjustment::where('supplier_id', $this->supplierId)
            ->where('type', 'adjustment')
            ->whereNull('proforma_invoice_id')
            ->get();
    }

    /**
     * Calculate supplier balance with cached payment data
     */
    #[Computed]
    public function calculateSupplierBalance()
    {
        $paymentData = $this->getOrBuildPaymentData();
        $adjustments = $this->getSupplierAdjustments();


        $totalPaid = collect($paymentData)
            ->sum(fn($data) => array_sum($data['paid']));

        $totalExpected = collect($paymentData)
            ->sum(fn($data) => array_sum($data['expected']));

        $totalAdjustments = $adjustments->sum('diff');
        $this->supplierBalance = $totalPaid - $totalExpected + $totalAdjustments;
    }

    /**
     * Generate payment summary table with optimized processing
     */
    #[Computed]
    public function generateSupplierPaymentSummaryTable()
    {
        $paymentData = $this->getOrBuildPaymentData();
        $adjustments = $this->getSupplierAdjustments();

        $paymentSummaryTable = [];
        $currencyDiffBalances = [];


        list($currencyDiffBalances, $paymentSummaryTable) =
            $this->buildProformaRows($paymentData, $currencyDiffBalances, $paymentSummaryTable);

        list($currencyDiffBalances, $paymentSummaryTable) =
            $this->buildAdjustmentRows($adjustments, $currencyDiffBalances, $paymentSummaryTable);


        $this->supplierPaymentSummaryTable = array_reverse($paymentSummaryTable);
        $this->currencyDiffBalances = $currencyDiffBalances;
    }

    /**
     * Get or build cached payment data for all proformas
     */
    private function getOrBuildPaymentData(): array
    {
        if ($this->cachedPaymentData !== null) {
            return $this->cachedPaymentData;
        }

        $this->cachedPaymentData = [];

        foreach ($this->proformasForSupplier as $proforma) {
            $this->cachedPaymentData[$proforma->id] = [
                'proforma' => $proforma,
                'expected' => $this->supplierSummaryService->calculateExpectedPayments($proforma),
                'paid' => $this->supplierSummaryService->calculatePaidPayments($proforma),
            ];
        }

        return $this->cachedPaymentData;
    }

    protected function buildAdjustmentRows($adjustments, array $currencyDiffBalances, array $paymentSummaryTable): array
    {
        foreach ($adjustments as $adjustment) {
            $currency = $adjustment->currency;
            $diff = $adjustment->diff;
            $paid = $adjustment->paid;
            $expected = $adjustment->expected;

            // Update currency balances with adjustment
            $currencyDiffBalances[$currency]['adjusted'] =
                ($currencyDiffBalances[$currency]['adjusted'] ?? 0) + $diff;
            $currencyDiffBalances[$currency]['total'] =
                ($currencyDiffBalances[$currency]['total'] ?? 0) + $diff;

            $paymentSummaryTable[] = [
                'id' => $adjustment->id,
                'type' => 'adjustment',
                'proforma' => null,
                'incompleteOrder' => collect(),
                'contract_number' => $adjustment->contract_number ?? 'ADJUSTMENT-' . $adjustment->id,
                'reference_number' => 'ADJ-' . $adjustment->id,
                'proforma_number' => 'N/A',
                'paid_amount' => number_format($paid, 2),
                'paid_currency' => $currency,
                'expected_amount' => number_format($expected, 2),
                'diff' => number_format($diff, 2),
                'diff_status' => $this->getDiffStatus($diff),
                'adjustment_record' => $adjustment,
            ];
        }
        return array($currencyDiffBalances, $paymentSummaryTable);
    }


    protected function buildProformaRows(array $paymentData, array $currencyDiffBalances, array $paymentSummaryTable): array
    {
        foreach ($paymentData as $proformaId => $data) {
            $proforma = $data['proforma'];
            $currencies = array_unique(array_merge(array_keys($data['expected']), array_keys($data['paid'])));
            $incompleteRequests = $proforma->orders
                ->flatMap(fn($order) => $order->paymentRequests->where('status', '!=', 'completed'))
                ->values();

            foreach ($currencies as $currency) {
                $expected = $data['expected'][$currency] ?? 0;
                $paid = $data['paid'][$currency] ?? 0;
                $diff = $paid - $expected;

                // Calculate adjusted diff for non-Rial currencies
                $adjustedDiff = ($currency !== 'Rial' && $expected == 0)
                    ? ($diff - $paid)
                    : $diff;

                // Update currency balances
                $currencyDiffBalances[$currency]['adjusted'] =
                    ($currencyDiffBalances[$currency]['adjusted'] ?? 0) + $adjustedDiff;
                $currencyDiffBalances[$currency]['total'] =
                    ($currencyDiffBalances[$currency]['total'] ?? 0) + $diff;

                $paymentSummaryTable[] = [
                    'id' => $proforma->id,
                    'type' => 'proforma',
                    'proforma' => $proforma,
                    'incompleteOrder' => $incompleteRequests,
                    'contract_number' => $proforma->contract_number ?? $proforma->proforma_number,
                    'reference_number' => $proforma->reference_number ?? 'N/A',
                    'proforma_number' => $proforma->proforma_number ?? 'N/A',
                    'paid_amount' => number_format($paid, 2),
                    'paid_currency' => $currency,
                    'expected_amount' => number_format($expected, 2),
                    'diff' => number_format($diff, 2),
                    'diff_status' => $this->getDiffStatus($diff),
                ];
            }
        }
        return array($currencyDiffBalances, $paymentSummaryTable);
    }

    private function getDiffStatus(float $diff): string
    {
        return match (true) {
            $diff > 0 => 'Overpaid',
            $diff < 0 => 'Underpaid',
            default => 'Settled'
        };
    }

    public function refreshData()
    {
        if (!$this->supplierId) return;

        $this->cachedPaymentData = null;
        $this->loadSupplierData();
        $this->calculateSupplierBalance();
        $this->generateSupplierPaymentSummaryTable();
        $this->currentPage = 1;
    }

    public function nextPage()
    {
        $totalPages = $this->totalPages();
        if ($this->currentPage < $totalPages) {
            $this->currentPage++;
        }
    }

    public function previousPage()
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function totalPages(): int
    {
        $totalItems = is_array($this->supplierPaymentSummaryTable)
            ? count($this->supplierPaymentSummaryTable)
            : 0;

        return max(1, (int)ceil($totalItems / $this->perPage));
    }

    public function render()
    {
        $totalItems = is_array($this->supplierPaymentSummaryTable)
            ? count($this->supplierPaymentSummaryTable)
            : 0;

        $totalPages = $this->totalPages();

        $this->currentPage = max(1, min($this->currentPage, $totalPages));

        $paginatedData = $totalItems > 0
            ? collect($this->supplierPaymentSummaryTable)
                ->slice(($this->currentPage - 1) * $this->perPage, $this->perPage)
                ->values()
            : collect();


        return view('livewire.case-summary.supplier-summary', [
            'paginatedData' => $paginatedData,
            'totalPages' => $totalPages,
            'currentPage' => $this->currentPage,
            'totalItems' => $totalItems,
        ]);
    }

    public function index()
    {
        return view('components.Summary.main');
    }
}
