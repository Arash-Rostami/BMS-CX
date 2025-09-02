<?php

namespace App\Observers;

use App\Models\PaymentRequest;
use App\Services\SmartCacheManager;

class PaymentRequestObserver
{
    public $afterCommit = true;

    public function deleted(PaymentRequest $paymentRequest): void
    {
        SmartCacheManager::invalidate('PaymentRequest');
    }

    public function restored(PaymentRequest $paymentRequest): void
    {
        SmartCacheManager::invalidate('PaymentRequest');
    }

    public function saved(PaymentRequest $paymentRequest): void
    {
        SmartCacheManager::invalidate('PaymentRequest');
    }
}
