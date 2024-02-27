<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Filament\Resources\PaymentRequestResource;
use App\Models\User;
use App\Services\NotificationManager;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentRequest extends CreateRecord
{
    protected static string $resource = PaymentRequestResource::class;

    protected function afterCreate(): void
    {
        $data = [
            'record' => $this->record->order->invoice_number,
            'type' => 'new',
            'module' => 'paymentRequest',
            'url' => route('filament.admin.resources.payment-requests.index'),
            'recipients' => User::all()
        ];

        NotificationManager::send($data);

        sleep(1);

        $this->notifyManagement($this->record);
    }

    /**
     * @return void
     */
    public function notifyManagement($record): void
    {
        $dataStatus = [
            'record' => $record->order->invoice_number,
            'type' => 'pending',
            'module' => 'payment',
            'url' => route('filament.admin.resources.payment-requests.index'),
            'recipients' => User::all()
        ];

        NotificationManager::send($dataStatus, true);
    }
}
