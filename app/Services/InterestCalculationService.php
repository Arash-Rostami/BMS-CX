<?php

namespace App\Services;

use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Settlement;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InterestCalculationService
{
    public function processPayment(Account $account, float $paymentBase, string $date, ?float $foreignAmount = null, ?float $exchangeRate = null)
    {
        DB::transaction(function () use ($account, $paymentBase, $date, $foreignAmount, $exchangeRate) {
            $paymentDate = Carbon::parse($date);
            $remainingPayment = $paymentBase;

            $activeLedgers = LedgerEntry::where('account_id', $account->id)
                ->where('is_settled', false)
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($activeLedgers as $ledger) {
                if ($remainingPayment <= 0) {
                    break;
                }

                $lastEventDate = $ledger->settlements()->orderBy('transaction_date', 'desc')->value('transaction_date');
                if (!$lastEventDate) {
                    $lastEventDate = $ledger->transaction_date;
                }

                $lastEventDateObj = Carbon::parse($lastEventDate);
                $ledgerDateObj = Carbon::parse($ledger->transaction_date);

                $startDay = clone $ledgerDateObj;
                $startDayDiff = $startDay->diffInDays($lastEventDateObj, false);

                $endDay = clone $ledgerDateObj;
                $endDayDiff = $endDay->diffInDays($paymentDate, false);

                $durationDays = $endDayDiff - $startDayDiff;

                $accruedInterest = $this->calculateInterest($ledger->remaining_principal, $startDayDiff, $endDayDiff, $ledger->rate_matrix_snapshot);

                if ($ledger->type === 'receipt') {
                    $accruedInterest = -abs($accruedInterest);
                }

                $ledger->unpaid_interest += $accruedInterest;

                $deductedFromInterest = 0;
                $deductedFromPrincipal = 0;

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

                Settlement::create([
                    'ledger_entry_id' => $ledger->id,
                    'transaction_date' => $paymentDate,
                    'duration_days' => $durationDays,
                    'foreign_settlement_amount' => $foreignAmount,
                    'settlement_exchange_rate' => $exchangeRate,
                    'settlement_amount' => $deductedFromInterest + $deductedFromPrincipal,
                    'accrued_interest_in_period' => $accruedInterest,
                    'deducted_from_interest' => $deductedFromInterest,
                    'deducted_from_principal' => $deductedFromPrincipal,
                ]);
            }

            if ($remainingPayment > 0) {
                LedgerEntry::create([
                    'account_id' => $account->id,
                    'user_id' => auth()->id() ?? 1,
                    'type' => 'receipt',
                    'description' => 'Overpayment spillover',
                    'transaction_date' => $paymentDate,
                    'currency_type' => 'USD',
                    'amount_foreign_currency' => $remainingPayment,
                    'exchange_rate' => 1,
                    'principal_amount_base' => $remainingPayment,
                    'commission_amount' => 0,
                    'total_disbursed_base' => $remainingPayment,
                    'rate_matrix_snapshot' => config('financial.interest_tiers'),
                    'remaining_principal' => $remainingPayment,
                ]);
            }
        });
    }

    public function calculateInterest(float $principal, int $startDay, int $endDay, array $rateMatrix): float
    {
        $durationDays = $endDay - $startDay;

        if ($durationDays == 0 || $principal <= 0) {
            return 0;
        }

        if ($durationDays < 0) {
            $firstTierRate = $rateMatrix[0]['rate'];
            return $principal * $durationDays * ($firstTierRate / 30);
        }

        $totalInterest = 0;
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
}
