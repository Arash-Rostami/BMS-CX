<?php

namespace App\Observers;

use App\Models\Grade;
use App\Services\SmartCacheManager;

class GradeObserver
{

    public bool $afterCommit = true;

    public function deleted(Grade $grade): void
    {
        SmartCacheManager::invalidate('Grade');
    }

    public function restored(Grade $grade): void
    {
        SmartCacheManager::invalidate('Grade');
    }

    public function saved(Grade $grade): void
    {
        SmartCacheManager::invalidate('Grade');
    }
}
