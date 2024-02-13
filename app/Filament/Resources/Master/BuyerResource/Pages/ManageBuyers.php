<?php

namespace App\Filament\Resources\Master\BuyerResource\Pages;

use App\Filament\Resources\BuyerResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBuyers extends ManageRecords
{
    protected static string $resource = BuyerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
