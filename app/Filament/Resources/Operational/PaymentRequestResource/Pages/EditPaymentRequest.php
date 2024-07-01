<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Filament\Resources\PaymentRequestResource;
use App\Models\User;
use App\Notifications\FilamentNotification;
use App\Notifications\PaymentRequestStatusNotification;
use App\Services\NotificationManager;
use App\Services\RetryableEmailService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;


class EditPaymentRequest extends EditRecord
{
    protected static string $resource = PaymentRequestResource::class;


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
        $data['extra'] = data_get($this->form->getRawState(), 'extra');

        return $data;
    }

    protected function beforeSave()
    {
        session(['old_status_payment' => $this->record->getOriginal('status')]);
    }

    protected function afterSave(): void
    {
        $this->sendEditNotification();

        $this->sendStatusNotification();

        $this->clearSessionData();
    }

    protected function sendEditNotification()
    {
        foreach (User::getUsersByRole('admin') as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => Admin::getOrderRelation($this->record),
                'type' => 'edit',
                'module' => 'paymentRequest',
                'url' => route('filament.admin.resources.payment-requests.edit', ['record' => $this->record->id]),
            ]));
        }
    }

    protected function sendStatusNotification()
    {
        $newStatus = $this->record['status'];

        if ($newStatus && $newStatus !== session('old_status_payment')) {

            $this->persistStatusChanger();

            foreach (User::getUsersByRole('admin') as $recipient) {
                $recipient->notify(new FilamentNotification([
                    'record' => Admin::getOrderRelation($this->record),
                    'type' => $newStatus,
                    'module' => 'payment',
                    'url' => route('filament.admin.resources.payment-requests.edit', ['record' => $this->record->id]),
                ], true));
            }

            $this->notifyViaEmail($newStatus);
        }
    }

    protected function clearSessionData()
    {
        session()->forget('old_status_payment');
    }


    /**
     * @return void
     */
    public function notifyViaEmail($status): void
    {
//        $arguments = [
//            ($status == 'allowed') ? User::getUsersByRoles(['manager', 'agent']) : User::getUsersByRole('agent'),
//            new PaymentRequestStatusNotification($this->record, $status)
//        ];
        $arguments = [
            User::getUsersByRole('admin'),
            new PaymentRequestStatusNotification($this->record, $status)
        ];

        RetryableEmailService::dispatchEmail('payment request', ...$arguments);
    }


    /**
     * @return void
     */
    public function persistStatusChanger(): void
    {
        $statusChangeInfo = [
            'changed_by' => auth()->user()->full_name,
            'changed_at' => now()->toDateTimeString(),
        ];

        $extra = $this->record->extra ?? [];
        $extra['statusChangeInfo'] = $statusChangeInfo;

        $this->record->extra = $extra;
        $this->record->save();
    }
}
