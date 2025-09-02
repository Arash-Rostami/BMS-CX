<?php

namespace App\Observers;

use App\Models\Name;
use App\Services\SmartCacheManager;

class NameObserver
{
    public bool $afterCommit = true;

    public function deleted(Name $name): void
    {
        SmartCacheManager::invalidate('Name');
    }

    public function restored(Name $name): void
    {
        SmartCacheManager::invalidate('Name');
    }

    public function saved(Name $name): void
    {
        SmartCacheManager::invalidate('Name');
    }
}
