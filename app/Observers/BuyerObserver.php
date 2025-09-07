<?php

namespace App\Observers;

use App\Models\Buyer;
use App\Services\SmartCacheManager;

class BuyerObserver
{

    public bool $afterCommit = true;

    public function deleted(Buyer $buyer): void
    {
        SmartCacheManager::invalidate('Buyer');
        Buyer::flushQueryCache();
    }

    public function restored(Buyer $buyer): void
    {
        SmartCacheManager::invalidate('Buyer');
        Buyer::flushQueryCache();
    }

    public function saved(Buyer $buyer): void
    {
        SmartCacheManager::invalidate('Buyer');
        Buyer::flushQueryCache();
    }
}
