<?php

namespace App\Livewire\CaseSummary;

use App\Models\ProformaInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialSummary extends Component
{
    public ?int $proformaInvoiceId;
    public ?ProformaInvoice $proformaInvoice = null;

    protected $listeners = ['selectProforma' => 'mount'];

    public function mount($proformaId = null)
    {
        $this->proformaInvoiceId = $proformaId;
        $this->loadProforma($proformaId);
    }

    protected function loadProforma($id)
    {
        if (!$id) {
            $this->proformaInvoice = null;
            return;
        }

        $this->proformaInvoice = ProformaInvoice::with([
            'buyer',
            'supplier',
            'category',
            'product',
            'grade',
            'associatedPaymentRequests.payments.attachments',
            'associatedPaymentRequests.payments.paymentRequests',
            'associatedPaymentRequests.user',
            'associatedPaymentRequests.payments.user',
            'attachments',
            'orders.orderDetail',
            'orders.logistic.portOfDelivery',
            'orders.doc',
            'orders.paymentRequests.payments.attachments',
            'orders.paymentRequests.user',
            'orders.paymentRequests.payments.user',
            'orders.attachments',
            'orders.purchaseStatus'
        ])->find($id);
    }

    #[Computed]
    public function orderSummary()
    {
        if (!$this->proformaInvoice) {
            return ['rows' => [], 'totals' => array_fill_keys(['quantity', 'initial_payment', 'payment', 'sum'], 0)];
        }

        $rows = [];
        $totals = array_fill_keys(['quantity', 'initial_payment', 'payment', 'sum'], 0);

        foreach ($this->proformaInvoice->orders as $order) {
            $orderDetail = $order->orderDetail;
            $logistic = $order->logistic;
            $doc = $order->doc;

            $quantity = $logistic?->net_weight
                ?? $orderDetail?->final_quantity
                ?? $orderDetail?->provisional_quantity
                ?? 0;

            $price = $orderDetail?->final_price ?? $orderDetail?->provisional_price ?? 0;
            $total = ($quantity && $price) ? $quantity * $price : null;

            $payment = ($orderDetail?->provisional_total ?? 0) + ($orderDetail?->final_total ?? 0);

            $this->updateTotals($totals, $quantity, $orderDetail?->initial_payment, $payment, $total);

            [$blAttachment, $docAttachments] = $this->getOrderAttachments($order->attachments ?? collect());

            $rows[] = [
                'id' => $order->id,
                'part' => $order->part,
                'currency' => $orderDetail?->currency,
                'status' => $order->order_status,
                'stage' => $order->purchaseStatus?->name,
                'reference_number' => $order->reference_number,
                'bl_date' => $doc?->BL_date?->format('d M, Y'),
                'port_of_delivery' => $logistic?->portOfDelivery?->name,
                'provisional_price' => $orderDetail?->provisional_price,
                'final_price' => $orderDetail?->final_price,
                'quantity' => $quantity,
                'initial_payment' => $orderDetail?->initial_payment,
                'payment' => $payment,
                'total' => $total,
                'bl_attachment' => $blAttachment,
                'doc_attachments' => $docAttachments,
            ];
        }

        return compact('rows', 'totals');
    }

    #[Computed]
    public function paymentSummary()
    {
        if (!$this->proformaInvoice) {
            return ['rows' => [], 'totals' => ['paid' => 0, 'balance' => 0]];
        }

        $rows = [];
        $invoiceTotal = $this->orderSummary()['totals']['sum'];
        $runningPaid = 0;


        // 1) Calculate other advances that don't belong to this proforma
        $runningPaid += $this->processAdvancePayments($rows, $invoiceTotal, $runningPaid);


        // 2) Add order-related payments with same currency filter
        $runningPaid += $this->processOrderPayments($rows, $invoiceTotal, $runningPaid);


        return [
            'rows' => $rows,
            'totals' => [
                'paid' => $runningPaid,
                'balance' => $invoiceTotal - $runningPaid,
            ],
        ];
    }

    protected function calculateDateDiff($date1, $date2)
    {
        if ($date1 && $date2) {
            return Carbon::parse($date1)->diffInDays($date2, false);
        }
        return null;
    }

    private function calculateOtherAdvances(): float
    {
        if ($this->proformaInvoice->associatedPaymentRequests->isEmpty()) {
            return 0;
        }

        return $this->proformaInvoice->associatedPaymentRequests
            ->flatMap->payments
            ->flatMap(fn($payment) => $payment->paymentRequests->filter(
                fn($req) => trim($req->proforma_invoice_number) !== trim($this->proformaInvoice->proforma_number)
            ))
            ->sum('requested_amount') ?? 0;
    }


    private function calculatePaymentAmount($pmt, bool $isCollective, float $proformaWeight, float $otherAdvances): float
    {
        $paymentAmount = $pmt->amount;

        if ($isCollective) {
            $totalSum = $pmt->paymentRequests->sum('requested_amount');
            $paymentAmount = $pmt->amount * ($proformaWeight / $totalSum);
        }

        return $paymentAmount - $otherAdvances;
    }

    private function processAdvancePayments(array &$rows, float $invoiceTotal, float $currentPaid): float
    {
        $additionalPaid = 0;
        $otherAdvances = $this->calculateOtherAdvances();

        foreach ($this->proformaInvoice->associatedPaymentRequests as $req) {
            $isCollective = $req->associatedProformaInvoices?->count() > 1;
            $proformaWeight = ($this->proformaInvoice->price ?? 0) * ($this->proformaInvoice->quantity ?? 0) * (($this->proformaInvoice->percentage ?? 0) / 100);

            foreach ($req->payments as $pmt) {
                $paymentAmount = $this->calculatePaymentAmount($pmt, $isCollective, $proformaWeight, $otherAdvances);
                $additionalPaid += $paymentAmount;
                $otherAdvances = 0;

                $rows[] = $this->buildPaymentRow($pmt, $req, null, $paymentAmount, $invoiceTotal, $currentPaid + $additionalPaid, true);
            }
        }

        return $additionalPaid;
    }

    private function updateTotals(array &$totals, $quantity, $initialPayment, $payment, $total): void
    {
        if (is_numeric($quantity)) $totals['quantity'] += $quantity;
        if (is_numeric($initialPayment)) $totals['initial_payment'] += $initialPayment;
        if (is_numeric($payment)) $totals['payment'] += $payment;
        if (is_numeric($total)) $totals['sum'] += $total;
    }

    protected function getOrderAttachments(Collection $attachments): array
    {
        // Find BL attachment (prioritize dated-bl over draft-bl)
        $blAttachment = $attachments
            ->first(fn($att) => trim($att->name) === 'dated-bl')
            ?? $attachments->first(fn($att) => trim($att->name) === 'draft-bl');

        $bl = $blAttachment ? ['url' => $blAttachment->file_path, 'name' => $blAttachment->name] : null;

        // Get document attachments
        $docs = $attachments
            ->whereIn('name', ['pci', 'final-invoice'])
            ->map(fn($att) => ['url' => $att->file_path, 'name' => $att->name])
            ->values()
            ->toArray();

        return [$bl, $docs];
    }

    private function processOrderPayments(array &$rows, float $invoiceTotal, float $currentPaid): float
    {
        $additionalPaid = 0;
        $payments = $this->getOrderPayments();

        foreach ($payments as $item) {
            $additionalPaid += $item['pmt']->amount;
            $rows[] = $this->buildPaymentRow(
                $item['pmt'],
                $item['req'],
                $item['ord'],
                $item['pmt']->amount,
                $invoiceTotal,
                $currentPaid + $additionalPaid,
                false
            );
        }

        return $additionalPaid;
    }


    protected function formatAttachments($attachments)
    {
        return $attachments->map(function ($attachment) {
            return [
                'url' => $attachment->file_path,
                'name' => $attachment->name,
            ];
        })->sortBy('name')->values()->toArray();
    }


    protected function getOrderPayments()
    {
        if (!$this->proformaInvoice || $this->proformaInvoice->orders->isEmpty()) {
            return collect();
        }

        return $this->proformaInvoice->orders
            ->flatMap(function ($order) {
                $currency = $order->orderDetail?->currency;
                if (!$currency) return collect();

                return $order->paymentRequests
                    ->filter(fn($req) => $req->currency === $currency)
                    ->flatMap(fn($req) => $req->payments->map(fn($pmt) => [
                        'pmt' => $pmt,
                        'req' => $req,
                        'ord' => $order,
                    ]));
            })
            ->sortBy(fn($item) => $item['pmt']->created_at);
    }

    private function buildPaymentRow($pmt, $req, $ord, float $amount, float $invoiceTotal, float $runningPaid, bool $isAdvance): array
    {
        $base = [
            'id' => $pmt->id,
            'request_id' => $req->id,
            'advance' => $isAdvance,
            'currency' => $pmt->currency,
            'reference_number' => $pmt->reference_number,
            'request_reference_number' => $req->reference_number,
            'type' => ucfirst($req->type_of_payment),
            'receipts' => $this->formatAttachments($pmt->attachments),
            'value_date' => $pmt->date?->format('d M, Y'),
            'payer' => $pmt->payer,
            'amount' => $amount,
            'balance' => $invoiceTotal - $runningPaid,
            'deadline' => $req->deadline?->format('d M, Y'),
            'swift' => $req->swift_code,
            'request_created_at' => $req->created_at->format('d M, Y'),
            'request_created_by' => $req->user?->full_name,
            'created_at' => $pmt->created_at->format('d M, Y'),
            'created_by' => $pmt->user?->full_name,
            'account' => $req->bank_name,
            'recipient' => $req->recipient_name,
            'diff' => $this->calculateDateDiff($pmt->date, $req->deadline),
        ];

        if ($ord) {
            $base['order_reference_number'] = $ord->reference_number;
        }

        return $base;
    }


    public function exportPdf(): StreamedResponse
    {
        $data = [
            'proformaInvoice' => $this->proformaInvoice,
            'orderSummary' => $this->orderSummary(),
            'paymentSummary' => $this->paymentSummary(),
        ];

        $filename = "BMS-financial-summary-" . trim($data['proformaInvoice']['contract_number'] ?? $this->proformaInvoiceId) . ".pdf";

        return response()->streamDownload(function () use ($data) {
            echo Pdf::loadView('filament.pdfs.financialSummary', $data)
                ->setPaper('a4', 'landscape')
                ->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function render()
    {
        return view('livewire.case-summary.financial-summary', [
            'proformaInvoice' => $this->proformaInvoice,
            'orderSummary' => $this->orderSummary(),
            'paymentSummary' => $this->paymentSummary(),
        ]);
    }
}
