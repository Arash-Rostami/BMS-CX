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
        $paymentRequest = PaymentRequest::find($data['payment_request_id']);

        $previousPayments = $paymentRequest->payments()->sum('amount');

        $processedData = $this->processPayments($data, $paymentRequest, $previousPayments);

        return static::getModel()::create($processedData);
    }

    protected function afterCreate(): void
    {
        foreach (User::getUsersByRole('admin') as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $this->record->paymentRequests->order_invoice_number ??  $this->record->paymentRequests->reason->reason,
                'type' => 'new',
                'module' => 'payment',
                'url' => route('filament.admin.resources.payments.index'),
            ]));
        }
    }


    protected function processPayments(array $data, $paymentRequest, $previousPayments): array
    {
        $payableAmount = $data['amount'];

        $totalPaid = $previousPayments + $payableAmount;
        $remainder = $paymentRequest->requested_amount - $totalPaid;

        // Determine the amount to be paid for the current request
//        $amountToPay = min($paymentRequest->requested_amount - $previousPayments, $payableAmount);

        // Update the status if the full amount is covered
        $paymentRequest->update([
            'status' => $totalPaid >= $paymentRequest->requested_amount ? 'completed' : 'processing',
        ]);

        //Create record of payment in Balance Model (balances table)
        $this->createBalance($paymentRequest, $data);

        // Prepare the data for the new record
        return [
                'payment_request_id' => $paymentRequest->id,
                'amount' => $payableAmount,
                'extra' => [
                    'balanceStatus' => $remainder > 0 ? 'debit' : ($remainder < 0 ? 'credit' : 'settled'),
                    'remainderSum' => $remainder,
                    'note' => $data['extra']['note']
                ]
            ] + $data;
    }


    /**
     * @param $paymentRequest
     * @param $data
     * @return void
     */
    private function createBalance($paymentRequest, $data): void
    {

        $categories = [
            'payees' => $paymentRequest->payee_id,
            'suppliers' => $paymentRequest->supplier_id,
            'contractors' => $paymentRequest->contractor_id,
            'departments' => $paymentRequest->department_id,
        ];


        foreach ($categories as $category => $categoryId) {
            if (!$categoryId) {
                continue;
            }

            $hasSupplierOrContractor = $paymentRequest->supplier_id || $paymentRequest->contractor_id;
            $isDepartmentWithoutSupplierOrContractor = $category === 'departments' && !$hasSupplierOrContractor;
            $isPayeeWithoutSupplierOrContractor = $category === 'payees' && !$hasSupplierOrContractor;

            $shouldCreateBalance = $hasSupplierOrContractor || $isDepartmentWithoutSupplierOrContractor || $isPayeeWithoutSupplierOrContractor;

            if ($shouldCreateBalance) {
                Balance::create([
                    'amount' => $data['amount'],
                    'category' => $category,
                    'category_id' => $categoryId,
                    'extra' => ['currency' => $data['currency']]
                ]);
            }
        }
    }
}
