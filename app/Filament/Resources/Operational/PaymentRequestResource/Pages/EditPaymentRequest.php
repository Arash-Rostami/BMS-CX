<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Filament\Resources\PaymentRequestResource;
use App\Models\User;
use App\Notifications\FilamentNotification;
use App\Notifications\PaymentRequestStatusNotification;
use App\Services\AttachmentCreationService;
use App\Services\NotificationManager;
use App\Services\PaymentRequestService;
use App\Services\RetryableEmailService;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\ReplicateAction;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;


class EditPaymentRequest extends EditRecord
{
    protected static string $resource = PaymentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PrintAction::make()
                ->color('amber'),
           Actions\Action::make('pdf')
                ->label('PDF')
                ->color('success')
                ->icon('heroicon-c-inbox-arrow-down')
                ->action(function (Model $record) {
                    return response()->streamDownload(function () use ($record) {
                        echo Pdf::loadHtml(view('filament.pdfs.paymentRequest', ['record' => $record])
                            ->render())
                            ->stream();
                    }, 'BMS-' . $record->reference_number . '.pdf');
                }),
            Actions\ReplicateAction::make()
                ->visible(fn() => $this->record->order_id != null || $this->record->proforma_invoice_number == null)
                ->color('info')
                ->icon('heroicon-o-clipboard-document-list')
                ->modalWidth(MaxWidth::Medium)
                ->modalIcon('heroicon-o-clipboard-document-list')
                ->record(fn() => $this->record)
                ->mutateRecordDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();
                    return $data;
                })
                ->beforeReplicaSaved(fn(Model $replica) => $replica->status = 'pending')
                ->after(fn(Model $replica) => Admin::syncPaymentRequest($replica))
                ->successRedirectUrl(fn(Model $replica): string => route('filament.admin.resources.payment-requests.edit', ['record' => $replica->id,])),
            Actions\DeleteAction::make()
                ->hidden(fn(?Model $record) => $record ? $record->payments->isNotEmpty() : false)
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
        if ($this->record->payments->isNotEmpty()) {
            $this->haltProcess();
        }

        session(['old_status_payment' => $this->record->getOriginal('status')]);


        AttachmentCreationService::createFromExisting($this->form->getState(), $this->record->getOriginal('id'), 'payment_request_id');
    }


    protected function afterSave(): void
    {
        $allRecipients = (new PaymentRequestService())->fetchAccountants();

        $this->sendEditNotification($allRecipients);

        $this->sendStatusNotification($allRecipients);

        $this->clearSessionData();
    }

    private function sendEditNotification($allRecipients)
    {
        foreach ($allRecipients as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => Admin::getOrderRelation($this->record),
                'type' => 'edit',
                'module' => 'paymentRequest',
                'url' => route('filament.admin.resources.payment-requests.edit', ['record' => $this->record->id]),
            ]));
        }
    }

    private function sendStatusNotification($allRecipients)
    {
        $newStatus = $this->record['status'];

        if ($newStatus && $newStatus !== session('old_status_payment')) {

            $this->persistStatusChanger();

            $madeBy = $this->record['extra']['made_by'] ?? null;
            $specificRecipient = !empty($madeBy) ? $this->findUserByName($madeBy) : null;


            if ($specificRecipient && !$allRecipients->contains('id', $specificRecipient->id)) {
                $allRecipients->push($specificRecipient);
            }

            foreach ($allRecipients as $recipient) {
                $recipient->notify(new FilamentNotification([
                    'record' => Admin::getOrderRelation($this->record),
                    'type' => $newStatus,
                    'module' => 'payment',
                    'url' => route('filament.admin.resources.payment-requests.edit', ['record' => $this->record->id]),
                ], true));
            }

//            $this->notifyViaEmail($newStatus, $allRecipients);
        }
    }

    private function clearSessionData()
    {
        session()->forget('old_status_payment');
    }


    private function notifyViaEmail($status, $allRecipients): void
    {
        $arguments = [$allRecipients, new PaymentRequestStatusNotification($this->record, $status)];

        RetryableEmailService::dispatchEmail('payment request', ...$arguments);
    }


    private function persistStatusChanger(): void
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


    private function haltProcess(): void
    {
        Notification::make()
            ->warning()
            ->title('Record Locked: Payment Received')
            ->persistent()
            ->send();

        $this->halt();
    }

    public function findUserByName($madeBy)
    {
        // Explode the name into parts, removing any empty strings
        $nameParts = array_filter(explode(' ', trim($madeBy)), fn($value) => $value !== '');

        // Initialize the array pointer to the first element
        reset($nameParts);

        // Determine how many parts and construct the query
        switch (count($nameParts)) {
            case 2:  // Assuming only first and last names are provided
                $firstName = current($nameParts);
                $lastName = next($nameParts);
                $user = User::where('first_name', $firstName)
                    ->where('last_name', $lastName)
                    ->first();
                break;

            case 3:  // Assuming first, middle, and last names are provided
                $firstName = current($nameParts);
                $middleName = next($nameParts);
                $lastName = next($nameParts);
                $user = User::where('first_name', $firstName)
                    ->where('middle_name', $middleName)
                    ->where('last_name', $lastName)
                    ->first();
                break;

            default:
                $user = null;
                break;
        }
        return $user;
    }
}
