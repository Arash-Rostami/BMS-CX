<?php

namespace App\Filament\Resources\Operational\OrderRequestResource\Pages;

use App\Filament\Resources\OrderRequestResource;
use App\Models\User;
use App\Notifications\OrderRequestStatusNotification;
use App\Services\NotificationManager;
use App\Services\RetryableEmailService;
use Exception;
use Filament\Notifications\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Notification as EmailNotification;


class CreateOrderRequest extends CreateRecord
{
    protected static string $resource = OrderRequestResource::class;


    protected function afterCreate(): void
    {
        $data = [
            'record' => $this->record->product->name,
            'type' => 'new',
            'module' => 'orderRequest',
            'url' => route('filament.admin.resources.order-requests.index'),
            'recipients' => User::all()
        ];

        NotificationManager::send($data);

        $this->notifyViaEmail();

        $this->notifyManagement();
    }


    /**
     * @return void
     */
    public function notifyManagement(): void
    {
        $dataStatus = [
            'record' => $this->record->product->name,
            'type' => 'pending',
            'module' => 'order',
            'url' => route('filament.admin.resources.order-requests.index'),
            'recipients' => User::all()
        ];

        NotificationManager::send($dataStatus, true);
    }

    /**
     * @return void
     */
    public function notifyViaEmail(): void
    {
        $arguments = [User::find(1), new OrderRequestStatusNotification($this->record)];

        RetryableEmailService::dispatchEmail(
            EmailNotification::send,
            'order request',
            ...$arguments
        );
    }
}
