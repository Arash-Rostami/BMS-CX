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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification as EmailNotification;


class CreateOrderRequest extends CreateRecord
{
    protected static string $resource = OrderRequestResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['request_status']) || $data['request_status'] == 'pending') {
            $data['request_status'] = 'approved';
        }
        return $data;
    }


    protected function afterCreate(): void
    {
        $agents = $this->fetchAgents();
        $this->persistReferenceNumber();


        foreach ($agents as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $this->record->product->name,
                'type' => 'new',
                'module' => 'orderRequest',
                'url' => route('filament.admin.resources.profroma-invoices.index'),
            ]));
        }

//        $this->notifyViaEmail($agents);

//        $this->notifyManagement();
    }

    protected function persistReferenceNumber(): void
    {
        $yearSuffix = date('y');
        $orderIndex = $this->record->id;

        $referenceNumber = sprintf('PI-%s%04d', $yearSuffix, $orderIndex);

        $extra = $this->record->extra ?? [];

        $extra['reference_number'] = $referenceNumber;

        $this->record->extra = $extra;

        $this->record->save();
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
            'url' => route('filament.admin.resources.profroma-invoices.index'),
//            'recipients' => User::getUsersByRole('manager').
            'recipients' => User::getUsersByRole('admin')
        ];

        NotificationManager::send($dataStatus, true);
    }

    /**
     * @return void
     */
    public function notifyViaEmail($agents): void
    {
        $arguments = [$agents, new OrderRequestStatusNotification($this->record)];
// FOR TEST PURPOSE
//       $arguments = [User::getUsersByRole('admin'), new OrderRequestStatusNotification($this->record)];

        RetryableEmailService::dispatchEmail('order request', ...$arguments);
    }


    /**
     * @return mixed
     */
    public function fetchAgents(): mixed
    {
        return Cache::remember('agents', 480, function () {
            return User::getUsersByRole('agent');
        });
    }
}
