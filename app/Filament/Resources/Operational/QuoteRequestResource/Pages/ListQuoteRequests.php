<?php

namespace App\Filament\Resources\Operational\QuoteRequestResource\Pages;

use App\Filament\Resources\QuoteRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuoteRequests extends ListRecords
{
    protected static string $resource = QuoteRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-sparkles'),
        ];
    }
}
