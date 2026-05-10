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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecalculateAccountLedger implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected int $accountId;
    protected Carbon $fromDate;
    protected Carbon $rebuildDate;

    public function __construct($accountId, $fromDate)
    {
        $this->accountId = (int)$accountId;
        $this->fromDate = Carbon::parse($fromDate);
        $this->rebuildDate = $this->fromDate->copy()->subDay();
    }

    public function handle(InterestCalculationService $calculator): void
    {
        DB::transaction(function () use ($calculator) {
            $account = Account::findOrFail($this->accountId);

            $settlementsToReplay = Settlement::query()
                ->whereHas('ledgerEntry', fn($q) => $q->where('account_id', $account->id))
                ->where('transaction_date', '>=', $this->fromDate)
                ->orderBy('transaction_date', 'asc')
                ->get();

            // ── Group 1: ledgers born BEFORE fromDate ─────────────────────────
            $ledgersToReset = LedgerEntry::where('account_id', $account->id)
                ->where('transaction_date', '<=', $this->rebuildDate)
                ->lockForUpdate()
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $previousSettlements = $this->loadPreviousSettlements(
                $account->id,
                $ledgersToReset->pluck('id')->all()
            );
            $this->resetLedgers($ledgersToReset, $previousSettlements);

            // ── Group 2: ledgers born WITHIN the replay window ────────────────
            $replayWindowLedgers = LedgerEntry::where('account_id', $account->id)
                ->where('transaction_date', '>=', $this->fromDate)
                ->where('description', '!=', InterestCalculationService::OVERPAYMENT_DESCRIPTION)
                ->lockForUpdate()
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $this->resetLedgers($replayWindowLedgers, collect());

            // ── Re-apply credits + pre-credit interest across both groups ──────
            $this->applyDisbursementCredits(
                $account->id,
                $ledgersToReset->merge($replayWindowLedgers),
                $calculator
            );

            $this->purgeAndReplay($account, $calculator, $settlementsToReplay);
        });
    }

    protected function applyDisbursementCredits(int $accountId, Collection $allLedgers, InterestCalculationService $calculator): void
    {
        $disbursements = $allLedgers
            ->where('type', 'disbursement')
            ->sortBy('transaction_date')
            ->values();

        if ($disbursements->isEmpty()) return;

        $allReceipts = LedgerEntry::where('account_id', $accountId)
            ->where('type', 'receipt')
            ->where(function ($q) {
                $q->where('description', '!=', InterestCalculationService::OVERPAYMENT_DESCRIPTION)
                    ->orWhere('transaction_date', '<', $this->fromDate);
            })
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($disbursements as $disbursement) {
            $disbursementDate = Carbon::parse($disbursement->transaction_date);
            $startingRemaining = (float)$disbursement->remaining_principal;
            $remaining = $startingRemaining;
            $preCreditInterest = 0.0;

            foreach ($allReceipts as $receipt) {
                if ($remaining <= 0) break;
                if ($receipt->transaction_date > $disbursementDate) continue;
                if ($receipt->is_settled || $receipt->remaining_principal >= 0) continue;

                $available = abs($receipt->remaining_principal);
                $consume = min($available, $remaining);

                // Counter-interest on consumed amount for [receipt_date → disbursement_date]
                $receiptDate = Carbon::parse($receipt->transaction_date);
                $daysHeld = $receiptDate->diffInDays($disbursementDate, false);

                if ($daysHeld > 0) {
                    $preCreditInterest += $calculator->calculateInterest(
                        $consume,
                        0,
                        (int)$daysHeld,
                        (array)$receipt->rate_matrix_snapshot
                    );
                }

                $receipt->remaining_principal += $consume;
                if ($receipt->remaining_principal >= 0) {
                    $receipt->remaining_principal = 0;
                    $receipt->is_settled = true;
                }
                $receipt->save();
                $remaining -= $consume;
            }

            $applied = $startingRemaining - $remaining;
            $disbursement->applied_credit_amount = $applied;
            $disbursement->remaining_principal = $remaining;
            $disbursement->is_settled = $remaining <= 0;
            // Store pre-credit interest as negative unpaid_interest for processPayment to consume
            $disbursement->unpaid_interest = $preCreditInterest > 0 ? -$preCreditInterest : 0.0;
            $disbursement->save();
        }
    }

    protected function loadPreviousSettlements(int $accountId, array $ledgerIds): Collection
    {
        if (empty($ledgerIds)) return collect();

        return Settlement::query()
            ->whereIn('ledger_entry_id', $ledgerIds)
            ->where('transaction_date', '<', $this->fromDate)
            ->orderBy('transaction_date', 'asc')
            ->get()
            ->groupBy('ledger_entry_id');
    }

    protected function purgeAndReplay(Account $account, InterestCalculationService $calculator, Collection $settlementsToReplay): void
    {
        LedgerEntry::where('account_id', $account->id)
            ->where('type', 'receipt')
            ->where('description', InterestCalculationService::OVERPAYMENT_DESCRIPTION)
            ->where('transaction_date', '>=', $this->fromDate)
            ->delete();

        Settlement::whereHas('ledgerEntry', fn($q) => $q->where('account_id', $account->id))
            ->where('transaction_date', '>=', $this->fromDate)
            ->delete();

        foreach ($settlementsToReplay as $settlement) {
            if ($settlement->settlement_amount <= 0) continue;

            $calculator->processPayment(
                $account,
                (float)$settlement->settlement_amount,
                $settlement->transaction_date->format('Y-m-d'),
                $settlement->foreign_settlement_amount !== null ? (float)$settlement->foreign_settlement_amount : null,
                $settlement->settlement_exchange_rate !== null ? (float)$settlement->settlement_exchange_rate : null,
                $settlement->currency_type ?? 'USD',
                null,
            );
        }
    }

    protected function resetLedgers(Collection $ledgers, Collection $groupedSettlements): void
    {
        foreach ($ledgers as $ledger) {
            if ($ledger->type === 'receipt') {
                $ledger->remaining_principal = -$ledger->total_disbursed_base;
            } else {
                $ledger->remaining_principal = $ledger->total_disbursed_base;
                $ledger->applied_credit_amount = 0;
            }
            $ledger->unpaid_interest = 0;
            $ledger->is_settled = false;

            foreach ($groupedSettlements->get($ledger->id, collect()) as $settlement) {
                $ledger->remaining_principal -= $settlement->deducted_from_principal;
                $ledger->unpaid_interest -= $settlement->deducted_from_interest;
                $ledger->unpaid_interest += $settlement->accrued_interest_in_period
                    - $settlement->counter_interest_applied;


                if ($ledger->unpaid_interest < 0) {
                    $excess = abs($ledger->unpaid_interest);
                    $ledger->remaining_principal = max(0, $ledger->remaining_principal - $excess);
                    $ledger->unpaid_interest = 0;

                    if ($ledger->remaining_principal <= 0) {
                        $ledger->remaining_principal = 0;
                        $ledger->is_settled = true;
                    }
                }

                if ($ledger->remaining_principal <= 0 && $ledger->unpaid_interest <= 0) {
                    $ledger->remaining_principal = 0;
                    $ledger->is_settled = true;
                }
            }
            $ledger->save();
        }
    }
}
