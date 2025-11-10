<?php

namespace App\Observers;

use App\Models\ProformaInvoice;
use App\Services\SmartCacheManager;

class ProformaInvoiceObserver
{
    /**
     * Handle events after all transactions are committed.
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the ProformaInvoice "deleted" event.
     */
    public function deleted(ProformaInvoice $proformaInvoice): void
    {
        SmartCacheManager::invalidate('ProformaInvoice');
        ProformaInvoice::flushQueryCache();
    }

    /**
     * Handle the ProformaInvoice "restored" event.
     */
    public function restored(ProformaInvoice $proformaInvoice): void
    {
        SmartCacheManager::invalidate('ProformaInvoice');
        ProformaInvoice::flushQueryCache();
    }

    /**
     * Handle the ProformaInvoice "saved" event.
     */
    public function saved(ProformaInvoice $proformaInvoice): void
    {
        SmartCacheManager::invalidate('ProformaInvoice');
        ProformaInvoice::flushQueryCache();
    }
}
