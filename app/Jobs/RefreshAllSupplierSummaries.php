<?php

namespace App\Jobs;

use App\Models\ProformaInvoice;
use App\Services\SupplierSummaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshAllSupplierSummaries implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The chunk size for processing.
     *
     * @var int
     */
    public int $chunkSize = 50;


    /**
     * Execute the job.
     */
    public function handle(SupplierSummaryService $service): void
    {
        ProformaInvoice::select('id')
            ->chunk($this->chunkSize, function ($slice) use ($service) {
                foreach ($slice as $pi) {
                    $service->rebuild($pi->id);
                }
                sleep(2);
            });
    }
}
