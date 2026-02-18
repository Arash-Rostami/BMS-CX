<?php

namespace App\Console\Commands;

use App\Jobs\RefreshAllSupplierSummaries;
use Illuminate\Console\Command;

class RunRefreshSummaryJob extends Command
{
    protected $signature = 'run:refresh-summary';
    protected $description = 'Dispatch RefreshAllSupplierSummaries job';

    public function handle(): void
    {
        RefreshAllSupplierSummaries::dispatch();
        $this->info('RefreshAllSupplierSummaries dispatched successfully.');
    }
}
