<?php

namespace App\Filament\Resources\Operational\QuoteRequestResource\Pages;

use App\Filament\Resources\QuoteRequestResource;
use App\Jobs\SendQuoteRequest;
use App\Mail\QuoteRequestEmail;
use App\Models\ProviderList;
use App\Models\QuoteToken;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class CreateQuoteRequest extends CreateRecord
{
    protected static string $resource = QuoteRequestResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $recipients = $this->getRecipientNames($data['extra']['recipient']);

        session()->put('recipients', $recipients);

        return $data;
    }

    protected function afterCreate()
    {
        $recipients = session('recipients');
        $useMarkdown = $this->record->extra['use_markdown'] ?? false;

        if ($useMarkdown) {
            $this->sendMarkdownEmails($recipients);
        } else {
            $this->generateTokensAndDispatchJobs($recipients);
        }

        session()->forget('recipients');
    }

    protected function sendMarkdownEmails(array $recipients)
    {
        $content = $this->record->extra['details'] ?? '';

        collect($recipients)->each(function ($recipient) use ($content) {
            Mail::to($recipient['email'])->queue(new QuoteRequestEmail($content, $recipient, auth()->user()));
        });
    }

    protected function generateTokensAndDispatchJobs(array $recipients)
    {
        $tokensData = collect($recipients)->map(function ($recipient) {
            return [
                'token' => (string)Str::uuid(),
                'validity' => $this->record->validity ?? null,
                'quote_request_id' => $this->record->id,
                'quote_provider_id' => $recipient['id'],
                'created_at' => now(),
            ];
        })->toArray();

        QuoteToken::insert($tokensData);

        $jobs = collect($tokensData)->map(function ($tokenData) {
            $dataToSend = $this->serializeData($tokenData, $tokenData['quote_provider_id']);
            return new SendQuoteRequest($dataToSend);
        })->toArray();

        Queue::bulk($jobs);
    }


    public function getRecipientNames($recipient)
    {
        return ProviderList::whereIn('id', $recipient)
            ->with('quoteProviders')
            ->get()
            ->pluck('quoteProviders')
            ->flatten()
            ->map(function ($provider) {
                return [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'company' => $provider->company,
                    'title' => $provider->title,
                    'email' => $provider->email,
                ];
            })
            ->unique('id')
            ->toArray();
    }

    protected function serializeData($token, mixed $recipient): array
    {
        $dataToSend = $this->record->toArray();
        $dataToSend['token'] = $token;
        $dataToSend['recipient'] = $recipient;
        $dataToSend['email'] = auth()->user()->email;
        return $dataToSend;
    }
}
