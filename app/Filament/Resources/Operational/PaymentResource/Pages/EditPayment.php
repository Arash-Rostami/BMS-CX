<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\User;
use App\Services\NotificationManager;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->successNotification(fn(Model $record) => Admin::send($record)),
        ];
    }

//    protected function beforeSave()
//    {
//        return dd($this->record);
//    }

    protected function afterSave(): void
    {
        $data = [
            'record' => $this->record->order->invoice_number,
            'type' => 'edit',
            'module' => 'payment',
            'url' =>  route('filament.admin.resources.payments.edit', ['record' => $this->record->id]),
            'recipients' => User::all()
        ];

        NotificationManager::send($data);
    }
}
