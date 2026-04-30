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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $accountId;
    protected $fromDate;

    public function __construct($accountId, $fromDate)
    {
        $this->accountId = $accountId;
        $this->fromDate = Carbon::parse($fromDate);
    }

    public function handle(InterestCalculationService $calculator): void
    {
        DB::transaction(function () use ($calculator) {
            $account = Account::findOrFail($this->accountId);

            $settlementsToReplay = Settlement::whereHas('ledgerEntry', function ($query) use ($account) {
                $query->where('account_id', $account->id);
            })->where('transaction_date', '>=', $this->fromDate)
              ->orderBy('transaction_date', 'asc')
              ->get();

            $rebuildDate = $this->fromDate->copy()->subDay();

            $ledgersToReset = LedgerEntry::where('account_id', $account->id)
                ->where('transaction_date', '<=', $rebuildDate)
                ->lockForUpdate()
                ->get();

            foreach ($ledgersToReset as $ledger) {
                $ledger->remaining_principal = $ledger->principal_amount_base;
                $ledger->unpaid_interest = 0;
                $ledger->is_settled = false;

                $previousSettlements = Settlement::where('ledger_entry_id', $ledger->id)
                    ->where('transaction_date', '<', $this->fromDate)
                    ->orderBy('transaction_date', 'asc')
                    ->get();

                foreach ($previousSettlements as $settlement) {
                    $ledger->remaining_principal -= $settlement->deducted_from_principal;
                    $ledger->unpaid_interest -= $settlement->deducted_from_interest;
                    $ledger->unpaid_interest += $settlement->accrued_interest_in_period;

                    if ($ledger->remaining_principal <= 0) {
                         $ledger->remaining_principal = 0;
                         $ledger->is_settled = true;
                    }
                }
                $ledger->save();
            }

            Settlement::whereHas('ledgerEntry', function ($query) use ($account) {
                $query->where('account_id', $account->id);
            })->where('transaction_date', '>=', $this->fromDate)->delete();

            foreach ($settlementsToReplay as $settlement) {
                if ($settlement->settlement_amount > 0) {
                    $calculator->processPayment(
                        $account,
                        $settlement->settlement_amount,
                        $settlement->transaction_date->format('Y-m-d'),
                        $settlement->foreign_settlement_amount,
                        $settlement->settlement_exchange_rate
                    );
                }
            }
        });
    }
}
