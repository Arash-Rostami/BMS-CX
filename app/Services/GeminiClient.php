<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiClient
{
    protected $key;
    protected $model;
    protected $endpointBase;

    public function __construct()
    {
        $this->key = config('services.gemini.key');
        $this->model = config('services.gemini.model');
        $this->endpointBase = config('services.gemini.endpoint');
    }


    public function generate(string $prompt): array
    {
        $url = $this->endpointBase . $this->model . ':generateContent';

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $resp =
            Http::withOptions(['verify' => false])
                ->withHeaders(['x-goog-api-key' => $this->key, 'Content-Type' => 'application/json'])
                ->timeout(30)
                ->post($url, $payload);

        if ($resp->failed()) {
            throw new \RuntimeException('Gemini API error: ' . $resp->body(), $resp->status());
        }

        return $resp->json();
    }
}
