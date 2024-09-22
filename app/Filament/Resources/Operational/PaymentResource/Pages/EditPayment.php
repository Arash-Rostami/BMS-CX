<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\User;
use App\Notifications\FilamentNotification;
use App\Services\NotificationManager;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

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
                        echo Pdf::loadHtml(view('filament.pdfs.payment', ['record' => $record])
                            ->render())
                            ->stream();
                    }, 'BMS-' . $record->reference_number . '.pdf');
                }),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->successNotification(fn(Model $record) => Admin::send($record)),
        ];
    }

    protected function afterSave(): void
    {
        $records = $this->record->paymentRequests->map(fn($each) => $each->proforma_invoice_number ?? $each->reason->reason)->join(', ');

        foreach (User::getUsersByRole('admin') as $recipient) {
            $recipient->notify(new FilamentNotification([
                'record' => $records,
                'type' => 'edit',
                'module' => 'payment',
                'url' => route('filament.admin.resources.payments.edit', ['record' => $this->record->id]),
            ]));
        }
    }
}
