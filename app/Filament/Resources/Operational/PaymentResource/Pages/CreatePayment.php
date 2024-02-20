<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $paymentRequestIds = $data['payment_request_id'];

        $count = count($paymentRequestIds);

        for ($i = 0; $i < $count - 1; $i++) {
            $newData = $data;

            $newData['payment_request_id'] = $paymentRequestIds[$i];

            static::getModel()::create($newData);
        }

        $data['payment_request_id'] = $paymentRequestIds[$count - 1];

        return $data;
    }
}
