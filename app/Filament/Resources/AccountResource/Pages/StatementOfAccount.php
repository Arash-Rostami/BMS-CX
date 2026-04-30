<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use Filament\Resources\Pages\Page;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class StatementOfAccount extends Page
{
    protected static string $resource = AccountResource::class;

    protected static string $view = 'filament.resources.account-resource.pages.statement-of-account';

    public $record;
    public $transactions = [];
    public $accountName = '';

    public function mount($record): void
    {
        $this->record = $record;

        $account = Account::findOrFail($record);
        $this->accountName = $account->name;

        $ledgers = DB::table('ledger_entries')
            ->where('account_id', $account->id)
            ->select(
                'id',
                'transaction_date',
                'type as event_type',
                'description',
                'total_disbursed_base as debit',
                DB::raw('0 as credit'),
                DB::raw('0 as accrued_interest')
            );

        $settlements = DB::table('settlements')
            ->join('ledger_entries', 'settlements.ledger_entry_id', '=', 'ledger_entries.id')
            ->where('ledger_entries.account_id', $account->id)
            ->select(
                'settlements.id',
                'settlements.transaction_date',
                DB::raw("'settlement' as event_type"),
                DB::raw("'Payment against ledger #' || ledger_entries.id as description"),
                DB::raw('0 as debit'),
                'settlements.settlement_amount as credit',
                'settlements.accrued_interest_in_period as accrued_interest'
            );

        $transactions = $ledgers->union($settlements)
            ->orderBy('transaction_date')
            ->orderBy('event_type')
            ->orderBy('id')
            ->get();

        $runningBalance = 0;

        foreach ($transactions as $transaction) {
            if ($transaction->event_type === 'disbursement') {
                $runningBalance += $transaction->debit;
            } elseif ($transaction->event_type === 'receipt' || $transaction->event_type === 'settlement') {
                $runningBalance -= $transaction->credit;
            }


            $runningBalance += $transaction->accrued_interest;

            $transaction->running_balance = $runningBalance;
        }

        $this->transactions = $transactions;
    }
}
