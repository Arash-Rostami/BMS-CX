<?php

namespace App\Filament\Resources\Master\PackagingResource\Pages;

use App\Filament\Resources\PackagingResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePackagings extends ManageRecords
{
    protected static string $resource = PackagingResource::class;

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
