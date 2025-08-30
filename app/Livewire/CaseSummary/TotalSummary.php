<?php

namespace App\Livewire\CaseSummary;

use App\Models\Name;
use App\Models\ProformaInvoice;
use Livewire\Component;


class TotalSummary extends Component
{
    public string $search = '';
    public mixed $selectedProforma = null;
    public mixed $proformaOptions = [];
    public mixed $proformaAttachmentNames;
    public mixed $orderAttachmentNames;
    public mixed $businessInsights;

    public bool $loadFinancialSummary = false;
    public bool $loadSupplierSummary = false;
    private array $cachedTotals = [];
    private array $cachedMetrics = [];


    public function index()
    {
        return view('components.Summary.main');
    }

    public function loadFinancialTab()
    {
        $this->loadFinancialSummary = true;
    }

    public function loadSupplierTab()
    {
        $this->loadSupplierSummary = true;
    }

    public function render()
    {
        return view('livewire.case-summary.total-summary');
    }

    public function resetData()
    {
        $this->resetLoadingFlags();

        $this->search = '';
        $this->selectedProforma = null;
        $this->proformaOptions = [];
        $this->businessInsights = null;
        $this->cachedTotals = [];
        $this->cachedMetrics = [];
        $this->dispatch('refreshSupplierSummary');
        $this->dispatch('$refresh');
        $this->dispatch('clear-selection');
    }

    public function resetLoadingFlags()
    {
        $this->loadFinancialSummary = false;
        $this->loadSupplierSummary = false;
        $this->dispatch('reset-active-tab');
    }

    public function selectProforma($id)
    {
        $this->resetLoadingFlags();
        $this->cachedTotals = [];
        $this->cachedMetrics = [];

        $this->selectedProforma = $this->fetchProformaInvoice($id);

        // Assign attachment names to component properties.
        $attachmentNames = $this->fetchAttachmentNames();
        $this->proformaAttachmentNames = $attachmentNames['ProformaInvoice']?->pluck('title') ?? collect();
        $this->orderAttachmentNames = $attachmentNames['Order']?->pluck('title') ?? collect();

        // Assemble the business insights.
        $this->businessInsights = $this->calculateAllMetrics();

        // Update the search input with the selected proforma number.
        $this->search = $this->buildSearchLabelled();
        $this->proformaOptions = [];
    }

    public function updatedSearch()
    {
        if (strlen($this->search) === 0) {
            $this->resetData();
        } else {
            $this->proformaOptions = (strlen($this->search) >= 3)
                ? $this->fetchProformaOptions($this->search)
                : [];
        }
    }

    public function verifyProforma()
    {
        if ($this->selectedProforma) {
            $this->selectedProforma->update([
                'verified' => true,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
            $this->selectedProforma = $this->selectedProforma->fresh();
        }
    }

    private function buildSearchLabelled(): string
    {
        $parts = [];

        if (!empty($this->selectedProforma->contract_number)) {
            $parts[] = 'Contract No: ' . $this->selectedProforma->contract_number ?? 'N/A';
        }

        if (!empty($this->selectedProforma->proforma_number)) {
            $parts[] = 'PI No: ' . $this->selectedProforma->proforma_number ?? 'N/A';
        }

        return implode(' ↔️ ', $parts);
    }

    private function calculateAllMetrics(): object
    {
        if (!$this->selectedProforma) return (object)[];

        $totalPaymentRequests = $this->calculateTotalPaymentRequests();
        $totalPayments = $this->calculateTotalPayments();

        [$totalInitialPayment, $totalProvisionalTotal, $totalFinalTotal, $orderTotal] =
            $this->calculateOrderTotals();

        $expectedPaymentByCurrency = $this->calculateExpectedPaymentByCurrency();
        $quantityComparison = $this->calculateTotalOrderQuantities();
        $prepaymentAndRemaining = $this->calculatePrepaymentAndRemainingAmount();

        return (object)[
            'total_payment_requests_paid' => $this->formatCurrencyTotals($totalPaymentRequests),
            'total_payments_paid' => $this->formatCurrencyTotals($totalPayments),
            'payment_discrepancies' => $this->calculateDiscrepancies($totalPaymentRequests, $totalPayments),
            'total_count' => $this->calculateTotalCounts(),
            'total_initial_payment' => $totalInitialPayment,
            'total_provisional_total' => $totalProvisionalTotal,
            'total_final_total' => $totalFinalTotal,
            'aggregate_total' => $orderTotal,
            'prepayment' => $totalInitialPayment,
            'contractual_prepayment' => $prepaymentAndRemaining['prepayment'],
            'contractual_remaining_amount' => $prepaymentAndRemaining['remaining_amount'],
            'days_elapsed' => $this->selectedProforma->proforma_date?->diffInDays(now()),
            'gap_bl_proforma' => $this->calculateGaps()[0],
            'gap_declaration_proforma' => $this->calculateGaps()[1],
            'expected_payment_by_currency' => $expectedPaymentByCurrency,
            'total_payments_made_by_currency' => $totalPayments,
            'payment_status_by_currency' => $this->calculatePaymentStatus($expectedPaymentByCurrency, $totalPayments),
            'quantity_comparison' => $quantityComparison,
            'progress_by_quantity' => $this->calculateProgressByQuantity(),
            'progress_by_payment' => $this->calculateProgressByPayment($totalPayments),
        ];
    }

    private function calculateDiscrepancies(array $requestedTotals, array $paidTotals): array
    {
        $discrepancies = [];
        foreach ($requestedTotals as $currency => $requestedAmount) {
            $paidAmount = $paidTotals[$currency] ?? 0;
            $difference = $paidAmount - $requestedAmount;
            if ($difference !== 0) {
                $discrepancies[$currency] = [
                    'requested_amount' => $requestedAmount,
                    'paid_amount' => $paidAmount,
                    'difference' => $difference,
                ];
            }
        }
        return $discrepancies;
    }

    private function calculateExpectedPaymentByCurrency(): array
    {
        if (isset($this->cachedMetrics['expected_payments'])) {
            return $this->cachedMetrics['expected_payments'];
        }

        $expectedPayments = [];

        foreach ($this->selectedProforma->orders as $order) {
            if (!$order->orderDetail) continue;

            $currency = $order->orderDetail->currency ?? 'USD';
            $initialPayment = $order->orderDetail->initial_payment ?? 0;
            $provisionalTotal = $order->orderDetail->provisional_total ?? 0;
            $finalTotal = $order->orderDetail->final_total ?? 0;
            $amount = $initialPayment + $provisionalTotal + $finalTotal;

            if ($amount == 0) {
                continue;
            }

            $expectedPayments[$currency] = ($expectedPayments[$currency] ?? 0) + $amount;
        }

        return $this->cachedMetrics['expected_payments'] = $expectedPayments;
    }


    private function calculateGaps(): array
    {
        $gapBl = $gapDeclaration = [];

        foreach ($this->selectedProforma->orders->sortBy('part') as $order) {
            if ($order->doc) {
                $part = $order->part;
                if ($order->doc->BL_date) {
                    $blGap = $order->doc->BL_date->diffInDays($this->selectedProforma->proforma_date);
                    $gapBl[] = "Part $part: $blGap Days";
                }
                if ($order->doc->declaration_date) {
                    $declarationGap = $order->doc->declaration_date->diffInDays($this->selectedProforma->proforma_date);
                    $gapDeclaration[] = "Part $part: $declarationGap Days";
                }
            }
        }

        return [implode('<br>', $gapBl), implode('<br>', $gapDeclaration)];
    }

    private function calculateOrderTotals(): array
    {
        if (isset($this->cachedMetrics['order_totals'])) {
            return $this->cachedMetrics['order_totals'];
        }

        $totalInitialPayment = $totalProvisionalTotal = $totalFinalTotal = 0;

        // Total initial payment is calculated from associated payment requests (excluding Rial).
        foreach ($this->selectedProforma->orders as $order) {
            if ($order->orderDetail) {
                $totalInitialPayment += $order->orderDetail->initial_payment ?? 0;
                $totalProvisionalTotal += $order->orderDetail->provisional_total ?? 0;
                $totalFinalTotal += $order->orderDetail->final_total ?? 0;
            }
        }

        $orderTotal = $totalInitialPayment + $totalProvisionalTotal + $totalFinalTotal;

        return $this->cachedMetrics['order_totals'] = [
            $totalInitialPayment, $totalProvisionalTotal, $totalFinalTotal, $orderTotal
        ];
    }

    private function calculatePaymentStatus(array $expectedPayments, array $paidPayments): array
    {
        $status = [];
        foreach ($expectedPayments as $currency => $expectedAmount) {
            $paidAmount = $paidPayments[$currency] ?? 0;
            $difference = $paidAmount - $expectedAmount;
            $status[$currency] = [
                'expected_amount' => $expectedAmount,
                'paid_amount' => $paidAmount,
                'status' => $difference > 0
                    ? 'Overpaid ' . number_format($difference, 2)
                    : ($difference < 0
                        ? 'Underpaid ' . number_format(abs($difference), 2)
                        : 'Settled'),
            ];
        }
        return $status;
    }

    private function calculatePrepaymentAndRemainingAmount(): array
    {
        $contractValue = $this->selectedProforma->price * $this->selectedProforma->quantity;
        $prepaymentPercentage = $this->selectedProforma->percentage ?
            ($this->selectedProforma->percentage / 100) : 0.1;
        $prepayment = $contractValue * $prepaymentPercentage;

        return [
            'contract_value' => $contractValue,
            'prepayment' => $prepayment,
            'remaining_amount' => $contractValue - $prepayment,
        ];
    }

    private function calculateProgressByPayment(array $totalPaymentsMadeByCurrency): float
    {
        $contractValue = $this->selectedProforma->price * $this->selectedProforma->quantity;
        $totalPaymentsMade = $totalPaymentsMadeByCurrency['USD'] ?? 0;

        return $contractValue > 0 ? ($totalPaymentsMade / $contractValue) * 100 : 0;
    }


    private function calculateProgressByQuantity(): float
    {
        $proformaQuantity = $this->selectedProforma->quantity;
        $shippedQuantity = 0;

        foreach ($this->selectedProforma->orders as $order) {
            if ($order->logistic) {
                $shippedQuantity += $order->logistic->net_weight ?? 0;
            }
        }

        return $proformaQuantity > 0 ? ($shippedQuantity / $proformaQuantity) * 100 : 0;
    }

    private function calculateTotalCounts(): string
    {
        $ordersCount = $this->selectedProforma->orders->count();

        $paymentRequestsCount = $this->selectedProforma->associatedPaymentRequests->count() +
            $this->selectedProforma->orders->sum(fn($order) => $order->paymentRequests->count());

        $paymentsCount = $this->selectedProforma->associatedPaymentRequests->sum(fn($pr) => $pr->payments->count()) +
            $this->selectedProforma->orders->sum(fn($order) => $order->paymentRequests->sum(fn($pr) => $pr->payments->count()));

        return "Order: $ordersCount<br>Pay. Req.: $paymentRequestsCount<br>Payment: $paymentsCount";
    }

    private function calculateTotalOrderQuantities(): array
    {
        $totalQuantity = 0;
        $netQuantity = 0;

        foreach ($this->selectedProforma->orders as $order) {
            if ($order->orderDetail) {
                $totalQuantity += $order->orderDetail->final_quantity
                    ?? $order->orderDetail->provisional_quantity
                    ?? $order->orderDetail->buying_quantity
                    ?? 0;
            }

            if ($order->logistic && $order->logistic->net_weight !== null) {
                $netQuantity += $order->logistic->net_weight;
            }
        }

        $proformaQuantity = $this->selectedProforma->quantity ?? 0;
        $valueToCompare = $netQuantity > 0 ? $netQuantity : $totalQuantity;
        $basis = $netQuantity > 0 ? 'net weight' : 'total quantity';


        return [
            'proforma_quantity' => $proformaQuantity,
            'total_order_quantity' => $totalQuantity,
            'total_net_weight' => $netQuantity,
            'status' => $this->compareQuantities($proformaQuantity, $valueToCompare, $basis),
        ];
    }

    private function calculateTotalPaymentRequests(): array
    {
        if (isset($this->cachedTotals['requests'])) {
            return $this->cachedTotals['requests'];
        }

        $totals = [];
        $proformaWeight = $this->selectedProforma->price * $this->selectedProforma->quantity *
            ($this->selectedProforma->percentage / 100);

        // From associated payment requests.
        foreach ($this->selectedProforma->associatedPaymentRequests as $pr) {
            $amount = $pr->requested_amount;

            if ($pr->associatedProformaInvoices->count() > 1) {
                $totalWeight = $pr->associatedProformaInvoices->sum(fn($p) => $p->price * $p->quantity * ($p->percentage / 100));
                $amount = $totalWeight > 0 ? ($pr->requested_amount * ($proformaWeight / $totalWeight)) : 0;
            }

            $totals[$pr->currency] = ($totals[$pr->currency] ?? 0) + $amount;
        }

        // From orders' payment requests.
        foreach ($this->selectedProforma->orders as $order) {
            foreach ($order->paymentRequests as $pr) {
                $totals[$pr->currency] = ($totals[$pr->currency] ?? 0) + $pr->requested_amount;
            }
        }

        return $this->cachedTotals['requests'] = $totals;
    }


    private function calculateTotalPayments(): array
    {
        if (isset($this->cachedTotals['payments'])) {
            return $this->cachedTotals['payments'];
        }

        $totals = [];
        $proformaWeight = $this->selectedProforma->price * $this->selectedProforma->quantity *
            ($this->selectedProforma->percentage / 100);

        // From associated payment requests' payments.
        foreach ($this->selectedProforma->associatedPaymentRequests as $req) {
            $isCollective = $req->associatedProformaInvoices->count() > 1;

            foreach ($req->payments as $payment) {
                $amount = $payment->amount;

                if ($isCollective) {
                    $totalSum = $payment->paymentRequests->sum('requested_amount');
                    $amount = $totalSum > 0 ? ($payment->amount * ($proformaWeight / $totalSum)) : 0;
                }

                $totals[$payment->currency] = ($totals[$payment->currency] ?? 0) + $amount;
            }
        }

        foreach ($this->selectedProforma->orders as $order) {
            foreach ($order->paymentRequests as $pr) {
                foreach ($pr->payments as $payment) {
                    $totals[$payment->currency] = ($totals[$payment->currency] ?? 0) + $payment->amount;
                }
            }
        }

        return $this->cachedTotals['payments'] = $totals;
    }

    private function compareQuantities(float $proformaQuantity, float $totalOrderQuantity, string $basis): string
    {
        if ($totalOrderQuantity > $proformaQuantity) {
            return sprintf(
                'Over-Ordered by %s MT based on %s',
                number_format($totalOrderQuantity - $proformaQuantity, 2),
                $basis
            );
        } elseif ($totalOrderQuantity < $proformaQuantity) {
            return sprintf(
                'Under-Ordered by %s based on %s',
                number_format($proformaQuantity - $totalOrderQuantity, 2),
                $basis
            );
        }
        return 'Matched exactly based on ' . $basis;
    }

    private function fetchAttachmentNames()
    {
        return Name::whereIn('module', ['ProformaInvoice', 'Order'])
            ->select('module', 'title')
            ->get()
            ->groupBy('module');
    }

    private function fetchProformaInvoice($id)
    {
        return ProformaInvoice::with([
            'attachments',
            'buyer:id,name',
            'supplier:id,name',
            'category:id,name',
            'product:id,name',
            'grade:id,name',
            'associatedPaymentRequests' => fn($query) => $query->whereNull('deleted_at'),
            'associatedPaymentRequests.payments' => fn($query) => $query->whereNull('deleted_at'),
            'associatedPaymentRequests.payments.paymentRequests' => fn($q) => $q->whereNull('deleted_at'),
            'associatedPaymentRequests.associatedProformaInvoices',
            'orders' => fn($query) => $query->whereNull('deleted_at'),
            'orders.orderDetail',
            'orders.logistic',
            'orders.doc',
            'orders.attachments',
            'orders.paymentRequests' => fn($query) => $query->whereNull('deleted_at'),
            'orders.paymentRequests.payments' => fn($query) => $query->whereNull('deleted_at'),
            'orders.paymentRequests.payments.paymentRequests'
        ])
            ->whereNull('deleted_at')
            ->findOrFail($id);
    }

    private function fetchProformaOptions(string $search)
    {
        $search = trim(strtolower($search));

        return ProformaInvoice::select(['id', 'proforma_number', 'contract_number', 'reference_number'])
            ->whereNull('deleted_at')
            ->with(['buyer:id,name', 'supplier:id,name', 'category:id,name', 'product:id,name', 'grade:id,name'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('lower(proforma_number) like ?', ["%{$search}%"])
                        ->orWhereRaw('lower(contract_number) like ?', ["%{$search}%"])
                        ->orWhereRaw('lower(reference_number) like ?', ["%{$search}%"])
                        ->orWhereHas('buyer', fn($q) => $q->whereRaw('lower(name) like ?', ["%{$search}%"]))
                        ->orWhereHas('supplier', fn($q) => $q->whereRaw('lower(name) like ?', ["%{$search}%"]))
                        ->orWhereHas('category', fn($q) => $q->whereRaw('lower(name) like ?', ["%{$search}%"]))
                        ->orWhereHas('product', fn($q) => $q->whereRaw('lower(name) like ?', ["%{$search}%"]))
                        ->orWhereHas('grade', fn($q) => $q->whereRaw('lower(name) like ?', ["%{$search}%"]));
                });
            })
            ->limit(5)
            ->get();
    }

    private function formatCurrencyTotals(array $totals): string
    {
        $formatted = [];
        foreach ($totals as $currency => $amount) {
            $formatted[] = "$currency: " . number_format($amount, 2);
        }
        return implode('<br>', $formatted);
    }
}
