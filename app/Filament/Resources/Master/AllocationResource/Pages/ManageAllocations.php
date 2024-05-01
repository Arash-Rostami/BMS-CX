<?php

namespace App\Filament\Resources\Master\AllocationResource\Pages;

use App\Filament\Resources\AllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAllocations extends ManageRecords
{
    protected static string $resource = AllocationResource::class;

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
