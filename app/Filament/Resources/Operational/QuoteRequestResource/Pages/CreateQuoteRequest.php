<?php

namespace App\Filament\Resources\Operational\QuoteRequestResource\Pages;

use App\Filament\Resources\QuoteRequestResource;
use App\Jobs\SendQuoteRequest;
use App\Models\QuoteToken;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class CreateQuoteRequest extends CreateRecord
{
    protected static string $resource = QuoteRequestResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        session()->put('recipients', $data['recipient']);

        return $data;
    }

    protected function afterCreate()
    {
        foreach (session('recipients') as $recipient) {
            $token = QuoteToken::create([
                'token' => Str::uuid(),
                'validity' => $this->record->validity ?? null,
                'quote_request_id' => $this->record->id,
                'quote_provider_id' => $recipient,
            ]);

            $dataToSend = $this->serializeData($token, $recipient);

            Queue::push(new SendQuoteRequest($dataToSend));
        }
        session()->forget('recipients');
    }

    /**
     * @param $token
     * @param mixed $recipientnpm
     * @return array
     */
    protected function serializeData($token, mixed $recipient): array
    {
        $dataToSend = $this->record->toArray();
        $dataToSend['token'] = $token;
        $dataToSend['recipient'] = $recipient;
        $dataToSend['email'] = auth()->user()->email;
        return $dataToSend;
    }
}
