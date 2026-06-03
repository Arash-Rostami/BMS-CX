<?php

$content = <<<'NEWCODE'
<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Settlement;
use App\Services\InterestCalculationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class RecalculateAccountLedger implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected int $accountId;

    public function __construct($accountId)
    {
        $this->accountId = (int)$accountId;
    }

    public function handle(InterestCalculationService $calculator): void
    {
        DB::transaction(function () use ($calculator) {
            $account = Account::findOrFail($this->accountId);

            $allSettlements = Settlement::query()
                ->whereHas('ledgerEntry', fn($q) => $q->where('account_id', $account->id))
                ->orderBy('transaction_date', 'asc')
                ->get();

            $paymentsToReplay = [];

            foreach ($allSettlements as $s) {
                $dateKey = $s->transaction_date->format('Y-m-d');
                $currency = $s->currency_type ?? 'USD';
                $rate = (float)$s->settlement_exchange_rate;
                $key = $dateKey . '_' . $currency . '_' . $rate;

                if (!isset($paymentsToReplay[$key])) {
                    $paymentsToReplay[$key] = [
                        'date' => $dateKey,
                        'amount' => 0.0,
                        'foreign_amount' => 0.0,
                        'currency' => $currency,
                        'rate' => $s->settlement_exchange_rate,
                    ];
                }

                $paymentsToReplay[$key]['amount'] += (float)$s->settlement_amount;
                if ($s->foreign_settlement_amount !== null) {
                    $paymentsToReplay[$key]['foreign_amount'] += (float)$s->foreign_settlement_amount;
                }
            }

            $overpayments = LedgerEntry::where('account_id', $account->id)
                ->where('type', 'receipt')
                ->where('description', InterestCalculationService::OVERPAYMENT_DESCRIPTION)
                ->get();

            foreach ($overpayments as $op) {
                $dateKey = $op->transaction_date->format('Y-m-d');
                $currency = $op->currency_type ?? 'USD';
                $rate = (float)$op->exchange_rate;
                $key = $dateKey . '_' . $currency . '_' . $rate;

                if (!isset($paymentsToReplay[$key])) {
                    $paymentsToReplay[$key] = [
                        'date' => $dateKey,
                        'amount' => 0.0,
                        'foreign_amount' => 0.0,
                        'currency' => $currency,
                        'rate' => $op->exchange_rate,
                    ];
                }

                $paymentsToReplay[$key]['amount'] += (float)$op->total_disbursed_base;
                if ($op->amount_foreign_currency !== null) {
                    $paymentsToReplay[$key]['foreign_amount'] += (float)$op->amount_foreign_currency;
                }
            }

            usort($paymentsToReplay, function($a, $b) {
                return strcmp($a['date'], $b['date']);
            });

            Settlement::whereHas('ledgerEntry', fn($q) => $q->where('account_id', $account->id))->delete();
            LedgerEntry::where('account_id', $account->id)
                ->where('type', 'receipt')
                ->where('description', InterestCalculationService::OVERPAYMENT_DESCRIPTION)
                ->delete();

            $allLedgers = LedgerEntry::where('account_id', $account->id)
                ->lockForUpdate()
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($allLedgers as $ledger) {
                if ($ledger->type === 'receipt') {
                    $ledger->remaining_principal = -(float)$ledger->total_disbursed_base;
                } else {
                    $ledger->remaining_principal = (float)$ledger->total_disbursed_base;
                    $ledger->applied_credit_amount = 0;
                }
                $ledger->unpaid_interest = 0;
                $ledger->is_settled = false;
                $ledger->save();
            }

            $disbursements = LedgerEntry::where('account_id', $account->id)
                ->where('type', 'disbursement')
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($disbursements as $disbursement) {
                $data = $disbursement->toArray();

                $calculator->applyDisbursementCredit($account, $data);

                if (isset($data['applied_credit_amount'])) {
                     $disbursement->applied_credit_amount = $data['applied_credit_amount'];
                     $disbursement->remaining_principal = $data['remaining_principal'];
                     $disbursement->unpaid_interest = $data['unpaid_interest'] ?? 0;
                     $disbursement->is_settled = $data['remaining_principal'] <= 0;
                     $disbursement->save();
                }
            }

            foreach ($paymentsToReplay as $payment) {
                if ($payment['amount'] <= 0) continue;

                $calculator->processPayment(
                    $account,
                    (float)$payment['amount'],
                    $payment['date'],
                    $payment['foreign_amount'] > 0 ? $payment['foreign_amount'] : null,
                    $payment['rate'],
                    $payment['currency'],
                    null
                );
            }
        });
    }
}
NEWCODE;

file_put_contents('app/Jobs/RecalculateAccountLedger.php', $content);
