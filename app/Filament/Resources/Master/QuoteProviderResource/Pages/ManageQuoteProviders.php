<?php

namespace App\Filament\Resources\Master\QuoteProviderResource\Pages;

use App\Filament\Resources\QuoteProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageQuoteProviders extends ManageRecords
{
    protected static string $resource = QuoteProviderResource::class;

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
