<?php

namespace App\Observers;

use App\Models\PortOfDelivery;
use App\Services\SmartCacheManager;

class PortOfDeliveryObserver
{
    public bool $afterCommit = true;

    public function saved(PortOfDelivery $portOfDelivery): void
    {
        SmartCacheManager::invalidate('PortOfDelivery');
    }

    public function deleted(PortOfDelivery $portOfDelivery): void
    {
        SmartCacheManager::invalidate('PortOfDelivery');
    }

    public function restored(PortOfDelivery $portOfDelivery): void
    {
        SmartCacheManager::invalidate('PortOfDelivery');
    }
}
