<?php

namespace App\Filament\Resources\Master\ShippingLineResource\Pages;

use App\Filament\Resources\ShippingLineResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageShippingLines extends ManageRecords
{
    protected static string $resource = ShippingLineResource::class;

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
