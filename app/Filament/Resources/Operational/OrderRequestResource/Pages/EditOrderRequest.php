<?php

namespace App\Filament\Resources\Operational\OrderRequestResource\Pages;

use App\Filament\Resources\OrderRequestResource;
use App\Models\User;
use App\Services\NotificationManager;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditOrderRequest extends EditRecord
{
    protected static string $resource = OrderRequestResource::class;

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
        session(['old_status' => $this->record->getOriginal('request_status')]);
    }

    protected function afterSave()
    {
        $newStatus = $this->record['request_status'];

        $data = [
            'record' => $this->record->product->name,
            'type' => 'edit',
            'module' => 'orderRequest',
            'url' => route('filament.admin.resources.order-requests.edit', ['record' => $this->record->id]),
            'recipients' => User::all()
        ];

        NotificationManager::send($data);


        if ($newStatus && session('old_status') !== $newStatus) {
            $newStatus = $newStatus === 'review' ? 'processing' : $newStatus;

            $statusData = [
                'record' => $this->record->product->name,
                'type' => $newStatus,
                'module' => 'order',
                'url' => route('filament.admin.resources.order-requests.edit', ['record' => $this->record->id]),
                'recipients' => User::all(),
            ];

            NotificationManager::send($statusData, true);
        }

    }


}
