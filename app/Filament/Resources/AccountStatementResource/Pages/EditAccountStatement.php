<?php

namespace App\Filament\Resources\AccountStatementResource\Pages;

use App\Filament\Resources\AccountStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccountStatement extends EditRecord
{
    protected static string $resource = AccountStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
