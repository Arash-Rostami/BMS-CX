<?php

namespace App\Filament\Resources\Operational\OrderRequestResource\Pages;

use App\Filament\Resources\OrderRequestResource;
use App\Models\User;
use App\Notifications\OrderRequestStatusNotification;
use App\Services\NotificationManager;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification as EmailNotification;


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
        $this->sendEditNotification();

        $this->sendStatusNotification();

        $this->clearSessionData();
    }

    protected function sendEditNotification()
    {
        $data = [
            'record' => $this->record->product->name,
            'type' => 'edit',
            'module' => 'orderRequest',
            'url' => route('filament.admin.resources.order-requests.edit', ['record' => $this->record->id]),
            'recipients' => User::all(),
        ];
        NotificationManager::send($data);
    }

    protected function sendStatusNotification()
    {
        $newStatus = $this->record['request_status'];

        if ($newStatus && $newStatus !== session('old_status')) {

            $statusData = [
                'record' => $this->record->product->name,
                'type' => $newStatus === 'review' ? 'processing' : ($newStatus === 'fulfilled' ? 'completed' : $newStatus),
                'module' => 'order',
                'url' => route('filament.admin.resources.order-requests.edit', ['record' => $this->record->id]),
                'recipients' => User::all(),
            ];

            $this->notifyViaEmail($statusData['type']);

            NotificationManager::send($statusData, true);
        }
    }

    protected function clearSessionData()
    {
        session()->forget('old_status');
    }

    /**
     * @return void
     */
    public function notifyViaEmail($status): void
    {
        EmailNotification::send(User::find(1), new OrderRequestStatusNotification($this->record, $status));
    }
}
