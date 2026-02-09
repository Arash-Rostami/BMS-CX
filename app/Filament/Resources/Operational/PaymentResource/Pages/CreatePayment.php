<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Balance;
use App\Models\CashTransaction;
use App\Models\PaymentRequest;
use App\Services\Notification\PaymentService;
use App\Services\SmartPayment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;


class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    public ?array $id = null;
    public ?string $module = null;
    public float $credit = 0;


    protected array $queryString = ['id', 'module'];
    protected $listeners = ['applyCredit'];


    public function applyCredit(float $credit = 0): void
    {
        if ($credit <= 0) return;

        $this->credit = $credit;

        $this->data['amount'] = max(
            0, (float)data_get($this->data, 'amount', 0) - $credit);
    }


    protected function afterCreate(): void
    {
        persistReferenceNumber($this->record, 'P');

        $paymentRequests = $this->record->paymentRequests->load('department:id,name');
        $records = $paymentRequests->map(fn($each) => $each->proforma_invoice_number ?? $each->reason->reason)->join(', ');
        $this->record['records'] = $records;

        $this->handleCashTransaction($this->record, $paymentRequests);
        (new PaymentService())->notifyAccountants($this->record);
    }

    protected function afterFill(): void
    {
        SmartPayment::fillForm($this->id, $this->module, $this->form);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $paymentRequests = PaymentRequest::with('payments.paymentRequests')
            ->findMany(data_get($data, 'paymentRequests'));

        $data = $this->processPaymentRequest($paymentRequests, $data);

        return static::getModel()::create($data);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['credit'] = $this->credit;

        if ($data['credit'] <= 0) return $data;

        $currency = $data['currency'] ?? '';
        $creditNote = "\n\n---\nSupplier Credit Applied:  {$currency} " .
            number_format($data['credit'], 2);

        if (!str_contains($data['notes'] ?? '', 'Supplier Credit Applied:')) {
            $data['notes'] = ($data['notes'] ?? '') . $creditNote;
        }

        return $data;
    }

    protected function processPaymentRequest($paymentRequests, array $data): array
    {
        $data = array_merge($data, [
            'remainder' => 0,
            'previousPayments' => 0,
            'totalRequestedAmount' => 0,
            'loop' => false,
            'share' => null,
            'sumOfOtherPR' => 0
        ]);

        foreach ($paymentRequests as $paymentRequest) {
            $data['amount'] = (float)$data['amount'];
            $data['previousPayments'] = $paymentRequest->payments->sum('amount');
            $data['totalRequestedAmount'] += $paymentRequest->requested_amount;

            $processedData = $this->processPayments($data, $paymentRequest);
            $data['remainder'] = $processedData['remainder'];
            $data['share'] = $data['amount'] - $paymentRequest->requested_amount;
        }

        $remainder = $data['totalRequestedAmount'] - ($data['amount'] + ($data['previousPayments'] - $data['sumOfOtherPR']));

        $data['payment_request'] = $paymentRequests->pluck('reference_number')->join(',');
        $data['extra'] = [
            'remainderSum' => $remainder,
            'balanceStatus' => $remainder > 0 ? 'debit' : ($remainder < 0 ? 'credit' : 'settled'),
        ];

        return $data;
    }

    protected function processPayments(array &$data, PaymentRequest $paymentRequest): array
    {
        $data['sumOfOtherPR'] = $paymentRequest->payments
            ->flatMap(fn($payment) => $payment->paymentRequests->where('id', '!=', $paymentRequest->id))
            ->sum('requested_amount');

        $totalPaid = ($data['previousPayments'] - $data['sumOfOtherPR']) + $data['amount'];
        $remainder = $paymentRequest->requested_amount - $totalPaid;
        $credit = $data['credit'] ?? 0;

        $paymentRequest->update([
            'status' => (($data['share'] ?? $totalPaid) + $credit) >= $paymentRequest->requested_amount
                ? 'completed' : 'processing',
        ]);

        if (!$data['loop']) {
            $this->createBalance($paymentRequest, $data);
        }

        return ['notes' => $data['notes'], 'remainder' => $remainder];
    }

    private function createBalance(PaymentRequest $paymentRequest, array &$data): void
    {
        $categories = [
            'payees' => $paymentRequest->payee_id,
            'suppliers' => $paymentRequest->supplier_id,
            'contractors' => $paymentRequest->contractor_id,
        ];

        foreach ($categories as $category => $categoryId) {
            if (!$categoryId) continue;

            $hasSupplierOrContractor = $paymentRequest->supplier_id || $paymentRequest->contractor_id;
            $isPayeeWithoutSupplierOrContractor = $category === 'payees' && !$hasSupplierOrContractor;

            if ($hasSupplierOrContractor || $isPayeeWithoutSupplierOrContractor) {
                Balance::create([
                    'payment' => $data['amount'],
                    'category' => $category,
                    'category_id' => $categoryId,
                    'department_id' => $paymentRequest->department_id,
                    'currency' => $data['currency'],
                    'extra' => ['currency' => $data['currency']]
                ]);

                $data['loop'] = true;
            }
        }
    }

    private function handleCashTransaction($payment, $paymentRequests): void
    {
        if ($payment->bypass_cash_ledger || strtolower($payment->currency) === 'rial') return;

        $pr = $paymentRequests->first(fn($pr) => strtolower(data_get($pr, 'extra.paymentMethod', '')) === 'cash');

        if (!$pr || ($amount = (float)$payment->amount) <= 0) return;

        CashTransaction::create([
            'type' => 'debit', 'amount' => $amount, 'currency' => $payment->currency,
            'description' => sprintf('📍From: %s (%s) 🎯To: %s',
                $pr->department->name ?? 'Unknown Department',
                $pr->reference_number ?? 'Unknown Reference Number',
                $pr->recipient_name ?? 'Unknown Recipient'
            ),
            'payment_id' => $payment->id, 'user_id' => auth()->id(),
        ]);
    }
}
