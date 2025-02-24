<?php

namespace App\Jobs;

use App\Services\Notification\SMS\Operator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelexReleaseNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $records = DB::table('proforma_invoices as pi')
            ->join('orders as o', 'pi.id', '=', 'o.proforma_invoice_id')
            ->leftJoin('suppliers as s', 'pi.supplier_id', '=', 's.id')
            ->leftJoin('users as u', 'pi.assignee_id', '=', 'u.id')
            ->whereNotNull('pi.assignee_id')
            ->whereRaw("
                EXISTS (
                    SELECT 1
                    FROM payment_requests pr
                    INNER JOIN payment_payment_request ppr ON pr.id = ppr.payment_request_id
                    INNER JOIN payments p ON ppr.payment_id = p.id
                    WHERE pr.status = 'completed'
                    AND pr.type_of_payment = 'balance'
                    AND pr.deleted_at IS NULL
                    AND p.deleted_at IS NULL
                    AND p.date BETWEEN CURDATE() - INTERVAL 10 DAY AND CURDATE() - INTERVAL 3 DAY
                    AND pr.order_id = o.id
                    GROUP BY pr.id, pr.requested_amount
                    HAVING SUM(p.amount) >= pr.requested_amount
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM attachments a
                    WHERE a.order_id = o.id
                    AND LOWER(a.name) LIKE '%telex-release%'
                )
            ")
            ->select(
                'pi.proforma_number',
                'pi.reference_number as proforma_reference',
                'o.reference_number as order_reference',
                'o.part as part',
                's.name as supplier_name',
                'u.phone as user_phone'
            )
            ->get();

        if ($records->isEmpty()) {
            Log::channel('telex_notification')->info('TelexNotificationJob: No records found.');
            return;
        }

        // Build a single aggregated message
        $message = "BMS Notification Service\n\nTelex Release Documents Needed:\n\n";

        foreach ($records as $record) {
            $message .= "Proforma Invoice: {$record->proforma_number} (Ref: {$record->proforma_reference})\n";
            $message .= "- Part {$record->part} - {$record->supplier_name} (Ref: {$record->order_reference})\n\n";
        }

        $firstRecord = $records->first();
        $operator = new Operator();
        $operator->send($firstRecord->user_phone, $message);
        Log::channel('telex_notification')->info('TelexNotificationJob sent successfully for ' . $firstRecord->user_phone);
    }
}
