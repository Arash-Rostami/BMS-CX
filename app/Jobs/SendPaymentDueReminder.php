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

    /**
     * Create a new job instance.
     */
    public function __construct(){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $financeUsers = User::getUsersByRole('admin');

        // Send a notification to each finance user about each due payment
        foreach ($this->getDuePayments() as $payment) {
            Notification::send($financeUsers, new PaymentDueNotification($payment));
        }
    }

    /**
     * @return mixed
     */
    public function getDuePayments()
    {
        return PaymentRequest::whereIn('status', ['allowed', 'approved', 'processing'])
            ->where('deadline', '>', Carbon::now())
            ->where('deadline', '<=', Carbon::now()->addWeek())
            ->get();
    }
}
