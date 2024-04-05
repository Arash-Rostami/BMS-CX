<?php

namespace App\Filament\Resources\Operational\QuoteRequestResource\Pages;

use App\Filament\Resources\QuoteRequestResource;
use App\Models\QuoteToken;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateQuoteRequest extends CreateRecord
{
    protected static string $resource = QuoteRequestResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        session()->put('recipients', $data['recipient']);

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach (session('recipients') as $recipient) {
            QuoteToken::create([
                'token' => Str::uuid(),
                'validity' => $this->record->validity ?? null,
                'quote_request_id' => $this->record->id,
                'quote_provider_id' => $recipient,

            ]);
        }
        session()->forget('recipients');
    }
}
