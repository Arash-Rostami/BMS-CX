<?php

namespace App\Filament\Resources\Financial\LedgerEntryResource\Pages;

use App\Filament\Resources\Financial\LedgerEntryResource\Pages\AdminComponents\Filter;
use App\Filament\Resources\Financial\LedgerEntryResource\Pages\AdminComponents\Form;
use App\Filament\Resources\Financial\LedgerEntryResource\Pages\AdminComponents\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;

class Admin
{
    use Form, Table, Filter;

    /***
     * Rate convention is set in config('financial.exchange_rate_mode'):
     *   'multiply' (default) — rate = base units per 1 foreign unit
     *                          e.g. rate 1.08 means 1 EUR = 1.08 USD → 1000 EUR × 1.08 = 1080 USD
     *   'divide'             — rate = foreign units per 1 base unit
     *                          e.g. rate 1.08 means 1 USD = 1.08 EUR → 1000 EUR / 1.08 = 925 USD
     */
    public static function recalculateAmounts(Set $set, Get $get): void
    {
        $rate = (float)$get('exchange_rate');
        $foreign = (float)$get('amount_foreign_currency');

        if ($rate <= 0 || $foreign <= 0) {
            return;
        }

        $principal = config('financial.exchange_rate_mode', 'multiply') === 'divide'
            ? $foreign / $rate
            : $foreign * $rate;

        $set('principal_amount_base', number_format($principal, 3, '.', ''));
        static::recalculateTotals($set, $get, $principal);
    }

    public static function recalculateTotals(Set $set, Get $get, ?float $principal = null): void
    {
        $principal ??= (float)$get('principal_amount_base');
        $commission = (float)$get('commission_amount');
        $currency = $get('currency_type');

        if (!static::isBaseCurrency($currency) && $commission > 0) {
            $rate = (float)$get('exchange_rate');
            if ($rate > 0) {
                $mode = config('financial.exchange_rate_mode', 'multiply');
                $commission = $mode === 'divide' ? $commission / $rate : $commission * $rate;
            } else {
                $commission = 0;
            }
        }

        $set('total_disbursed_base', number_format($principal + $commission, 3, '.', ''));
        $set('remaining_principal', number_format($principal, 3, '.', ''));
    }
}
