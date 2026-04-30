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
    }

    public function restored(Supplier $supplier): void
    {
        SmartCacheManager::invalidate('Supplier');
    }

    public function saved(Supplier $supplier): void
    {
        SmartCacheManager::invalidate('Supplier');
    }
}
