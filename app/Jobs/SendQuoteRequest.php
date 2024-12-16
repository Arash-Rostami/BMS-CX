<?php

namespace App\Jobs;

use App\Models\QuoteProvider;
use App\Notifications\QuoteRequestNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendQuoteRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected array $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        $recipient = QuoteProvider::find($this->data['recipient']);

        if (!$recipient) {
            Log::warning("QuoteProvider with ID {$this->data['recipient']} not found.");
            return;
        }

        $this->data['recipient'] = $recipient;

        if ($this->data['recipient']) {
            Notification::send($this->data['recipient'], new QuoteRequestNotification($this->data));
        }
    }
}
