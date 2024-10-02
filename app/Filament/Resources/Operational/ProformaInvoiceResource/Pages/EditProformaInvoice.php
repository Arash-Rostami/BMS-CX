<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\Pages;

//use App\Filament\deprecated\OrderRequestResource\Pages\Admin;
use App\Filament\Resources\ProformaInvoiceResource;
use App\Models\Attachment;
use App\Models\User;
use App\Notifications\FilamentNotification;
use App\Notifications\ProformaInvoiceStatusNotification;
use App\Services\AttachmentCreationService;
use App\Services\ProformaInvoiceService;
use App\Services\RetryableEmailService;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use LaraZeus\Delia\Filament\Actions\BookmarkHeaderAction;

class EditProformaInvoice extends EditRecord
{
    protected static string $resource = ProformaInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createPaymentRequest')
                ->label('Smart Payment')
                ->url(fn(Model $record) => route('filament.admin.resources.payment-requests.create', ['id' => $record->id, 'module' => 'proforma-invoice']))
                ->icon('heroicon-o-credit-card')
                ->color('warning')
                ->hidden(fn(?Model $record) => $record ? $record->activeApprovedPaymentRequests->isNotEmpty() : false)
                ->openUrlInNewTab(),
            PrintAction::make()
                ->color('amber'),
            Actions\Action::make('pdf')
                ->label('PDF')
                ->color('success')
                ->icon('heroicon-c-inbox-arrow-down')
                ->action(function (Model $record) {
                    return response()->streamDownload(function () use ($record) {
                        echo Pdf::loadHtml(view('filament.pdfs.proformaInvoice', ['record' => $record])->render())->stream();
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

        AttachmentCreationService::createFromExisting($this->form->getState(), $this->record->getOriginal('id'));
    }


    protected function afterSave()
    {
        $agents = (new ProformaInvoiceService())->fetchAgents();

        $this->sendEditNotification($agents);

        $this->sendStatusNotification($agents);

        $this->clearSessionData();
    }

    protected function sendEditNotification($agents)
    {
        foreach ($agents as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $this->record->proforma_number . ' (' . $this->record->reference_number . ')',
                'type' => 'edit',
                'module' => 'proformaInvoice',
                'url' => route('filament.admin.resources.proforma-invoices.edit', ['record' => $this->record->id]),
            ]));
        }
    }

    protected function sendStatusNotification($agents)
    {
        $newStatus = $this->record['status'];

        if ($newStatus && $newStatus !== session('old_status')) {

            foreach ($agents as $recipient) {
                $recipient->notify(new FilamentNotification([
                    'record' => $this->record->proforma_number . ' (' . $this->record->reference_number . ')',
                    'type' => $newStatus === 'review' ? 'processing' : ($newStatus === 'fulfilled' ? 'completed' : $newStatus),
                    'module' => 'proformaInvoice',
                    'url' => route('filament.admin.resources.proforma-invoices.edit', ['record' => $this->record->id]),
                ], true));
            }
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
        $arguments = [
            ($status == 'approved') ? User::getUsersByRole('agent') : User::getUsersByRole('partner'),
            new ProformaInvoiceStatusNotification($this->record, $status)
        ];

        RetryableEmailService::dispatchEmail('order request', ...$arguments);
    }
}
