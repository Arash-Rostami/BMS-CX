<?php

namespace App\Console;

use App\Jobs\TelexReleaseNotificationJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new TelexReleaseNotificationJob)
            ->at('10:00')
            ->when(function () {
                $dayOfWeek = now()->dayOfWeek;
                return $dayOfWeek !== 4 && $dayOfWeek !== 5;
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
