<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\NotificationManager;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $paymentRequestIds = $data['payment_request_id'];
        $paymentAmount = $data['amount'];
        $totalAmountPaid = 0;

        foreach ($paymentRequestIds as $index => $paymentRequestId) {

            // If this is the last item, no need to continue the loop
            if ($index === count($paymentRequestIds) - 1) {
                break;
            }

            // Calculate the remaining payable amount
            $payableAmount = $paymentAmount - $totalAmountPaid;

            // Find the payment request to update
            $paymentRequest = PaymentRequest::find($paymentRequestId);

            // Determine the amount to be paid for the current request
            $amountToPay = min($paymentRequest->individual_amount, $payableAmount);

            // Update the status if the full amount is covered
            if ($amountToPay >= $paymentRequest->individual_amount) {
                $paymentRequest->update(['status' => 'completed']);
            }

            // Prepare the data for the new record
            $newData = [
                    'payment_request_id' => $paymentRequestId,
                    'amount' => $amountToPay,
                ] + $data;

            // Create the new record
            static::getModel()::create($newData);

            // Update the total amount paid
            $totalAmountPaid += $amountToPay;
        }

        // Update the amount for the last payment request
        $data['amount'] = $paymentAmount - $totalAmountPaid;
        $data['payment_request_id'] = end($paymentRequestIds);

        $finalPaymentRequest = PaymentRequest::find($data['payment_request_id']);
        if ($data['amount'] >= $finalPaymentRequest->individual_amount) {
            $finalPaymentRequest->update(['status' => 'completed']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $data = [
            'record' => $this->record->order->invoice_number,
            'type' => 'new',
            'module' => 'payment',
            'url' => route('filament.admin.resources.payments.index'),
            'recipients' => User::getUsersByRole('admin')
        ];

        NotificationManager::send($data);
    }
}
