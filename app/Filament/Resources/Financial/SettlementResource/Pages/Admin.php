<?php

namespace App\Filament\Resources\Financial\SettlementResource\Pages;

use App\Filament\Resources\Financial\SettlementResource\Pages\AdminComponents\Filter;
use App\Filament\Resources\Financial\SettlementResource\Pages\AdminComponents\Form;
use App\Filament\Resources\Financial\SettlementResource\Pages\AdminComponents\Table;
use App\Jobs\RecalculateAccountLedger;
use App\Models\LedgerEntry;
use App\Models\Settlement;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class Admin
{
    use Filter;
    use Form;
    use Table;


    /**
     * Same config-driven convention as LedgerEntry:
     *   'multiply' (default) — foreign × rate = base  (e.g. 1000 EUR × 1.08 = 1080 USD)
     *   'divide'             — foreign / rate = base  (e.g. 1000 EUR / 1.08 =  925 USD)
     */
    public static function recalculateSettlement(Set $set, Get $get): void
    {
        if (static::isBaseCurrency($get('currency_type'))) return;

        $foreign = (float)$get('foreign_settlement_amount');
        $rate = (float)$get('settlement_exchange_rate');

        if ($foreign <= 0 || $rate <= 0) return;

        $base = config('financial.exchange_rate_mode', 'multiply') === 'divide'
            ? $foreign / $rate
            : $foreign * $rate;

        $set('settlement_amount', round($base, 6));
    }

    public static function updateAndRecalculate(Model $record, array $data): Model
    {
        $accountId = $record->ledgerEntry->account_id;
        $recalcDate = min($record->getOriginal('transaction_date'), $data['transaction_date']);
        $ledgerEntryId = $record->ledger_entry_id;

        $record->update($data);

        RecalculateAccountLedger::dispatchSync($accountId, $recalcDate);

        // Return the replacement settlement created by replay, not the deleted corpse
        $replacement = Settlement::where('ledger_entry_id', $ledgerEntryId)
            ->where('transaction_date', $data['transaction_date'])
            ->first();

        Notification::make()->title('Loan Ledger timeline successfully recalculated')
            ->success()
            ->send();

        return $replacement ?? new Settlement();
    }

    public static function deleteAndRecalculate(Model $record): void
    {
        $accountId = $record->ledgerEntry->account_id;
        $recalcDate = $record->transaction_date;

        $record->delete();

        RecalculateAccountLedger::dispatchSync($accountId, $recalcDate);

        Notification::make()->title('Loan Ledger timeline successfully recalculated')
            ->success()
            ->send();
    }
}
