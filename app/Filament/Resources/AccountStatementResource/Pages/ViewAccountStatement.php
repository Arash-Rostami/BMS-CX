<?php

namespace App\Filament\Resources\AccountStatementResource\Pages;

use App\Filament\Resources\AccountStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAccountStatement extends ViewRecord
{
    protected static string $resource = AccountStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
