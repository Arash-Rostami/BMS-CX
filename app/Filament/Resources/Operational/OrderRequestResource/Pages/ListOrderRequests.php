<?php

namespace App\Filament\Resources\Operational\OrderRequestResource\Pages;

use App\Filament\Resources\OrderRequestResource;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrderRequests extends ListRecords
{
    protected static string $resource = OrderRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ExcelImportAction::make()
                ->color("success"),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return OrderRequestResource::getWidgets();
    }

}
