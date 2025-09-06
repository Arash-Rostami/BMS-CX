<?php

namespace App\Filament\Resources\Operational\CashTransactionResource\Widgets;

use App\Models\CashTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class CashBalanceHeaderWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $balances = Cache::remember('cash_balance_by_currency', 900, fn() => CashTransaction::getBalanceByCurrency());

        if (empty($balances)) {
            return [
                Stat::make('No Cash Transactions', '0.00')
                    ->description('Add initial funds to start tracking')
                    ->descriptionIcon('heroicon-m-information-circle')
                    ->color('warning'),
            ];
        }

        $stats = [];
        foreach ($balances as $currency => $amount) {
            $isPositive = $amount >= 0;

            $stats[] = Stat::make(
                "Balance ({$currency})",
                number_format($amount, 2)
            )
                ->description($isPositive ? 'Positive ' : 'Negative ')
                ->descriptionIcon($isPositive ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->icon($this->getCurrencyIcon($currency))
                ->color($isPositive ? 'success' : 'danger');
        }

        return $stats;
    }

    private function getCurrencyIcon(string $currency): string
    {
        return ['EURO' => 'heroicon-o-currency-euro', 'USD' => 'heroicon-o-currency-dollar'][$currency]
            ?? 'heroicon-o-currency-dollar';
    }
}
