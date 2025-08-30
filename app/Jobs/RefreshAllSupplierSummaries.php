<?php

namespace App\Jobs;

use App\Models\ProformaInvoice;
use App\Services\SupplierSummaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshAllSupplierSummaries implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $chunkSize = 30;

    /**
     * Execute the job.
     */
    public function handle(SupplierSummaryService $service): void
    {
        try {
            ProformaInvoice::select('id')->whereNull('deleted_at')->where('status', '!=', 'rejected')
                ->chunkById($this->chunkSize, function ($slice) use ($service) {
                    $proformaIds = $slice->pluck('id')->all();

                    if (empty($proformaIds)) {
                        return;
                    }

                    try {
                        $service->rebuildMany($proformaIds);
                    } catch (Throwable $e) {
                        Log::error('Failed to rebuild supplier summary for batch.', [
                            'proforma_ids' => $proformaIds,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }

                    sleep(2);
                });
        } finally {

            DB::disconnect('mysql');
            gc_collect_cycles();
        }
    }
}
