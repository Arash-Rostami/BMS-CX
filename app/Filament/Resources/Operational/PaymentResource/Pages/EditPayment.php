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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['extra']) {
            $originalExtra = data_get($this->record, 'extra', []);
            $editedExtra = $data['extra'];

            $mergedExtra = array_merge($originalExtra, $editedExtra);

            $data['extra'] = $mergedExtra;
        }

        return $data;
    }


    protected function afterSave(): void
    {
        $data = [
            'record' => $this->record->paymentRequests->reason->reason,
            'type' => 'edit',
            'module' => 'payment',
            'url' => route('filament.admin.resources.payments.edit', ['record' => $this->record->id]),
            'recipients' => User::getUsersByRole('admin')
        ];

        NotificationManager::send($data);
    }
}
