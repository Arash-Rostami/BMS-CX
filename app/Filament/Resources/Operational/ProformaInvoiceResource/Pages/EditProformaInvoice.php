<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\Pages;

//use App\Filament\deprecated\OrderRequestResource\Pages\Admin;
use App\Filament\Resources\ProformaInvoiceResource;
use App\Models\Attachment;
use App\Models\User;
use App\Notifications\ProformaInvoiceStatusNotification;
use App\Services\AttachmentCreationService;
use App\Services\Notification\ProformaInvoiceService;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class EditProformaInvoice extends EditRecord
{
    protected static string $resource = ProformaInvoiceResource::class;

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
                        echo Pdf::loadView('filament.pdfs.proformaInvoice', ['record' => $record])->output();
                    }, 'BMS-' . $record->reference_number . '.pdf');
                }),
            Actions\ReplicateAction::make()
                ->color('info')
                ->icon('heroicon-o-clipboard-document-list')
                ->modalWidth(MaxWidth::Medium)
                ->modalIcon('heroicon-o-clipboard-document-list')
                ->record(fn() => $this->record)
                ->mutateRecordDataUsing(function (array $data): array {
                    $data['user_id'] = auth()->id();
                    return $data;
                })
                ->after(fn(Model $replica) => Admin::syncProformaInvoice($replica))
                ->successRedirectUrl(fn(Model $replica): string => route('filament.admin.resources.proforma-invoices.edit', ['record' => $replica->id,])),
            Actions\DeleteAction::make()
                ->hidden(fn(?Model $record) => $record && ($record->activeApprovedPaymentRequests->isNotEmpty() || $record->activeOrders->isNotEmpty()))
                ->icon('heroicon-o-trash')
                ->successNotification(fn(Model $record) => Admin::send($record)),
        ];
    }


    protected function beforeSave()
    {
        session(['old_status' => $this->record->getOriginal('status')]);
        $hasExistingAttachment = data_get($this->form->getRawState(), 'use_existing_attachments') ?? false;

        if ($hasExistingAttachment) {
            Cache::put('available_attachments', data_get($this->form->getRawState(), 'available_attachments'), 10);
        }

        AttachmentCreationService::createFromExisting($this->record->getOriginal('id'));
    }


    protected function afterSave()
    {
        $service = new ProformaInvoiceService();

        $service->notifyAgents($this->record, 'edit');

        $this->sendStatusNotification($service);

        $this->clearSessionData();
    }


    protected function sendStatusNotification($service)
    {
        $newStatus = $this->record['status'];

        if ($newStatus && $newStatus !== session('old_status')) {

            $status = $newStatus === 'review'
                ? 'processing'
                : ($newStatus === 'fulfilled' ? 'completed' : $newStatus);

            $service->notifyAgents($this->record, type: $status, status: true);
        }
    }


    protected function clearSessionData()
    {
        session()->forget('old_status');
    }
}
