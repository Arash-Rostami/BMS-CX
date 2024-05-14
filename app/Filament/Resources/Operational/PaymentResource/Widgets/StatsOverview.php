<?php

namespace App\Filament\Resources\Operational\PaymentResource\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $formattedSums = array_map(fn($each) => number_format($each),
            Payment::sumAmountsForCurrencies(['USD', 'EURO', 'Yuan', 'Dirham', 'Ruble', 'Rial']));

        return [
            Stat::make('USD Amount', '$ ' . ($formattedSums['USD'] ?? '0.00'))
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->label(new HtmlString("<span class='text-gray'>USD</span>"))
                ->color('blue'),

            Stat::make('EURO Amount', '€ ' . ($formattedSums['EURO'] ?? '0.00'))
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->label(new HtmlString("<span class='text-gray'>EURO</span>"))
                ->color('green'),

            Stat::make('Yuan Amount', '¥ ' . ($formattedSums['Yuan'] ?? '0.00'))
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->label(new HtmlString("<span class='text-gray'>Yuan</span>"))
                ->color('red'),

            Stat::make('Dirham Amount', 'AED ' . ($formattedSums['Dirham'] ?? '0.00'))
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->label(new HtmlString("<span class='text-gray'>Dirham</span>"))
                ->color('purple'),

            Stat::make('Ruble Amount', '₽ ' . ($formattedSums['Ruble'] ?? '0.00'))
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->label(new HtmlString("<span class='text-gray'>Ruble</span>"))
                ->color('orange'),

            Stat::make('Rial Amount', 'IRR ' . ($formattedSums['Rial'] ?? '0.00'))
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->label(new HtmlString("<span class='text-gray'>Rial</span>"))
                ->color('yellow'),
        ];
    }
}
