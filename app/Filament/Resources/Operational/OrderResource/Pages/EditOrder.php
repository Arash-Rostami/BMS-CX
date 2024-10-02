<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\CreateProformaInvoice;
use App\Filament\Resources\OrderResource;
use App\Notifications\FilamentNotification;
use App\Services\AttachmentCreationService;
use App\Services\OrderService;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createPaymentRequest')
                ->label('Smart Payment')
                ->url(fn($record) => route('filament.admin.resources.payment-requests.create', ['id' => $record->id, 'module' => 'order']))
                ->icon('heroicon-o-credit-card')
                ->visible(fn($record) => Admin::isPaymentCalculated($record))
                ->color('warning')
                ->hidden(fn(?Model $record) => $record ? $record->paymentRequests->isNotEmpty() : false)
                ->openUrlInNewTab(),
            PrintAction::make()
                ->color('amber'),
            Actions\Action::make('pdf')
                ->label('PDF')
                ->color('success')
                ->icon('heroicon-c-inbox-arrow-down')
                ->action(function (Model $record) {
                    return response()->streamDownload(function () use ($record) {
                        echo Pdf::loadHtml(view('filament.pdfs.order', ['record' => $record])
                            ->render())
                            ->stream();
                    }, 'BMS-' . $record->reference_number . '.pdf');
                }),
            Actions\ReplicateAction::make()
                ->color('info')
                ->icon('heroicon-o-clipboard-document-list')
                ->modalWidth(MaxWidth::Medium)
                ->modalIcon('heroicon-o-clipboard-document-list')
                ->record(fn() => $this->record)
                ->beforeReplicaSaved(function (Model $replica) {
                    Admin::increasePart($replica);
                    Admin::replicateRelatedModels($replica);
                })
                ->after(fn(Model $replica) => Admin::syncOrder($replica))
                ->successRedirectUrl(fn(Model $replica): string => route('filament.admin.resources.orders.edit', ['record' => $replica->id,])),
            Actions\DeleteAction::make()
                ->hidden(fn(?Model $record) => $record ? $record->paymentRequests->isNotEmpty() : false)
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
        AttachmentCreationService::createFromExisting($this->form->getState(), $this->record->getOriginal('id'), 'order_id');
    }

    protected function afterSave(): void
    {
        $agents = (new OrderService())->fetchAgents();

        foreach ($agents as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $this->record->invoice_number . ' (' . $this->record->reference_number . ')',
                'type' => 'edit',
                'module' => 'order',
                'url' => route('filament.admin.resources.orders.view', ['record' => $this->record->id]),
            ]));
        }
    }
}
