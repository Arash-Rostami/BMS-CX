<?php

namespace App\Filament\Resources\Operational\PaymentResource\Pages;

use App\Filament\Resources\PaymentRequestResource;
use App\Filament\Resources\PaymentResource;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-sparkles'),
            ExcelImportAction::make()
                ->color("success"),
            PrintAction::make(),
        ];
    }


    protected function getHeaderWidgets(): array
    {
        return PaymentResource::getWidgets();
    }
}
