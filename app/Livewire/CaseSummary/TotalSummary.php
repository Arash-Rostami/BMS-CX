<?php

namespace App\Livewire\CaseSummary;

use App\Models\Name;
use App\Models\ProformaInvoice;
use Livewire\Attributes\Computed;
use Livewire\Component;


class TotalSummary extends Component
{
    public string $search = '';
    public mixed $selectedProforma = null;
    public mixed $proformaOptions = [];
    public mixed $proformaAttachmentNames;
    public mixed $orderAttachmentNames;
    public mixed $businessInsights;

    /**
     * When the search property updates, we fetch matching Proforma invoices
     * if the search term is at least 3 characters long.
     */
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

    /**
     * Marks the selected proforma as verified.
     */
    public function verifyProforma()
    {
        if ($this->selectedProforma) {
            $this->selectedProforma->update([
                'verified'    => true,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
            $this->selectedProforma = $this->selectedProforma->fresh();
        }
    }

    /**
     * Fetches Proforma options based on the search term.
     *
     * Example: If $search = "abc", the query will look for any proforma_number,
     * contract_number, etc. containing "abc" (case-insensitive) and return 5 results.
     */
    private function fetchProformaOptions(string $search)
    {
        $search = trim(strtolower($search));

        return ProformaInvoice::query()
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

    /**
     * Called when a proforma is selected.
     * Fetches the detailed proforma data, attachments, and calculates all business insights.
     */
    public function selectProforma($id)
    {
        $attachmentNames = $this->fetchAttachmentNames();

        $this->selectedProforma = $this->fetchProformaInvoice($id);

        $totalPaymentRequests = $this->calculateTotalPaymentRequests($this->selectedProforma);
        $totalPayments = $this->calculateTotalPayments($this->selectedProforma);

        $formattedPaymentRequests = $this->formatCurrencyTotals($totalPaymentRequests);
        $formattedPayments = $this->formatCurrencyTotals($totalPayments);

        $discrepancies = $this->calculateDiscrepancies($totalPaymentRequests, $totalPayments);
        $totalCounts = $this->calculateTotalCounts($this->selectedProforma);

        list($totalInitialPayment, $totalProvisionalTotal, $totalFinalTotal, $prepayment, $orderTotal) =
            $this->calculateOrderTotals($this->selectedProforma);

        $daysElapsed = $this->calculateDaysElapsed($this->selectedProforma);
        list($gapBlProformaText, $gapDeclarationProformaText) = $this->calculateGaps($this->selectedProforma);
        $expectedPaymentByCurrency = $this->calculateExpectedPaymentByCurrency($this->selectedProforma);
        $paymentStatusByCurrency = $this->calculatePaymentStatus($expectedPaymentByCurrency, $totalPayments);

        $quantityComparison = $this->calculateTotalOrderQuantities($this->selectedProforma);
        $prepaymentAndRemaining = $this->calculatePrepaymentAndRemainingAmount($this->selectedProforma);
        $progressByQuantity = $this->calculateProgressByQuantity($this->selectedProforma);
        $progressByPayment = $this->calculateProgressByPayment($this->selectedProforma, $totalPayments);


        // Assemble the business insights.
        $this->businessInsights = (object)[
            'total_payment_requests_paid' => $formattedPaymentRequests,
            'total_payments_paid' => $formattedPayments,
            'payment_discrepancies' => $discrepancies,
            'total_count' => $totalCounts,
            'total_initial_payment' => $totalInitialPayment,
            'total_provisional_total' => $totalProvisionalTotal,
            'total_final_total' => $totalFinalTotal,
            'aggregate_total' => $orderTotal,
            'prepayment' => $prepayment,
            'contractual_prepayment' => $prepaymentAndRemaining['prepayment'],
            'contractual_remaining_amount' => $prepaymentAndRemaining['remaining_amount'],
            'days_elapsed' => $daysElapsed,
            'gap_bl_proforma' => $gapBlProformaText,
            'gap_declaration_proforma' => $gapDeclarationProformaText,
            'expected_payment_by_currency' => $expectedPaymentByCurrency,
            'total_payments_made_by_currency' => $totalPayments,
            'payment_status_by_currency' => $paymentStatusByCurrency,
            'quantity_comparison' => $quantityComparison,
            'progress_by_quantity' => $progressByQuantity,
            'progress_by_payment' => $progressByPayment,
        ];

        // Assign attachment names to component properties.
        $this->proformaAttachmentNames = $attachmentNames->where('module', 'ProformaInvoice')->pluck('title');
        $this->orderAttachmentNames = $attachmentNames->where('module', 'Order')->pluck('title');

        // Update the search input with the selected proforma number.
        $this->search = $this->selectedProforma->proforma_number ?? '';
        $this->proformaOptions = [];
    }

    /**
     * Fetches attachment names from the Name model.
     * Example: Returns a collection with module "ProformaInvoice" and "Order" each having a title.
     */
    private function fetchAttachmentNames()
    {
        return Name::whereIn('module', ['ProformaInvoice', 'Order'])
            ->groupBy('module', 'title')
            ->select('module', 'title')
            ->get();
    }

    /**
     * Retrieves the proforma invoice with all required relationships.
     */
    private function fetchProformaInvoice($id)
    {
        return ProformaInvoice::with([
            'attachments',
            'buyer',
            'supplier',
            'category',
            'product',
            'associatedPaymentRequests.payments.attachments',
            'orders.orderDetail',
            'orders.logistic',
            'orders.doc',
            'orders.paymentRequests.payments.attachments',
            'orders.attachments'
        ])->findOrFail($id);
    }

    /**
     * Calculates total requested payment amounts grouped by currency.
     * Example: If there is a payment request in USD for 100 and another for 150,
     * this method returns ['USD' => 250].
     */
    #[Computed]
    private function calculateTotalPaymentRequests($proforma)
    {
        $totals = [];

        // From associated payment requests.
        $proformaWeight = $proforma->price * $proforma->quantity * ($proforma->percentage / 100);
        $proforma->associatedPaymentRequests->whereNull('deleted_at')->each(function ($pr) use (&$totals, $proformaWeight) {
            $currency = $pr->currency;
            $amount = $pr->requested_amount;

            if ($pr->associatedProformaInvoices?->count() > 1) {
                $totalWeight = $pr->associatedProformaInvoices->sum(function ($p) {
                    return $p->price * $p->quantity * ($p->percentage / 100);
                });
                $amount = $pr->requested_amount * ($proformaWeight / $totalWeight);
            }

            if (!isset($totals[$currency])) {
                $totals[$currency] = 0;
            }
            $totals[$currency] += $amount;
        });

        // From orders' payment requests.
        $proforma->orders->flatMap->paymentRequests->whereNull('deleted_at')->each(function ($pr) use (&$totals) {
            $currency = $pr->currency;
            $amount = $pr->requested_amount;
            if (!isset($totals[$currency])) {
                $totals[$currency] = 0;
            }
            $totals[$currency] += $amount;
        });

        return $totals;
    }

    /**
     * Calculates total paid payment amounts grouped by currency.
     * Example: If payments in USD add up to 300, the method returns ['USD' => 300].
     */
    #[Computed]
    private function calculateTotalPayments($proforma)
    {
        $totals = [];

        // From associated payment requests' payments.
        $proforma->associatedPaymentRequests->each(function ($req) use (&$totals, $proforma) {
            $isCollective = $req->associatedProformaInvoices?->count() > 1;
            $proformaWeight = $proforma->price * $proforma->quantity * ($proforma->percentage / 100);

            $req->payments->whereNull('deleted_at')->each(function ($payment) use (&$totals, $isCollective, $proformaWeight) {
                $currency = $payment->currency;
                $amount = $payment->amount;

                if ($isCollective) {
                    $totalSum = $payment->paymentRequests->sum('requested_amount');
                    $amount = $payment->amount * ($proformaWeight / $totalSum);
                }

                if (!isset($totals[$currency])) {
                    $totals[$currency] = 0;
                }
                $totals[$currency] += $amount;
            });
        });

        // From orders' payment requests' payments.
        $proforma->orders->flatMap->paymentRequests->flatMap->payments->whereNull('deleted_at')->each(function ($payment) use (&$totals) {
            $currency = $payment->currency;
            $amount = $payment->amount;
            if (!isset($totals[$currency])) {
                $totals[$currency] = 0;
            }
            $totals[$currency] += $amount;
        });

        return $totals;
    }

    /**
     * Formats the totals into a readable string.
     * Example: ['USD' => 250, 'EUR' => 150] becomes "USD: $250.00<br>EUR: $150.00"
     */
    private function formatCurrencyTotals(array $totals): string
    {
        $formatted = [];
        foreach ($totals as $currency => $amount) {
            $formatted[] = "$currency: " . number_format($amount, 2);
        }
        return implode('<br>', $formatted);
    }

    /**
     * Calculates discrepancies by comparing requested vs. paid amounts.
     * Only returns currencies where there is a difference.
     */
    #[Computed]
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

    /**
     * Calculates counts for orders, payment requests, and payments.
     * Example: "Order: 5<br>Pay. Req.: 10<br>Payment: 8"
     */
    #[Computed]
    private function calculateTotalCounts($proforma): string
    {
        $ordersCount = $proforma->orders->whereNull('deleted_at')->count();

        $paymentRequestsCount = $proforma->associatedPaymentRequests->whereNull('deleted_at')->count() +
            $proforma->orders->flatMap->paymentRequests->whereNull('deleted_at')->count();

        $paymentsCount = $proforma->associatedPaymentRequests->flatMap->payments->whereNull('deleted_at')->count() +
            $proforma->orders->flatMap->paymentRequests->flatMap->payments->whereNull('deleted_at')->count();

        return "Order: $ordersCount<br>Pay. Req.: $paymentRequestsCount<br>Payment: $paymentsCount";
    }

    /**
     * Calculates order totals including initial payment, provisional total,
     * final total, and aggregate order total.
     */
    #[Computed]
    private function calculateOrderTotals($proforma): array
    {
        $orders = $proforma->orders->whereNull('deleted_at');

        // Total initial payment is calculated from associated payment requests (excluding Rial).
        $totalInitialPayment = $orders->sum(function ($order) {
            return $order->orderDetail ? $order->orderDetail->initial_payment : 0;
        });

//        $totalInitialPayment = $proforma->associatedPaymentRequests
//            ->where('currency', '!=', 'Rial')
//            ->whereNull('deleted_at')
//            ->sum('requested_amount');

        $totalProvisionalTotal = $orders->sum(function ($order) {
            return $order->orderDetail ? $order->orderDetail->provisional_total : 0;
        });
        $totalFinalTotal = $orders->sum(function ($order) {
            return $order->orderDetail ? $order->orderDetail->final_total : 0;
        });
        $prepayment = $orders->sum(function ($order) {
            return $order->orderDetail ? $order->orderDetail->initial_payment : 0;
        });
        $orderTotal = $prepayment + $totalProvisionalTotal + $totalFinalTotal;

        return [$totalInitialPayment, $totalProvisionalTotal, $totalFinalTotal, $prepayment, $orderTotal];
    }

    /**
     * Calculates the number of days elapsed since the proforma date.
     */
    #[Computed]
    private function calculateDaysElapsed($proforma)
    {
        return $proforma->proforma_date ? $proforma->proforma_date->diffInDays(now()) : null;
    }

    /**
     * Calculates gaps (in days) between the proforma date and BL/declaration dates.
     * Returns formatted strings for each gap.
     */
    #[Computed]
    private function calculateGaps($proforma): array
    {
        $gapBl = [];
        $gapDeclaration = [];
        $orders = $proforma->orders->whereNull('deleted_at')->sortBy('part');

        foreach ($orders as $order) {
            if ($order->doc) {
                $part = $order->part;
                if ($order->doc->BL_date) {
                    $blGap = $order->doc->BL_date->diffInDays($proforma->proforma_date);
                    $gapBl[] = "Part $part: $blGap Days";
                }
                if ($order->doc->declaration_date) {
                    $declarationGap = $order->doc->declaration_date->diffInDays($proforma->proforma_date);
                    $gapDeclaration[] = "Part $part: $declarationGap Days";
                }
            }
        }
        return [implode('<br>', $gapBl), implode('<br>', $gapDeclaration)];
    }

    /**
     * Calculates the expected payment amount by summing the initial payment,
     * provisional total, and final total for each order, grouped by currency.
     */
    #[Computed]
    private function calculateExpectedPaymentByCurrency($proforma): array
    {
        $expectedPayments = [];
        foreach ($proforma->orders as $order) {
            if ($order->deleted_at !== null || !$order->orderDetail) {
                continue;
            }

            $currency = $order->orderDetail->currency ?? 'USD';
            $initialPayment = $order->orderDetail->initial_payment ?? 0;
            $provisionalTotal = $order->orderDetail->provisional_total ?? 0;
            $finalTotal = $order->orderDetail->final_total ?? 0;
            $amount = $initialPayment + $provisionalTotal + $finalTotal;

            if ($amount == 0) {
                continue;
            }

            if (!isset($expectedPayments[$currency])) {
                $expectedPayments[$currency] = 0;
            }
            $expectedPayments[$currency] += $amount;
        }
        return $expectedPayments;
    }

    /**
     * Compares the expected payments with the actual payments to determine
     * the payment status by currency.
     */
    #[Computed]
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

    /**
     * Calculates the total quantity from all orders, prioritizing final_quantity,
     * then provisional_quantity, and finally buying_quantity.
     */
    #[Computed]
    private function calculateTotalOrderQuantities($proforma): array
    {
        $totalQuantity = 0;

        foreach ($proforma->orders as $order) {
            if ($order->deleted_at !== null || !$order->orderDetail) {
                continue;
            }

            $quantity = $order->orderDetail->final_quantity ??
                $order->orderDetail->provisional_quantity ??
                $order->orderDetail->buying_quantity ?? 0;

            $totalQuantity += $quantity;
        }

        return [
            'proforma_quantity' => $proforma->quantity ?? 0,
            'total_order_quantity' => $totalQuantity,
            'status' => $this->compareQuantities($proforma->quantity ?? 0, $totalQuantity),
        ];
    }

    /**
     * Compares the proforma quantity with the total order quantity and returns the status.
     */
    #[Computed]
    private function compareQuantities(float $proformaQuantity, float $totalOrderQuantity): string
    {
        if ($totalOrderQuantity > $proformaQuantity) {
            return 'Over-Ordered ' . number_format($totalOrderQuantity - $proformaQuantity, 2);
        } elseif ($totalOrderQuantity < $proformaQuantity) {
            return 'Under-Ordered ' . number_format($proformaQuantity - $totalOrderQuantity, 2);
        } else {
            return 'Matched';
        }
    }

    /**
     * Calculates the prepayment and remaining amount for the contract.
     */
    #[Computed]
    private function calculatePrepaymentAndRemainingAmount($proforma): array
    {
        $contractValue = $proforma->price * $proforma->quantity;
        $prepaymentPercentage = $proforma->percentage ? ($proforma->percentage / 100) : 0.1;
        $prepayment = $contractValue * $prepaymentPercentage;
        $remainingAmount = $contractValue - $prepayment;

        return [
            'contract_value' => $contractValue,
            'prepayment' => $prepayment,
            'remaining_amount' => $remainingAmount,
        ];
    }

    /**
     * Calculates the progress percentage based on the quantity shipped.
     */
    #[Computed]
    private function calculateProgressByQuantity($proforma): float
    {
        $proformaQuantity = $proforma->quantity;
        $shippedQuantity = $proforma->orders
            ->whereNull('deleted_at')
            ->sum(function ($order) {
                return $order->orderDetail ? $order->orderDetail->final_quantity ?? $order->orderDetail->provisional_quantity ?? $order->orderDetail->buying_quantity ?? 0 : 0;
            });

        return $proformaQuantity > 0 ? ($shippedQuantity / $proformaQuantity) * 100 : 0;
    }

    /**
     * Calculates the progress percentage based on payments made.
     */
    #[Computed]
    private function calculateProgressByPayment($proforma, array $totalPaymentsMadeByCurrency): float
    {
        $contractValue = $proforma->price * $proforma->quantity;
        $currency = 'USD';

        $totalPaymentsMade = $totalPaymentsMadeByCurrency[$currency] ?? 0;

        return $contractValue > 0 ? ($totalPaymentsMade / $contractValue) * 100 : 0;
    }

    public function resetData()
    {
        $this->search = '';
        $this->selectedProforma = null;
        $this->proformaOptions = [];
        $this->businessInsights = null;
        $this->dispatch('refreshSupplierSummary');
        $this->dispatch('$refresh');
    }

    public function render()
    {
        return view('livewire.case-summary.total-summary');
    }

    public function index()
    {
        return view('components.Summary.main');
    }
}
