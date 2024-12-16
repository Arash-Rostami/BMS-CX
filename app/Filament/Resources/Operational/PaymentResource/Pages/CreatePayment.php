<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Filament\Resources\Operational\PaymentRequestResource\Pages\Admin;
use App\Filament\Resources\PaymentResource;
use App\Models\Balance;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\Notification\PaymentService;
use App\Services\NotificationManager;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;


class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;


    protected function handleRecordCreation(array $data): Model
    {
        $paymentRequests = PaymentRequest::with('payments.paymentRequests')
            ->findMany(data_get($data, 'paymentRequests.id'));

        $data = $this->processPaymentRequest($paymentRequests, $data);

        return static::getModel()::create($data);
    }


    protected function processPaymentRequest($paymentRequests, array &$data): array
    {
        $data['remainder'] = 0;
        $data['previousPayments'] = 0;
        $data['totalRequestedAmount'] = 0;
        $data['notes'] = '';
        $data['loop'] = false;
        $data['share'] = null;
        $data['sumOfOtherPR'] = 0;

        foreach ($paymentRequests as $paymentRequest) {
            $data['amount'] = (float)$data['amount'];
            $data['previousPayments'] = $paymentRequest->payments->sum('amount');
            $data['totalRequestedAmount'] += $paymentRequest->requested_amount;

            $processedData = $this->processPayments($data, $paymentRequest);
            $data['remainder'] = $processedData['remainder'];
            $data['notes'] = $processedData['notes'];
            $data['share'] = $data['amount'] - $paymentRequest->requested_amount;
        }

        $data['payment_request'] = implode(',', $paymentRequests->pluck('reference_number')->toArray());
        $data['extra'] = [
            'remainderSum' => $remainder = $data['totalRequestedAmount'] - ($data['amount'] + ($data['previousPayments'] - ($data['sumOfOtherPR']))),
            'balanceStatus' => $remainder > 0 ? 'debit' : ($remainder < 0 ? 'credit' : 'settled'),
        ];

        return $data;
    }


    protected function processPayments(array &$data, $paymentRequest): array
    {
        $previousPaymentRecords = $paymentRequest->payments;
        $data['sumOfOtherPR'] = $previousPaymentRecords->flatMap(function ($payment) use ($paymentRequest) {
            return $payment->paymentRequests->where('id', '!=', $paymentRequest->id);
        })->sum('requested_amount');


        $totalPaid = ($data['previousPayments'] - ($data['sumOfOtherPR'])) + $data['amount'];
        $remainder = $paymentRequest->requested_amount - $totalPaid;


        $paymentRequest->update([
            'status' => ($data['share'] ?? $totalPaid) >= ($paymentRequest->requested_amount)
                ? 'completed' : 'processing',
        ]);

        if (!$data['loop']) {
            $this->createBalance($paymentRequest, $data);
        }

        return ['notes' => $data['notes'], 'remainder' => $remainder,];
    }

    private function createBalance($paymentRequest, &$data): void
    {

        $categories = [
            'payees' => $paymentRequest->payee_id,
            'suppliers' => $paymentRequest->supplier_id,
            'contractors' => $paymentRequest->contractor_id,
        ];


        foreach ($categories as $category => $categoryId) {
            if (!$categoryId) {
                continue;
            }

            $hasSupplierOrContractor = $paymentRequest->supplier_id || $paymentRequest->contractor_id;
            $isPayeeWithoutSupplierOrContractor = $category === 'payees' && !$hasSupplierOrContractor;

            $shouldCreateBalance = $hasSupplierOrContractor || $isPayeeWithoutSupplierOrContractor;

            if ($shouldCreateBalance) {

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

    protected function afterCreate(): void
    {
        $records = $this->record->paymentRequests->map(fn($each) => $each->proforma_invoice_number ?? $each->reason->reason)->join(', ');

        persistReferenceNumber($this->record, 'P');

        $this->record['records'] = $records;

        $service = new PaymentService();

        $service->notifyAccountants($this->record);
    }
}
