<?php

namespace App\Observers;

use App\Models\Allocation;
use App\Services\SmartCacheManager;

class AllocationObserver
{

    public bool $afterCommit = true;

    public function created(Allocation $allocation): void
    {
        SmartCacheManager::invalidate('Allocation');
    }

    public function deleted(Allocation $allocation): void
    {
        SmartCacheManager::invalidate('Allocation');
    }

    public function restored(Allocation $allocation): void
    {
        SmartCacheManager::invalidate('Allocation');
    }
}
