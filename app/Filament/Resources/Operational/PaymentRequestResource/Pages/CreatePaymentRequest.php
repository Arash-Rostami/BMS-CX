<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Filament\Resources\PaymentRequestResource;
use App\Models\Order;
use App\Models\User;
use App\Notifications\FilamentNotification;
use App\Notifications\PaymentRequestStatusNotification;
use App\Services\NotificationManager;
use App\Services\RetryableEmailService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Notification as EmailNotification;


class CreatePaymentRequest extends CreateRecord
{
    protected static string $resource = PaymentRequestResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $partExists = isset($data['part']) && $data['part'];
        $data['extra']['made_by'] = auth()->user()->full_name;

        if ($partExists) {
            $data['order_id'] = $data['part'];
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach (User::getUsersByRole('admin') as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => Admin::getOrderRelation($this->record),
                'type' => 'new',
                'module' => 'paymentRequest',
                'url' => route('filament.admin.resources.payment-requests.index'),
            ]));
        }

        $this->notifyViaEmail();

        $this->notifyManagement();
    }

    /**
     * @return void
     */
    public function notifyManagement(): void
    {
        foreach (User::getUsersByRole('admin') as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => Admin::getOrderRelation($this->record),
                'type' => 'pending',
                'module' => 'payment',
                'url' => route('filament.admin.resources.payment-requests.index'),
            ], true));
        }
    }

    /**
     * @return void
     */
    public function notifyViaEmail(): void
    {
//        $arguments = [User::getUsersByRole('accountant'), new PaymentRequestStatusNotification($this->record)];
        $arguments = [User::getUsersByRole('admin'), new PaymentRequestStatusNotification($this->record)];

        RetryableEmailService::dispatchEmail('payment request', ...$arguments);
    }
}
