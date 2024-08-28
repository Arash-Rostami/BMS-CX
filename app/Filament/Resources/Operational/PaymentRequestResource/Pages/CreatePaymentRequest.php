<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Filament\Resources\PaymentRequestResource;
use App\Models\User;
use App\Notifications\FilamentNotification;
use App\Notifications\PaymentRequestStatusNotification;
use App\Services\RetryableEmailService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;


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
        $accountants = $this->fetchAccountants();
        $this->persistReferenceNumber();


        foreach ($accountants as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => Admin::getOrderRelation($this->record),
                'type' => 'new',
                'module' => 'paymentRequest',
                'url' => route('filament.admin.resources.payment-requests.index'),
            ]));
        }

        $this->notifyViaEmail($accountants);

//        $this->notifyManagement();
    }

    protected function persistReferenceNumber(): void
    {
        $yearSuffix = date('y');
        $orderIndex = $this->record->id;

        $referenceNumber = sprintf('PR-%s%04d', $yearSuffix, $orderIndex);

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
        foreach (User::getUsersByRole('manager') as $recipient) {
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
    public function notifyViaEmail($accountants): void
    {
        $arguments = [$accountants, new PaymentRequestStatusNotification($this->record)];
// FOR TEST PURPOSE
//       $arguments = [User::getUsersByRole('admin'), new PaymentRequestStatusNotification($this->record)];

        RetryableEmailService::dispatchEmail('payment request', ...$arguments);
    }


    /**
     * @return mixed
     */
    public function fetchAccountants(): mixed
    {
        return Cache::remember('accountants', 480, function () {
            return User::getUsersByRole('accountant');
        });
    }
}
