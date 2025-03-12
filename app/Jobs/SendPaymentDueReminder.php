<?php

namespace App\Jobs;

use App\Models\PaymentRequest;
use App\Models\User;
use App\Notifications\PaymentDueNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendPaymentDueReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public PaymentRequest $paymentRequest;

    /**
     * Create a new job instance.
     */
    public function __construct(PaymentRequest $paymentRequest)
    {
        $this->paymentRequest = $paymentRequest;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $financeUsers = User::getUsersByRole('admin');

        Notification::send($financeUsers, new PaymentDueNotification($this->paymentRequest));
    }
}
