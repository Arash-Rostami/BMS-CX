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
                ->visible(fn() => auth()->user()->role == 'Admin'),
            ExcelImportAction::make()
                ->visible(fn() => auth()->user()->role == 'Admin')
                ->color("success"),
        ];
    }
}
