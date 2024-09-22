<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\Pages;

use App\Filament\deprecated\OrderRequestResource;
use App\Filament\Resources\ProformaInvoiceResource;
use ArielMejiaDev\FilamentPrintable\Actions\PrintAction;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListProformaInvoices extends ListRecords
{
    protected static string $resource = ProformaInvoiceResource::class;

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
        return ProformaInvoiceResource::getWidgets();
    }
}
