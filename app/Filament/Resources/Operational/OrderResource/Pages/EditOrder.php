<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\User;
use App\Services\NotificationManager;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

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
            'record' => $this->record->invoice_number,
            'type' => 'edit',
            'module' => 'order',
            'url' =>  route('filament.admin.resources.orders.view', ['record' => $this->record->id]),
            'recipients' => User::all()
        ];

        NotificationManager::send($data);
    }
}
