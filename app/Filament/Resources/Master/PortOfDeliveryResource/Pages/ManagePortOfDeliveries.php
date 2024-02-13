<?php

namespace App\Filament\Resources\Master\PortOfDeliveryResource\Pages;

use App\Filament\Resources\PortOfDeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePortOfDeliveries extends ManageRecords
{
    protected static string $resource = PortOfDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
