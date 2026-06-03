<?php
//
//namespace App\Jobs;
//
//use App\Models\Account;
//use App\Models\LedgerEntry;
//use App\Models\Settlement;
//use App\Services\InterestCalculationService;
//use Carbon\Carbon;
//use Illuminate\Bus\Queueable;
//use Illuminate\Contracts\Queue\ShouldQueue;
//use Illuminate\Foundation\Bus\Dispatchable;
//use Illuminate\Queue\InteractsWithQueue;
//use Illuminate\Queue\SerializesModels;
//use Illuminate\Support\Collection;
//use Illuminate\Support\Facades\DB;
//
//class RecalculateAccountLedger implements ShouldQueue
//{
//    use Dispatchable;
//    use InteractsWithQueue;
//    use Queueable;
//    use SerializesModels;
//
//    protected int $accountId;
//    protected Carbon $fromDate;
//    protected Carbon $rebuildDate;
//
//    public function __construct($accountId, $fromDate)
//    {
//        $this->accountId = (int)$accountId;
//        $this->fromDate = Carbon::parse($fromDate);
//        $this->rebuildDate = $this->fromDate->copy()->subDay();
//    }
//
//    public function handle(InterestCalculationService $calculator): void
//    {
//        DB::transaction(function () use ($calculator) {
//            $account = Account::findOrFail($this->accountId);
//
//            $settlementsToReplay = Settlement::query()
//                ->whereHas('ledgerEntry', fn($q) => $q->where('account_id', $account->id))
//                ->where('transaction_date', '>=', $this->fromDate)
//                ->orderBy('transaction_date', 'asc')
//                ->get();
//
//            // ── Group 1: ledgers born BEFORE fromDate ─────────────────────────
//            $ledgersToReset = LedgerEntry::where('account_id', $account->id)
//                ->where('transaction_date', '<=', $this->rebuildDate)
//                ->lockForUpdate()
//                ->orderBy('transaction_date', 'asc')
//                ->orderBy('id', 'asc')
//                ->get();
//
//            $previousSettlements = $this->loadPreviousSettlements(
//                $account->id,
//                $ledgersToReset->pluck('id')->all()
//            );
//            $this->resetLedgers($ledgersToReset, $previousSettlements);
//
//            // ── Group 2: ledgers born WITHIN the replay window ────────────────
//            $replayWindowLedgers = LedgerEntry::where('account_id', $account->id)
//                ->where('transaction_date', '>=', $this->fromDate)
//                ->where('description', '!=', InterestCalculationService::OVERPAYMENT_DESCRIPTION)
//                ->lockForUpdate()
//                ->orderBy('transaction_date', 'asc')
//                ->orderBy('id', 'asc')
//                ->get();
//
//            $this->resetLedgers($replayWindowLedgers, collect());
//
//            // ── Re-apply credits + pre-credit interest across both groups ──────
//            $this->applyDisbursementCredits(
//                $account->id,
//                $ledgersToReset->merge($replayWindowLedgers),
//                $calculator
//            );
//
//            $this->purgeAndReplay($account, $calculator, $settlementsToReplay);
//        });
//    }
//
//    protected function applyDisbursementCredits(int $accountId, Collection $allLedgers, InterestCalculationService $calculator): void
//    {
//        $disbursements = $allLedgers
//            ->where('type', 'disbursement')
//            ->sortBy('transaction_date')
//            ->values();
//
//        if ($disbursements->isEmpty()) return;
//
//        $allReceipts = LedgerEntry::where('account_id', $accountId)
//            ->where('type', 'receipt')
//            ->where(function ($q) {
//                $q->where('description', '!=', InterestCalculationService::OVERPAYMENT_DESCRIPTION)
//                    ->orWhere('transaction_date', '<', $this->fromDate);
//            })
//            ->orderBy('transaction_date', 'asc')
//            ->orderBy('id', 'asc')
//            ->lockForUpdate()
//            ->get();
//
//        foreach ($disbursements as $disbursement) {
//            $disbursementDate = Carbon::parse($disbursement->transaction_date);
//            $startingRemaining = (float)$disbursement->remaining_principal;
//            $remaining = $startingRemaining;
//            $preCreditInterest = 0.0;
//
//            foreach ($allReceipts as $receipt) {
//                if ($remaining <= 0) break;
//                if ($receipt->transaction_date > $disbursementDate) continue;
//                if ($receipt->is_settled || $receipt->remaining_principal >= 0) continue;
//
//                $available = abs($receipt->remaining_principal);
//                $consume = min($available, $remaining);
//
//                // Counter-interest on consumed amount for [receipt_date → disbursement_date]
//                $receiptDate = Carbon::parse($receipt->transaction_date);
//                $daysHeld = $receiptDate->diffInDays($disbursementDate, false);
//
//                if ($daysHeld > 0) {
//                    $preCreditInterest += $calculator->calculateInterest(
//                        $consume,
//                        0,
//                        (int)$daysHeld,
//                        (array)$receipt->rate_matrix_snapshot
//                    );
//                }
//
//                $receipt->remaining_principal += $consume;
//                if ($receipt->remaining_principal >= 0) {
//                    $receipt->remaining_principal = 0;
//                    $receipt->is_settled = true;
//                }
//                $receipt->save();
//                $remaining -= $consume;
//            }
//
//            $applied = $startingRemaining - $remaining;
//            $disbursement->applied_credit_amount = $applied;
//            $disbursement->remaining_principal = $remaining;
//            $disbursement->is_settled = $remaining <= 0;
//            // Store pre-credit interest as negative unpaid_interest for processPayment to consume
//            $disbursement->unpaid_interest = $preCreditInterest > 0 ? -$preCreditInterest : 0.0;
//            $disbursement->save();
//        }
//    }
//
//    protected function loadPreviousSettlements(int $accountId, array $ledgerIds): Collection
//    {
//        if (empty($ledgerIds)) return collect();
//
//        return Settlement::query()
//            ->whereIn('ledger_entry_id', $ledgerIds)
//            ->where('transaction_date', '<', $this->fromDate)
//            ->orderBy('transaction_date', 'asc')
//            ->get()
//            ->groupBy('ledger_entry_id');
//    }
//
//    protected function purgeAndReplay(Account $account, InterestCalculationService $calculator, Collection $settlementsToReplay): void
//    {
//        LedgerEntry::where('account_id', $account->id)
//            ->where('type', 'receipt')
//            ->where('description', InterestCalculationService::OVERPAYMENT_DESCRIPTION)
//            ->where('transaction_date', '>=', $this->fromDate)
//            ->delete();
//
//        Settlement::whereHas('ledgerEntry', fn($q) => $q->where('account_id', $account->id))
//            ->where('transaction_date', '>=', $this->fromDate)
//            ->delete();
//
//        foreach ($settlementsToReplay as $settlement) {
//            if ($settlement->settlement_amount <= 0) continue;
//
//            $calculator->processPayment(
//                $account,
//                (float)$settlement->settlement_amount,
//                $settlement->transaction_date->format('Y-m-d'),
//                $settlement->foreign_settlement_amount !== null ? (float)$settlement->foreign_settlement_amount : null,
//                $settlement->settlement_exchange_rate !== null ? (float)$settlement->settlement_exchange_rate : null,
//                $settlement->currency_type ?? 'USD',
//                null,
//            );
//        }
//    }
//
//    protected function resetLedgers(Collection $ledgers, Collection $groupedSettlements): void
//    {
//        foreach ($ledgers as $ledger) {
//            if ($ledger->type === 'receipt') {
//                $ledger->remaining_principal = -$ledger->total_disbursed_base;
//            } else {
//                $ledger->remaining_principal = $ledger->total_disbursed_base;
//                $ledger->applied_credit_amount = 0;
//            }
//            $ledger->unpaid_interest = 0;
//            $ledger->is_settled = false;
//
//            foreach ($groupedSettlements->get($ledger->id, collect()) as $settlement) {
//                $ledger->remaining_principal -= $settlement->deducted_from_principal;
//                $ledger->unpaid_interest -= $settlement->deducted_from_interest;
//                $ledger->unpaid_interest += $settlement->accrued_interest_in_period
//                    - $settlement->counter_interest_applied;
//
//
//                if ($ledger->unpaid_interest < 0) {
//                    $excess = abs($ledger->unpaid_interest);
//                    $ledger->remaining_principal = max(0, $ledger->remaining_principal - $excess);
//                    $ledger->unpaid_interest = 0;
//
//                    if ($ledger->remaining_principal <= 0) {
//                        $ledger->remaining_principal = 0;
//                        $ledger->is_settled = true;
//                    }
//                }
//
//                if ($ledger->remaining_principal <= 0 && $ledger->unpaid_interest <= 0) {
//                    $ledger->remaining_principal = 0;
//                    $ledger->is_settled = true;
//                }
//            }
//            $ledger->save();
//        }
//    }
//}


namespace App\Jobs;

use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Settlement;
use App\Services\InterestCalculationService;
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

    /**
     * This job intentionally performs a full-account replay because the current
     * schema stores settlements as generated per-ledger output rows, not as
     * first-class source payment events.
     *
     * Disbursement credit-application and payment-replay are interleaved in
     * chronological order so that credit which only exists *after* a payment
     * (overpayment spillover) is available to a later disbursement,
     */
    protected string $fromDate;

    public function __construct(int $accountId, string $fromDate)
    {
        $this->accountId = $accountId;
        $this->fromDate = $fromDate;
    }

    public function handle(InterestCalculationService $calculator): void
    {
        DB::transaction(function () use ($calculator): void {
            $account = Account::findOrFail($this->accountId);

            $paymentsToReplay = $this->collectReplayPayments($account);

            $this->purgeGeneratedRows($account);
            $this->resetManualReceipts($account);
            $this->resetDisbursements($account);

            $this->replayTimeline($account, $calculator, $paymentsToReplay);
        });
    }

    /**
     * Reconstruct payment-like replay events from generated settlement rows and
     * generated overpayment receipt rows.
     */
    protected function collectReplayPayments(Account $account): array
    {
        $payments = [];

        $settlements = Settlement::query()
            ->whereHas('ledgerEntry', fn($q) => $q->where('account_id', $account->id))
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($settlements as $settlement) {
            $date = $settlement->transaction_date->format('Y-m-d');
            $currency = $settlement->currency_type ?? config('financial.base_currency', 'USD');
            $rate = $settlement->settlement_exchange_rate !== null
                ? (float)$settlement->settlement_exchange_rate
                : null;

            // Key on (date, currency) only
            $key = $this->paymentKey($date, $currency);

            if (!isset($payments[$key])) {
                $payments[$key] = [
                    'date' => $date,
                    'amount' => 0.0,
                    'currency' => $currency,
                    'rate' => null,
                    'foreign_values' => [],
                ];
            }

            $payments[$key]['amount'] += (float)$settlement->settlement_amount;

            if ($rate !== null && $payments[$key]['rate'] === null) {
                $payments[$key]['rate'] = $rate;
            }

            if ($settlement->foreign_settlement_amount !== null) {
                $foreign = round((float)$settlement->foreign_settlement_amount, 6);
                $payments[$key]['foreign_values'][(string)$foreign] = $foreign;
            }
        }

        $overpayments = LedgerEntry::query()
            ->where('account_id', $account->id)
            ->where('type', 'receipt')
            ->where('description', InterestCalculationService::OVERPAYMENT_DESCRIPTION)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($overpayments as $overpayment) {
            $date = $overpayment->transaction_date->format('Y-m-d');
            $currency = $overpayment->currency_type ?? config('financial.base_currency', 'USD');

            // Overpayment receipts are always created base-currency with exchange_rate=1
            // and amount_foreign_currency = the base spillover. Neither is a genuine
            // foreign conversion, so do NOT key/derive rate or foreign from them — just
            // fold their base amount into the same-day payment.
            $key = $this->paymentKey($date, $currency);

            if (!isset($payments[$key])) {
                $payments[$key] = [
                    'date' => $date,
                    'amount' => 0.0,
                    'currency' => $currency,
                    'rate' => null,
                    'foreign_values' => [],
                ];
            }

            $payments[$key]['amount'] += (float)$overpayment->total_disbursed_base;
        }

        $payments = array_values(array_map(function (array $payment): array {
            $foreignValues = array_values($payment['foreign_values']);

            if (count($foreignValues) === 0) {
                $payment['foreign_amount'] = null;
            } elseif (count($foreignValues) === 1) {
                // Avoid multiplying one original foreign amount that was duplicated across multiple generated settlement rows.
                $payment['foreign_amount'] = $foreignValues[0];
            } else {
                // Best-effort fallback for ambiguous same-day/same-rate groups.
                $payment['foreign_amount'] = array_sum($foreignValues);
            }

            unset($payment['foreign_values']);

            return $payment;
        }, $payments));

        usort(
            $payments,
            fn(array $a, array $b): int => [$a['date'], $a['currency']]
                <=> [$b['date'], $b['currency']]
        );

        return $payments;
    }

    protected function purgeGeneratedRows(Account $account): void
    {
        Settlement::query()
            ->whereHas('ledgerEntry', fn($q) => $q->where('account_id', $account->id))
            ->delete();

        LedgerEntry::query()
            ->where('account_id', $account->id)
            ->where('type', 'receipt')
            ->where('description', InterestCalculationService::OVERPAYMENT_DESCRIPTION)
            ->delete();
    }

    protected function resetManualReceipts(Account $account): void
    {
        DB::table('ledger_entries')
            ->where('account_id', $account->id)
            ->where('type', 'receipt')
            ->where('description', '!=', InterestCalculationService::OVERPAYMENT_DESCRIPTION)
            ->update([
                'remaining_principal' => DB::raw('-1 * total_disbursed_base'),
                'applied_credit_amount' => 0,
                'unpaid_interest' => 0,
                'pre_credit_interest' => 0,
                'is_settled' => false,
            ]);
    }

    protected function resetDisbursements(Account $account): void
    {
        DB::table('ledger_entries')
            ->where('account_id', $account->id)
            ->where('type', 'disbursement')
            ->update([
                'remaining_principal' => DB::raw('total_disbursed_base'),
                'applied_credit_amount' => 0,
                'unpaid_interest' => 0,
                'pre_credit_interest' => 0,
                'is_settled' => false,
            ]);
    }

    /**
     * Replay disbursement-credit events and payment events in a single
     * chronological stream. On the same date, disbursements are applied before
     * payments — matching the account_statements view's sort_order (disbursement = 1, settlement = 2)
     */
    protected function replayTimeline(
        Account                    $account,
        InterestCalculationService $calculator,
        array                      $payments
    ): void
    {
        $disbursements = LedgerEntry::query()
            ->where('account_id', $account->id)
            ->where('type', 'disbursement')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        $events = [];

        foreach ($disbursements as $disbursement) {
            $events[] = [
                'date'         => $disbursement->transaction_date->format('Y-m-d'),
                'seq'          => 0, // disbursement before payment on the same day
                'id'           => $disbursement->id,
                'kind'         => 'disbursement',
                'disbursement' => $disbursement,
            ];
        }

        foreach ($payments as $payment) {
            $events[] = [
                'date'    => $payment['date'],
                'seq'     => 1,
                'id'      => 0,
                'kind'    => 'payment',
                'payment' => $payment,
            ];
        }

        usort(
            $events,
            fn(array $a, array $b): int => [$a['date'], $a['seq'], $a['id']]
                <=> [$b['date'], $b['seq'], $b['id']]
        );

        foreach ($events as $event) {
            if ($event['kind'] === 'disbursement') {
                $this->applyCreditToDisbursement($account, $calculator, $event['disbursement']);
                continue;
            }

            $payment = $event['payment'];
            if ((float)$payment['amount'] <= 0) {
                continue;
            }

            $calculator->processPayment(
                $account,
                (float)$payment['amount'],
                $payment['date'],
                $payment['foreign_amount'],
                $payment['rate'],
                $payment['currency'],
                null,
            );
        }
    }

    /**
     * Apply available borrower credit to a single disbursement at its own date,
     * consuming whatever receipts (manual or overpayment) exist at this point in
     * the replay. Delegates to the one canonical credit routine.
     */
    protected function applyCreditToDisbursement(
        Account                    $account,
        InterestCalculationService $calculator,
        LedgerEntry                $disbursement
    ): void
    {
        $data = $disbursement->toArray();

        $calculator->applyDisbursementCredit($account, $data);

        $disbursement->fill([
            'applied_credit_amount' => $data['applied_credit_amount'] ?? 0,
            'remaining_principal'   => $data['remaining_principal'] ?? $disbursement->total_disbursed_base,
            'unpaid_interest'       => $data['unpaid_interest'] ?? 0,
            'pre_credit_interest'   => $data['pre_credit_interest'] ?? 0,
            'is_settled'            => (float)($data['remaining_principal'] ?? $disbursement->total_disbursed_base) <= 0,
        ])->save();
    }

    protected function paymentKey(string $date, string $currency): string
    {
        return implode('|', [$date, $currency]);
    }
}

