<?php

namespace App\Filament\Resources\Operational\QuoteRequestResource\Pages;

use App\Filament\Resources\QuoteRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuoteRequest extends EditRecord
{
    protected static string $resource = QuoteRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
