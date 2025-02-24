<?php

namespace App\Console\Commands;

use App\Jobs\TelexReleaseNotificationJob;
use Illuminate\Console\Command;

class RunTelexNotificationJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-telex-notification-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch the TelexNotificationJob';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        TelexReleaseNotificationJob::dispatch();
        $this->info('TelexNotificationJob dispatched successfully.');
    }
}
