<?php

namespace App\Filament\Resources\Master\PayeeResource\Pages;

use App\Filament\Resources\PayeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePayees extends ManageRecords
{
    protected static string $resource = PayeeResource::class;

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
