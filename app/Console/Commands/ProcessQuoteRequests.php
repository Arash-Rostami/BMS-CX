<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ProcessQuoteRequests extends Command
{
    protected $signature = 'process-quote-requests';

    protected $description = 'Process pending quote requests';

    public function handle()
    {
        Artisan::call('queue:work --stop-when-empty');

        $this->info('Quote requests processed successfully.');
    }
}


