<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Services\Notification\PaymentService;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use niklasravnsborg\LaravelPdf\Facades\Pdf;


class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

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
                        echo Pdf::loadView('filament.pdfs.payment', ['record' => $record])->output();
                    }, 'BMS-' . $record->reference_number . '.pdf');
                }),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->tooltip(fn() => !auth()->user()->can('canEditInput', $this->record) ? 'Access denied' : '🚨 Make sure you ask Admin to delete balance record linked to this too.')
                ->disabled(fn() => !auth()->user()->can('canEditInput', $this->record))
                ->successNotification(fn(Model $record) => Admin::send($record)),
        ];
    }

    protected function beforeSave(): void
    {
        if (!auth()->user()->can('canEditInput', $this->record)) {
            Notification::make()
                ->title('Access Denied')
                ->body('You do not have permission to edit this record.')
                ->danger()
                ->persistent()
                ->send();

            throw ValidationException::withMessages([
                'error' => 'You do not have permission to edit this record.',
            ]);
        }
    }

    protected function afterSave(): void
    {
        $records = $this->record->paymentRequests->map(fn($each) => $each->proforma_invoice_number ?? $each->reason->reason)->join(', ');

        $this->record['records'] = $records;

        $service = new PaymentService();

        $service->notifyAccountants($this->record, 'edit');
    }
}
