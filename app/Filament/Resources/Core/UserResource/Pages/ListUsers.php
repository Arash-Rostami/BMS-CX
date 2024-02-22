<?php

namespace App\Filament\Resources\Core\UserResource\Pages;

use App\Filament\Resources\UserResource;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;


class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-sparkles')
                ->visible(fn() => isUserAdmin()),
            ExcelImportAction::make()
                ->visible(fn() => isUserAdmin())
                ->color("success"),
        ];
    }
}
