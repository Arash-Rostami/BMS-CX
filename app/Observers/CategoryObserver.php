<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\SmartCacheManager;

class CategoryObserver
{
    public bool $afterCommit = true;

    public function deleted(Category $category): void
    {
        SmartCacheManager::invalidate('Category');

    }

    public function restored(Category $category): void
    {
        SmartCacheManager::invalidate('v');

    }

    public function saved(Category $category): void
    {
        SmartCacheManager::invalidate('Category');
    }
}
