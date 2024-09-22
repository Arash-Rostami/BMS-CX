<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Filament\Resources\Operational\PaymentRequestResource\Pages\Admin;
use App\Filament\Resources\PaymentResource;
use App\Models\Balance;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Notifications\FilamentNotification;
use App\Services\NotificationManager;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;


class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;


    protected function handleRecordCreation(array $data): Model
    {

        $paymentRequests = PaymentRequest::findMany(data_get($data, 'paymentRequests.id'));

        list($totalAmountPaid, $totalRequestedAmount, $notes, $previousPayments) = $this->processPaymentRequest($paymentRequests, $data);

        $finalData = [
            'payment_request_id' => implode(',', data_get($data, 'paymentRequests.id')),
            'amount' => $totalAmountPaid,
            'notes' => $notes,
            'extra' => [
                'remainderSum' => $remainder = $totalRequestedAmount - ($totalAmountPaid + $previousPayments),
                'balanceStatus' => $remainder > 0 ? 'debit' : ($remainder < 0 ? 'credit' : 'settled'),
            ]
        ];

        return static::getModel()::create(array_merge($data, $finalData));
    }


    protected function processPaymentRequest($paymentRequests, array $data): array
    {
        $previousPayments = 0;
        $totalAmountPaid = 0;
        $totalRequestedAmount = 0;
        $notes = '';

        foreach ($paymentRequests as $paymentRequest) {
            $previousPayments = $paymentRequest->payments->sum('amount');
            $totalRequestedAmount += $paymentRequest->requested_amount;

            $processedData = $this->processPayments($data, $paymentRequest, $previousPayments);
            $totalAmountPaid += $processedData['amount'];
            $notes = $processedData['notes'];
        }
        return array($totalAmountPaid, $totalRequestedAmount, $notes, $previousPayments);
    }


    protected function processPayments(array $data, $paymentRequest, $previousPayments): array
    {
        $payableAmount = $data['amount'];
        $totalPaid = $previousPayments + $payableAmount;
        $remainder = $paymentRequest->requested_amount - $totalPaid;

        $paymentRequest->update([
            'status' => $totalPaid >= $paymentRequest->requested_amount ? 'completed' : 'processing',
        ]);

        $this->createBalance($paymentRequest, $data);

        return [
            'amount' => $payableAmount,
            'notes' => $data['notes'],
            'remainder' => $remainder,
        ];
    }

    private function createBalance($paymentRequest, $data): void
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
                    'extra' => ['currency' => $data['currency']]
                ]);
            }
        }
    }

    protected function afterCreate(): void
    {

        $records = $this->record->paymentRequests->map(fn($each) => $each->proforma_invoice_number ?? $each->reason->reason)->join(', ');

        $this->persistReferenceNumber();

        foreach (User::getUsersByRole('admin') as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $records,
                'type' => 'new',
                'module' => 'payment',
                'url' => route('filament.admin.resources.payments.index'),
            ]));
        }
    }

    protected function persistReferenceNumber(): void
    {
        $yearSuffix = date('y');

        $orderIndex = $this->record->id;

        $referenceNumber = sprintf('P-%s%04d', $yearSuffix, $orderIndex);

        $this->record->reference_number = $referenceNumber;

        $this->record->save();
    }
}
