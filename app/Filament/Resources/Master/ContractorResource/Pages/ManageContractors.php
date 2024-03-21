<?php

namespace App\Filament\Resources\Master\ContractorResource\Pages;

use App\Filament\Resources\ContractorResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageContractors extends ManageRecords
{
    protected static string $resource = ContractorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-sparkles')
                ->createAnother(false),
        ];
    }
}
