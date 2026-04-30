<?php

namespace App\Observers;

use App\Models\PaymentRequest;
use App\Services\SmartCacheManager;

class PaymentRequestObserver
{
    public $afterCommit = true;

    public function created(PaymentRequest $paymentRequest): void
    {
        SmartCacheManager::invalidate('PaymentRequest');
    }

    public function updated(PaymentRequest $paymentRequest): void
    {
        SmartCacheManager::invalidate('PaymentRequest');
    }

    public function deleted(PaymentRequest $paymentRequest): void
    {
        SmartCacheManager::invalidate('PaymentRequest');
    }

    public function restored(PaymentRequest $paymentRequest): void
    {
        SmartCacheManager::invalidate('PaymentRequest');
    }
}
