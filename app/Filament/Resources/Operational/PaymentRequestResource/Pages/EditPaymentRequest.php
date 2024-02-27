<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Filament\Resources\PaymentRequestResource;
use App\Models\User;
use App\Services\NotificationManager;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPaymentRequest extends EditRecord
{
    protected static string $resource = PaymentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->successNotification(fn(Model $record) => Admin::send($record)),
        ];
    }

    protected function beforeSave()
    {
        session(['old_status_payment' => $this->record->getOriginal('status')]);
    }

    protected function afterSave(): void
    {
        $this->sendEditNotification();

        $this->sendStatusNotification();

        $this->clearSessionData();
    }

    protected function sendEditNotification()
    {
        $data = [
            'record' => $this->record->order->invoice_number,
            'type' => 'edit',
            'module' => 'paymentRequest',
            'url' => route('filament.admin.resources.payment-requests.edit', ['record' => $this->record->id]),
            'recipients' => User::all()
        ];

        NotificationManager::send($data);
    }

    protected function sendStatusNotification()
    {
        $newStatus = $this->record['status'];

        if ($newStatus && $newStatus !== session('old_status_payment')) {

            $statusData = [
                'record' => $this->record->order->invoice_number,
                'type' => $newStatus,
                'module' => 'payment',
                'url' => route('filament.admin.resources.payment-requests.edit', ['record' => $this->record->id]),
                'recipients' => User::all(),
            ];

            sleep(1);

            NotificationManager::send($statusData, true);
        }
    }

    protected function clearSessionData()
    {
        session()->forget('old_status_payment');
    }
}
