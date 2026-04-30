<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\SmartCacheManager;

class OrderObserver
{
    public $afterCommit = true;

    public function deleted(Order $order): void
    {
        SmartCacheManager::invalidate('Order');
    }

    public function restored(Order $order): void
    {
        SmartCacheManager::invalidate('Order');
    }

    public function saved(Order $order): void
    {
        SmartCacheManager::invalidate('Order');
    }
}
