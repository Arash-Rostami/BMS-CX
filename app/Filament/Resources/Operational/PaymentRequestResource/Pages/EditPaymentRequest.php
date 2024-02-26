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

    protected function afterSave(): void
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
}
