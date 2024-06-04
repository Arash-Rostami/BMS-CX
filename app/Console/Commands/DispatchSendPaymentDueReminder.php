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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pendingCount = $this->countPendingRequests();

        if ($pendingCount === 0) {
            $this->info('No pending payment requests due soon.');
            return;
        }

        $this->info("Dispatching jobs for $pendingCount pending payment request(s).");
        Queue::push(new SendPaymentDueReminder());

        $this->processQueue();
        $this->info('Payment requests reminder sent successfully.');
    }

    protected function countPendingRequests()
    {
        return PaymentRequest::whereIn('status', ['allowed', 'approved', 'processing'])
            ->whereBetween('deadline', [Carbon::now(), Carbon::now()->addWeek()])
            ->count();
    }

    protected function processQueue()
    {
        sleep(5);

        Artisan::call('queue:work --stop-when-empty');
    }
}
