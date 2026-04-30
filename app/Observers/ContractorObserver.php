<?php

namespace App\Observers;

use App\Models\Contractor;
use App\Services\SmartCacheManager;

class ContractorObserver
{
    public bool $afterCommit = true;

    public function deleted(Contractor $contractor): void
    {
        SmartCacheManager::invalidate('Contractor');
    }

    public function restored(Contractor $contractor): void
    {
        SmartCacheManager::invalidate('Contractor');
    }

    public function saved(Contractor $contractor): void
    {
        SmartCacheManager::invalidate('Contractor');
    }
}
