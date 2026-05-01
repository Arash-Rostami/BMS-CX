<?php

namespace App\Filament\Resources\AccountStatementResource\Pages;

use App\Filament\Resources\AccountStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountStatements extends ListRecords
{
    protected static string $resource = AccountStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
