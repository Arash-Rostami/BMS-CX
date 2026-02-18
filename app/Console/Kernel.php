<?php

namespace App\Console;

use App\Jobs\RefreshAllSupplierSummaries;
use App\Jobs\TelexReleaseNotificationJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new TelexReleaseNotificationJob())
            ->at('10:00')
            ->when(fn() => !in_array(now()->dayOfWeek, [4, 5])) // not on Thu and Fri
            ->withoutOverlapping();

//        $schedule->call(function () {
//            \Illuminate\Support\Facades\DB::table('notifications')
//                ->where('created_at', '<', now()->subDays(7))
//                ->chunkById(100, function ($records) {
//                    \Illuminate\Support\Facades\DB::table('notifications')
//                        ->whereIn('id', $records->pluck('id'))
//                        ->delete();
//                });
//        })->dailyAt('03:00');

        $schedule->job(new RefreshAllSupplierSummaries())
            ->cron('0 5 * * *')
            ->withoutOverlapping();
    }
}
