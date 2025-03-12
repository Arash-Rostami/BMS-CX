<?php

namespace App\Console\Commands;

use App\Jobs\SendPaymentDueReminder;
use App\Models\PaymentRequest;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

class DispatchSendPaymentDueReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispatch-send-payment-due-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatching due payment request reminders.';


    public function handle()
    {
        $pendingRequests = $this->getPendingRequests();
        $pendingCount = $pendingRequests->count();

        if ($pendingCount === 0) {
            $this->info('No pending payment requests due soon.');
            return 0;
        }

        $this->info("Dispatching jobs for $pendingCount pending payment request(s).");

        foreach ($pendingRequests as $paymentRequest) {
            SendPaymentDueReminder::dispatch($paymentRequest);
        }

        $this->info('Payment request reminders dispatched successfully to the queue.');
        return 0;
    }

    protected function getPendingRequests()
    {
        return PaymentRequest::query()
            ->whereIn('status', ['allowed', 'approved', 'processing'])
            ->where('deadline', '>', Carbon::now())
            ->where('deadline', '<=', Carbon::now()->addWeek())
            ->get();
    }
}
