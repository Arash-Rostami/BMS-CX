<?php

namespace App\Filament\Resources\Operational\OrderRequestResource\Pages;

use App\Filament\Resources\OrderRequestResource;
use App\Models\User;
use App\Notifications\FilamentNotification;
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
        foreach (User::getUsersByRole('admin') as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $this->record->product->name,
                'type' => 'new',
                'module' => 'orderRequest',
                'url' => route('filament.admin.resources.order-requests.index'),
            ]));
        }

        $this->notifyViaEmail();

//        $this->notifyManagement();
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
//            'recipients' => User::getUsersByRole('manager').
            'recipients' => User::getUsersByRole('admin')
        ];

        NotificationManager::send($dataStatus, true);
    }

    /**
     * @return void
     */
    public function notifyViaEmail(): void
    {
//        $arguments = [User::getUsersByRole('manager'), new OrderRequestStatusNotification($this->record)];
        $arguments = [User::all(), new OrderRequestStatusNotification($this->record)];

        RetryableEmailService::dispatchEmail('order request', ...$arguments);
    }
}
