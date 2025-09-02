<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\SmartCacheManager;

class ProductObserver
{
    public bool $afterCommit = true;

    public function deleted(Product $product): void
    {
        SmartCacheManager::invalidate('Product');

    }

    public function restored(Product $product): void
    {
        SmartCacheManager::invalidate('Product');

    }

    public function saved(Product $product): void
    {
        SmartCacheManager::invalidate('Product');

    }
}
