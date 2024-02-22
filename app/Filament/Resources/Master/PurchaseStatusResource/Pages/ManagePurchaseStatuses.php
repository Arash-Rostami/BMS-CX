<?php

namespace App\Filament\Resources\Master\PurchaseStatusResource\Pages;

use App\Filament\Resources\PurchaseStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePurchaseStatuses extends ManageRecords
{
    protected static string $resource = PurchaseStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-sparkles')
                ->createAnother(false),
        ];
    }
}
