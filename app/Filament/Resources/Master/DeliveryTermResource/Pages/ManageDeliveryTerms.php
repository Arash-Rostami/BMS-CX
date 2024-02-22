<?php

namespace App\Filament\Resources\Master\DeliveryTermResource\Pages;

use App\Filament\Resources\DeliveryTermResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDeliveryTerms extends ManageRecords
{
    protected static string $resource = DeliveryTermResource::class;

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
