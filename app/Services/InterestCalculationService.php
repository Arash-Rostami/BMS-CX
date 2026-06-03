<?php


namespace App\Services;

use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Settlement;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InterestCalculationService
{
    public const OVERPAYMENT_DESCRIPTION = 'Overpayment spillover';

    /**
     * Apply available borrower credit against a new disbursement at creation time.
     * Also calculates pre-credit counter-interest for the period the receipt sat idle
     * before being consumed, and stores it as negative unpaid_interest.
     */
    public function applyDisbursementCredit(Account $account, array &$data): void
    {
        if (($data['type'] ?? '') !== 'disbursement') {
            return;
        }

        $gross = (float)$data['total_disbursed_base'];
        $disbursementDate = Carbon::parse($data['transaction_date']);
        $remaining = $gross;
        $applied = 0.0;
        $preCreditInterest = 0.0;

        $receipts = LedgerEntry::where('account_id', $account->id)
            ->where('type', 'receipt')
            ->where('is_settled', false)
            ->where('remaining_principal', '<', 0)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($receipts as $receipt) {
            if ($remaining <= 0) {
                break;
            }

            $available = abs($receipt->remaining_principal);
            $consume = min($available, $remaining);

            // Counter-interest on consumed amount for [receipt_date → disbursement_date]
            $receiptDate = Carbon::parse($receipt->transaction_date);
            $daysHeld = $receiptDate->diffInDays($disbursementDate, false);

            if ($daysHeld > 0) {
                $preCreditInterest += $this->calculateInterest(
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

            $applied += $consume;
            $remaining -= $consume;
        }

        $data['applied_credit_amount'] = $applied;
        $data['remaining_principal'] = max(0, $gross - $applied);
        $data['is_settled'] = $data['remaining_principal'] <= 0;

        // Store pre-credit interest as negative unpaid_interest for processPayment to consume
        if ($preCreditInterest > 0) {
            $data['pre_credit_interest'] = -$preCreditInterest;
            $data['unpaid_interest'] = -$preCreditInterest;
        }
    }

    /**
     * @param array<int, array{max_days?: int, rate: float}> $rateMatrix
     */
    public function calculateInterest(float $principal, int $startDay, int $endDay, array $rateMatrix): float
    {
        $durationDays = $endDay - $startDay;

        if ($durationDays === 0 || $principal <= 0) {
            return 0.0;
        }

        if ($durationDays < 0) {
            return $principal * $durationDays * ($rateMatrix[0]['rate'] / 30);
        }

        $totalInterest = 0.0;
        $currentStart = max(1, $startDay + 1);

        foreach ($rateMatrix as $tier) {
            if ($currentStart > $endDay) {
                break;
            }

            $tierMax = $tier['max_days'] ?? PHP_INT_MAX;

            if ($currentStart <= $tierMax) {
                $calcEnd = min($endDay, $tierMax);
                $daysInTier = $calcEnd - $currentStart + 1;

                if ($daysInTier > 0) {
                    $totalInterest += $principal * $daysInTier * ($tier['rate'] / 30);
                    $currentStart = $calcEnd + 1;
                }
            }
        }

        return $totalInterest;
    }

    public function processPayment(
        Account $account,
        float   $paymentBase,
        string  $date,
        ?float  $foreignAmount = null,
        ?float  $exchangeRate = null,
        string  $currency = 'USD',
        ?int    $userId = null
    ): array
    {
        return DB::transaction(function () use (
            $account, $paymentBase, $date, $foreignAmount, $exchangeRate, $currency, $userId
        ): array {
            $createdIds = [];
            $paymentDate = Carbon::parse($date);
            $remainingPayment = $paymentBase;

            $activeLedgers = LedgerEntry::query()
                ->where('account_id', $account->id)
                ->where('is_settled', false)
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $allSettlements = Settlement::query()
                ->whereIn('ledger_entry_id', $activeLedgers->pluck('id'))
                ->orderBy('transaction_date', 'desc')
                ->get();

            $latestSettlementDates = $allSettlements
                ->groupBy('ledger_entry_id')
                ->map(
                    fn(Collection $group): Carbon => Carbon::parse($group->first()->transaction_date)
                );

            // Pass 1: accumulate counter-interest from residual receipt credit
            $counterInterestPool = 0.0;

            foreach ($activeLedgers as $ledger) {
                if ($ledger->type !== 'receipt') {
                    continue;
                }

                if ($paymentDate->lt(Carbon::parse($ledger->transaction_date))) {
                    continue;
                }

                [$startDayDiff, $endDayDiff] = $this->resolveDateDiffs(
                    $ledger,
                    $paymentDate,
                    $latestSettlementDates
                );

                $counterInterest = $this->calculateInterest(
                    abs((float)$ledger->remaining_principal),
                    (int)$startDayDiff,
                    (int)$endDayDiff,
                    (array)$ledger->rate_matrix_snapshot
                );

                $counterInterest = max(0, $counterInterest - abs((float)$ledger->unpaid_interest));

                $ledger->unpaid_interest -= $counterInterest;
                $ledger->save();

                $counterInterestPool += $counterInterest;
            }

            // Pass 2: process loan ledgers
            foreach ($activeLedgers as $ledger) {
                if ($ledger->type === 'receipt') {
                    continue;
                }

                if ($remainingPayment <= 0) {
                    break;
                }

                if ($paymentDate->lt(Carbon::parse($ledger->transaction_date))) {
                    continue;
                }

                [$startDayDiff, $endDayDiff, $durationDays] = $this->resolveDateDiffs(
                    $ledger,
                    $paymentDate,
                    $latestSettlementDates
                );

                $grossInterest = $this->calculateInterest(
                    (float)$ledger->remaining_principal,
                    (int)$startDayDiff,
                    (int)$endDayDiff,
                    (array)$ledger->rate_matrix_snapshot
                );

                // Extract pre-credit counter-interest stored at disbursement birth
                $preCreditCounter = 0.0;
                if ($ledger->unpaid_interest < 0) {
                    $preCreditCounter = abs($ledger->unpaid_interest);
                    $ledger->unpaid_interest = 0.0;
                }

                // Pool only covers what birth credit did NOT already cover
                $remainingAfterPreCredit = max(0, $grossInterest - $preCreditCounter);
                $poolApplied = ($counterInterestPool > 0 && $remainingAfterPreCredit > 0)
                    ? min($counterInterestPool, $remainingAfterPreCredit)
                    : 0.0;
                $counterInterestPool -= $poolApplied;

                //  Do NOT cap counter-interest at gross interest.
                $counterApplied = $preCreditCounter + $poolApplied;

                $ledger->unpaid_interest += $grossInterest - $counterApplied;

                // Excess counter-interest (unpaid_interest < 0) reduces principal
                if ($ledger->unpaid_interest < 0) {
                    $excess = abs($ledger->unpaid_interest);
                    $ledger->remaining_principal = max(0, $ledger->remaining_principal - $excess);
                    $ledger->unpaid_interest = 0;

                    if ($ledger->remaining_principal <= 0) {
                        $ledger->remaining_principal = 0;
                        $ledger->is_settled = true;
                    }
                }

                $deductedFromInterest = 0.0;
                $deductedFromPrincipal = 0.0;

                if ($ledger->unpaid_interest > 0) {
                    if ($remainingPayment >= $ledger->unpaid_interest) {
                        $deductedFromInterest = $ledger->unpaid_interest;
                        $remainingPayment -= $ledger->unpaid_interest;
                        $ledger->unpaid_interest = 0;
                    } else {
                        $deductedFromInterest = $remainingPayment;
                        $ledger->unpaid_interest -= $remainingPayment;
                        $remainingPayment = 0;
                    }
                }

                if ($remainingPayment > 0 && $ledger->remaining_principal > 0) {
                    if ($remainingPayment >= $ledger->remaining_principal) {
                        $deductedFromPrincipal = $ledger->remaining_principal;
                        $remainingPayment -= $ledger->remaining_principal;
                        $ledger->remaining_principal = 0;
                        $ledger->is_settled = true;
                    } else {
                        $deductedFromPrincipal = $remainingPayment;
                        $ledger->remaining_principal -= $remainingPayment;
                        $remainingPayment = 0;
                    }
                }

                $ledger->save();

                $totalDeducted = $deductedFromInterest + $deductedFromPrincipal;

                if ($totalDeducted > 0) {
                    $settlement = Settlement::create([
                        'ledger_entry_id' => $ledger->id,
                        'transaction_date' => $paymentDate,
                        'duration_days' => $durationDays,
                        'foreign_settlement_amount' => $foreignAmount,
                        'settlement_exchange_rate' => $exchangeRate,
                        'settlement_amount' => $totalDeducted,
                        'currency_type' => $currency,
                        'accrued_interest_in_period' => $grossInterest,
                        'deducted_from_interest' => $deductedFromInterest,
                        'deducted_from_principal' => $deductedFromPrincipal,
                        'counter_interest_applied' => $counterApplied,
                    ]);
                    $createdIds[] = $settlement->id;
                }
            }

            if ($remainingPayment > 0) {
                LedgerEntry::create([
                    'account_id' => $account->id,
                    'user_id' => $userId ?? auth()->id() ?? 1,
                    'type' => 'receipt',
                    'description' => self::OVERPAYMENT_DESCRIPTION,
                    'transaction_date' => $paymentDate,
                    'currency_type' => config('financial.base_currency', 'USD'),
                    'amount_foreign_currency' => $remainingPayment,
                    'exchange_rate' => 1,
                    'principal_amount_base' => $remainingPayment,
                    'commission_amount' => 0,
                    'total_disbursed_base' => $remainingPayment,
                    'applied_credit_amount' => 0,
                    'rate_matrix_snapshot' => config('financial.interest_tiers'),
                    'remaining_principal' => -$remainingPayment,
                ]);
            }

            return $createdIds;
        });
    }

    private function resolveDateDiffs(
        LedgerEntry $ledger,
        Carbon      $paymentDate,
        Collection  $latestSettlementDates
    ): array
    {
        $lastEventDate = $latestSettlementDates->get($ledger->id) ?? $ledger->transaction_date;

        $ledgerDateObj = Carbon::parse($ledger->transaction_date);
        $lastEventDateObj = Carbon::parse($lastEventDate);

        $startDayDiff = $ledgerDateObj->diffInDays($lastEventDateObj, false);
        $endDayDiff = $ledgerDateObj->diffInDays($paymentDate, false);

        return [$startDayDiff, $endDayDiff, $endDayDiff - $startDayDiff];
    }
}
