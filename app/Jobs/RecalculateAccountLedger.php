<?php

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
     * Kept for compatibility with existing dispatch sites.
     *
     * This job intentionally performs a full-account replay because the current
     * schema stores settlements as generated per-ledger output rows, not as
     * first-class source payment events.
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
            $this->reapplyDisbursementCredits($account, $calculator);
            $this->replayPayments($account, $calculator, $paymentsToReplay);
        });
    }

    /**
     * Reconstruct payment-like replay events from generated settlement rows and
     * generated overpayment receipt rows.
     *
     * This is a best-effort reconstruction until the schema has a real payment
     * batch/source-event table.
     */
    protected function collectReplayPayments(Account $account): array
    {
        $payments = [];

        $settlements = Settlement::query()
            ->whereHas('ledgerEntry', fn ($q) => $q->where('account_id', $account->id))
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($settlements as $settlement) {
            $date = $settlement->transaction_date->format('Y-m-d');
            $currency = $settlement->currency_type ?? config('financial.base_currency', 'USD');
            $rate = $settlement->settlement_exchange_rate !== null
                ? (float) $settlement->settlement_exchange_rate
                : null;

            $key = $this->paymentKey($date, $currency, $rate);

            if (! isset($payments[$key])) {
                $payments[$key] = [
                    'date' => $date,
                    'amount' => 0.0,
                    'currency' => $currency,
                    'rate' => $rate,
                    'foreign_values' => [],
                ];
            }

            $payments[$key]['amount'] += (float) $settlement->settlement_amount;

            if ($settlement->foreign_settlement_amount !== null) {
                $foreign = round((float) $settlement->foreign_settlement_amount, 6);
                $payments[$key]['foreign_values'][(string) $foreign] = $foreign;
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
            $rate = $overpayment->exchange_rate !== null
                ? (float) $overpayment->exchange_rate
                : null;

            $key = $this->paymentKey($date, $currency, $rate);

            if (! isset($payments[$key])) {
                $payments[$key] = [
                    'date' => $date,
                    'amount' => 0.0,
                    'currency' => $currency,
                    'rate' => $rate,
                    'foreign_values' => [],
                ];
            }

            $payments[$key]['amount'] += (float) $overpayment->total_disbursed_base;

            if ($overpayment->amount_foreign_currency !== null) {
                $foreign = round((float) $overpayment->amount_foreign_currency, 6);
                $payments[$key]['foreign_values'][(string) $foreign] = $foreign;
            }
        }

        $payments = array_values(array_map(function (array $payment): array {
            $foreignValues = array_values($payment['foreign_values']);

            if (count($foreignValues) === 0) {
                $payment['foreign_amount'] = null;
            } elseif (count($foreignValues) === 1) {
                // Avoid multiplying one original foreign amount that was duplicated
                // across multiple generated settlement rows.
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
            fn (array $a, array $b): int => [$a['date'], $a['currency'], (string) $a['rate']]
                <=> [$b['date'], $b['currency'], (string) $b['rate']]
        );

        return $payments;
    }

    protected function purgeGeneratedRows(Account $account): void
    {
        Settlement::query()
            ->whereHas('ledgerEntry', fn ($q) => $q->where('account_id', $account->id))
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

    protected function reapplyDisbursementCredits(
        Account $account,
        InterestCalculationService $calculator
    ): void {
        $disbursements = LedgerEntry::query()
            ->where('account_id', $account->id)
            ->where('type', 'disbursement')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($disbursements as $disbursement) {
            $data = $disbursement->toArray();

            $calculator->applyDisbursementCredit($account, $data);

            $disbursement->fill([
                'applied_credit_amount' => $data['applied_credit_amount'] ?? 0,
                'remaining_principal' => $data['remaining_principal'] ?? $disbursement->total_disbursed_base,
                'unpaid_interest' => $data['unpaid_interest'] ?? 0,
                'pre_credit_interest' => $data['pre_credit_interest'] ?? 0,
                'is_settled' => (float) ($data['remaining_principal'] ?? $disbursement->total_disbursed_base) <= 0,
            ])->save();
        }
    }

    protected function replayPayments(
        Account $account,
        InterestCalculationService $calculator,
        array $payments
    ): void {
        foreach ($payments as $payment) {
            if ((float) $payment['amount'] <= 0) {
                continue;
            }

            $calculator->processPayment(
                $account,
                (float) $payment['amount'],
                $payment['date'],
                $payment['foreign_amount'],
                $payment['rate'],
                $payment['currency'],
                null,
            );
        }
    }

    protected function paymentKey(string $date, string $currency, ?float $rate): string
    {
        return implode('|', [
            $date,
            $currency,
            $rate !== null ? number_format($rate, 6, '.', '') : 'null',
        ]);
    }
}
