<?php

namespace App\Observers;

use App\Models\PurchaseStatus;
use App\Services\SmartCacheManager;

class PurchaseStatusObserver
{

    public bool $afterCommit = true;

    public function deleted(PurchaseStatus $purchaseStatus): void
    {
        SmartCacheManager::invalidate('PurchaseStatus');
    }

    public function restored(PurchaseStatus $purchaseStatus): void
    {
        SmartCacheManager::invalidate('PurchaseStatus');
    }

    public function saved(PurchaseStatus $purchaseStatus): void
    {
        SmartCacheManager::invalidate('PurchaseStatus');
    }
}
