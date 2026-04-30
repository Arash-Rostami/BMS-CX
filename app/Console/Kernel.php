<?php

namespace App\Console;

use App\Jobs\RefreshAllSupplierSummaries;
use App\Jobs\TelexReleaseNotificationJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }

    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new TelexReleaseNotificationJob())
            ->at('10:00')
            ->when(fn() => !in_array(now()->dayOfWeek, [4, 5]))
            ->withoutOverlapping();

        $schedule->call(function () {
            DB::table('notifications')
                ->where('created_at', '<', now()->subDays(7))
                ->delete();
        })->dailyAt('03:00');

        $schedule->job(new RefreshAllSupplierSummaries())
            ->dailyAt('05:00')
            ->withoutOverlapping();

        $schedule->job(new RefreshAllSupplierSummaries())
            ->at('17:10') // Changed from 05:00 to 15:10 for testing
            ->withoutOverlapping();
    }
}
