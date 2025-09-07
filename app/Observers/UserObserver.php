<?php

namespace App\Observers;

use App\Models\User;
use App\Services\SmartCacheManager;

class UserObserver
{
    public bool $afterCommit = true;

    public function deleted(User $user): void
    {
        SmartCacheManager::invalidate('User');
        User::flushQueryCache();
    }

    public function restored(User $user): void
    {
        SmartCacheManager::invalidate('User');
        User::flushQueryCache();
    }

    public function saved(User $user): void
    {
        SmartCacheManager::invalidate('User');
        User::flushQueryCache();
    }
}
