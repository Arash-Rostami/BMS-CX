<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Filament\Resources\PaymentRequestResource;
use App\Models\Attachment;
use App\Models\User;
use App\Notifications\PaymentRequestStatusNotification;
use App\Services\AttachmentCreationService;
use App\Services\Notification\PaymentRequestService;
use App\Services\NotificationManager;
use App\Services\RetryableEmailService;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\ReplicateAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use niklasravnsborg\LaravelPdf\Facades\Pdf;


class EditPaymentRequest extends EditRecord
{
    protected static string $resource = PaymentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('open_payment_attachments')
                ->label('👁️ Instant View of Payment Attachments')
                ->extraAttributes(['class' => 'animate-bounce'])
                ->visible(fn(Model $record) => Attachment::whereIn('payment_id', $record->payments->pluck('id'))->exists())
                ->action(function (Model $record) {
                    $attachmentUrls = Attachment::whereIn('payment_id', $record->payments->pluck('id'))
                        ->pluck('file_path')
                        ->map(fn($path) => asset($path))
                        ->all();

                    if (empty($attachmentUrls)){
                        Notification::make()
                            ->warning()
                            ->title('No Attachments Found')
                            ->body('No attachments found for the linked payments.')
                            ->send();
                        return;
                    }
                    $this->dispatch('open-new-tab', $attachmentUrls);
                }),
            PrintAction::make()
                ->color('amber'),
            Actions\Action::make('pdf')
                ->label('PDF')
                ->color('success')
                ->icon('heroicon-c-inbox-arrow-down')
                ->action(function (Model $record) {
                    return response()->streamDownload(function () use ($record) {
                        echo Pdf::loadView('filament.pdfs.paymentRequest', ['record' => $record])->output();
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

        if (! isset($data['total_amount'])) {
            $data['total_amount'] = $data['requested_amount'];
        }

        $data = (new CreatePaymentRequest())->persistAccountNo($data);

        return $data;
    }

    protected function beforeSave()
    {
        if ($this->record->payments->isNotEmpty()) {
            $this->haltProcess();
        }

        session(['old_status_payment' => $this->record->getOriginal('status')]);
        $hasExistingAttachment = data_get($this->form->getRawState(), 'use_existing_attachments') ?? false;

        if ($hasExistingAttachment) {
            Cache::put('available_attachments', data_get($this->form->getRawState(), 'available_attachments'), 10);
        }

        AttachmentCreationService::createFromExisting($this->record->getOriginal('id'), 'payment_request_id');
    }


    protected function afterSave(): void
    {
        $record = $this->record;

        $service = new PaymentRequestService();

        $service->notifyAccountants($record, type: 'edit');

        $this->sendStatusNotification($service);

        $this->clearSessionData();
    }


    private function sendStatusNotification($service)
    {
        $newStatus = $this->record['status'];


        if ($newStatus && $newStatus !== session('old_status_payment')) {

            $this->persistStatusChanger();
            $allRecipients = User::getUsersByRole('accountant');

            $madeBy = $this->record['user_id'] ?? null;
            $specificRecipient = !empty($madeBy) ? User::find($madeBy) : null;


            if ($specificRecipient && !$allRecipients->contains('id', $specificRecipient->id)) {
                $allRecipients->push($specificRecipient);
            }

            $service->notifyAccountants($this->record, type: $newStatus, status: true, accountants: $allRecipients);
        }
    }

    private function clearSessionData()
    {
        session()->forget('old_status_payment');
    }


    private function persistStatusChanger(): void
    {
        $statusChangeInfo = [
            'changed_by' => auth()->user()->full_name,
            'changed_at' => now()->toDateTimeString(),
        ];

        $extra = $this->record->extra ?? [];
        $extra['statusChangeInfo'] = $statusChangeInfo;

        $this->record->update([
            'extra' => $extra
        ]);
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
}
