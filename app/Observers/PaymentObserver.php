<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\SmartCacheManager;

class PaymentObserver
{
    /**
     *
     * @var bool
     */
    public $afterCommit = true;

    public function deleted(Payment $payment): void
    {
        SmartCacheManager::invalidate('Payment');
    }

    public function restored(Payment $payment): void
    {
        SmartCacheManager::invalidate('Payment');
    }

    public function saved(Payment $payment): void
    {
        SmartCacheManager::invalidate('Payment');
    }
}
