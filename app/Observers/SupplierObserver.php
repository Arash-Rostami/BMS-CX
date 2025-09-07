<?php

namespace App\Observers;

use App\Models\Supplier;
use App\Services\SmartCacheManager;

class SupplierObserver
{
    public bool $afterCommit = true;

    public function deleted(Supplier $supplier): void
    {
        SmartCacheManager::invalidate('Supplier');
        Supplier::flushQueryCache();
    }

    public function restored(Supplier $supplier): void
    {
        SmartCacheManager::invalidate('Supplier');
        Supplier::flushQueryCache();
    }

    public function saved(Supplier $supplier): void
    {
        SmartCacheManager::invalidate('Supplier');
        Supplier::flushQueryCache();
    }
}
