<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Filament\Resources\PaymentRequestResource;
use App\Models\User;
use App\Notifications\PaymentRequestStatusNotification;
use App\Services\NotificationManager;
use App\Services\RetryableEmailService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Notification as EmailNotification;


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

        $this->notifyViaEmail();

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

    /**
     * @return void
     */
    public function notifyViaEmail(): void
    {
        $arguments = [User::find(1), new PaymentRequestStatusNotification($this->record)];

        RetryableEmailService::dispatchEmail(
            EmailNotification::send,
            'payment request',
            ...$arguments
        );
    }
}
