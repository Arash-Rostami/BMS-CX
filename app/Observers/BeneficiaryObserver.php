<?php

namespace App\Observers;

use App\Models\Beneficiary;
use App\Services\SmartCacheManager;

class BeneficiaryObserver
{

    public bool $afterCommit = true;

    public function deleted(Beneficiary $beneficiary): void
    {
        SmartCacheManager::invalidate('Beneficiary');
        Beneficiary::flushQueryCache();
    }

    public function restored(Beneficiary $beneficiary): void
    {
        SmartCacheManager::invalidate('Beneficiary');
    }

    public function saved(Beneficiary $beneficiary): void
    {
        SmartCacheManager::invalidate('Beneficiary');
        Beneficiary::flushQueryCache();
    }
}
