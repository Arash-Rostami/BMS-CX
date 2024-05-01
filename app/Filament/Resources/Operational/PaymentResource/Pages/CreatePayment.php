<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\NotificationManager;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;


class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Load PaymentRequest models
        $paymentRequests = PaymentRequest::find($data['payment_request_id'])->get();

        list($lastModelInstance, $outstandingAmount) = $this->processPayments($data, $paymentRequests);

        if ($outstandingAmount > 0) {
            $outstandingData = [
                    'payment_request_id' => null,
                    'amount' => $outstandingAmount,
                ] + $data;

            $lastModelInstance = static::getModel()::create($outstandingData);
        }
        return $lastModelInstance;
    }


    protected function afterCreate(): void
    {
//        $data = [
//            'record' => $this->record->order->invoice_number,
//            'type' => 'new',
//            'module' => 'payment',
//            'url' => route('filament.admin.resources.payments.index'),
//            'recipients' => User::getUsersByRole('admin')
//        ];
//
//        NotificationManager::send($data);
    }


    /**
     * @param array $data
     * @param $paymentRequests
     * @return array
     */
    protected function processPayments(array $data, $paymentRequests): array
    {
        $paymentAmount = $data['amount'];
        $totalAmountPaid = 0;
        $lastModelInstance = null;

        foreach ($paymentRequests as $paymentRequest) {

            // Calculate the remaining payable amount
            $payableAmount = $paymentAmount - $totalAmountPaid;

            // Determine the amount to be paid for the current request
            $amountToPay = min($paymentRequest->requested_amount, $payableAmount);

            // Update the status if the full amount is covered
            $paymentRequest->update([
                'status' => $amountToPay >= $paymentRequest->requested_amount ? 'completed' : 'processing',
            ]);

            // Prepare the data for the new record
            $newData = [
                    'payment_request_id' => $paymentRequest->id,
                    'amount' => $amountToPay,
                ] + $data;

            // Create the new record
            $lastModelInstance = static::getModel()::create($newData);

            // Update the total amount paid
            $totalAmountPaid += $amountToPay;
        }

        $outstandingAmount = $paymentAmount - $totalAmountPaid;

        return array($lastModelInstance, $outstandingAmount);
    }
}
