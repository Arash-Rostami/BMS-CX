<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\User;
use App\Services\NotificationManager;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $paymentRequestIds = $data['payment_request_id'];

        $count = count($paymentRequestIds);

        // store the payment for each payment request selected, except the last one
        for ($i = 0; $i < $count - 1; $i++) {
            $newData = $data;

            $newData['payment_request_id'] = $paymentRequestIds[$i];

            static::getModel()::create($newData);
        }

        // save then store the payment for the last payment request
        $data['payment_request_id'] = $paymentRequestIds[$count - 1];

        return $data;
    }

    protected function afterCreate(): void
    {
        $data = [
            'record' => $this->record->order->invoice_number,
            'type' => 'new',
            'module' => 'payment',
            'url' =>  route('filament.admin.resources.payments.index'),
            'recipients' => User::getUsersByRole('admin')
        ];

        NotificationManager::send($data);
    }
}
