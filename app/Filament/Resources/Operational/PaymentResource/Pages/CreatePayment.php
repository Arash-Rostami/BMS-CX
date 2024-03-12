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
        $paymentRequests = PaymentRequest::whereIn('id', $data['payment_request_id'])->get();

        $paymentAmount = $data['amount'];
        $totalAmountPaid = 0;
        $lastModelInstance = null;

        foreach ($paymentRequests as $paymentRequest) {

            // Calculate the remaining payable amount
            $payableAmount = $paymentAmount - $totalAmountPaid;

            // Determine the amount to be paid for the current request
            $amountToPay = min($paymentRequest->individual_amount, $payableAmount);

            // Update the status if the full amount is covered
            $paymentRequest->update([
                'status' => $amountToPay >= $paymentRequest->individual_amount ? 'completed' : 'processing',
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
        return $lastModelInstance;
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
