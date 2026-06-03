<?php

namespace App\Filament\Resources\Financial\LedgerEntryResource\Pages;

use App\Filament\Resources\Financial\LedgerEntryResource\Pages\AdminComponents\Filter;
use App\Filament\Resources\Financial\LedgerEntryResource\Pages\AdminComponents\Form;
use App\Filament\Resources\Financial\LedgerEntryResource\Pages\AdminComponents\Table;
use App\Jobs\RecalculateAccountLedger;
use App\Models\LedgerEntry;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class Admin
{
    use Filter;
    use Form;
    use Table;

    /***
     * Rate convention is set in config('financial.exchange_rate_mode'):
     *   'multiply' (default) — rate = base units per 1 foreign unit
     *                          e.g. rate 1.08 means 1 EUR = 1.08 USD → 1000 EUR × 1.08 = 1080 USD
     *   'divide'             — rate = foreign units per 1 base unit
     *                          e.g. rate 1.08 means 1 USD = 1.08 EUR → 1000 EUR / 1.08 = 925 USD
     */
    public static function recalculateAmounts(Set $set, Get $get): void
    {
        $rate    = (float) $get('exchange_rate');
        $foreign = (float) $get('amount_foreign_currency');

        if ($rate <= 0 || $foreign <= 0) {
            return;
        }

        $principal = config('financial.exchange_rate_mode', 'multiply') === 'divide'
            ? $foreign / $rate
            : $foreign * $rate;

        $set('principal_amount_base', round($principal, 6));
        static::recalculateTotals($set, $get, $principal, autoCommission: true);
    }

    public static function recalculateTotals(Set $set, Get $get, ?float $principal = null, bool $autoCommission = false): void
    {
        $principal ??= (float) $get('principal_amount_base');
        $currency   = $get('currency_type');

        if ($autoCommission) {
            $set('commission_amount', round($principal * config('financial.commission_rate'), 6));
        }

        $commission = (float) $get('commission_amount');

        if (!static::isBaseCurrency($currency) && $commission > 0) {
            $rate = (float) $get('exchange_rate');
            if ($rate > 0) {
                $mode       = config('financial.exchange_rate_mode', 'multiply');
                $commission = $mode === 'divide' ? $commission / $rate : $commission * $rate;
            } else {
                $commission = 0;
            }
        }

        $total = $principal + $commission;

        $set('total_disbursed_base', round($total, 6));
        $set('remaining_principal',  round($total, 6));
    }

    public static function deleteAndRecalculate(LedgerEntry $record): void
    {
        $accountId = $record->account_id;
        $recalcDate = $record->transaction_date;

        $record->delete();

        RecalculateAccountLedger::dispatchSync($record->account_id);

        Notification::make()
            ->title('Ledger deleted and account recalculated')
            ->success()
            ->send();
    }

    public static function updateAndRecalculate(Model $record, array $data): Model
    {
        $recalcDate = min($record->getOriginal('transaction_date'), $data['transaction_date']);
        $record->update($data);

        RecalculateAccountLedger::dispatchSync($record->account_id);

        Notification::make()
            ->title('Ledger updated and account recalculated')
            ->success()
            ->send();

        return $record;
    }
}
