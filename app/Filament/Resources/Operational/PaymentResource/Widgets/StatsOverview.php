<?php

namespace App\Filament\Resources\Operational\PaymentResource\Widgets;

use App\Models\Payment;
use App\Services\PriceFormatter;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        list($formattedSums, $condensedSums) =
            $this->formatSums(Payment::sumAmountsForCurrencies(['USD', 'EURO', 'Yuan', 'Dirham', 'Ruble', 'Rial']));

        return [
            Stat::make('Rial Amount', $condensedSums['Rial'])
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->description('IRR ' . ($formattedSums['Rial'] ?? '0.00'))
                ->label(new HtmlString("<span class='text-gray'>Rial</span>"))
                ->color('yellow'),

            Stat::make('USD Amount', $condensedSums['USD'])
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->description('$ ' . ($formattedSums['USD'] ?? '0.00'))
                ->label(new HtmlString("<span class='text-gray'>USD</span>"))
                ->color('blue'),

            Stat::make('EURO Amount', $condensedSums['EURO'])
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->description('€ ' . ($formattedSums['EURO'] ?? '0.00'))
                ->label(new HtmlString("<span class='text-gray'>EURO</span>"))
                ->color('green'),


            Stat::make('Dirham Amount', $condensedSums['Dirham'])
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->description('AED ' . ($formattedSums['Dirham'] ?? '0.00'))
                ->label(new HtmlString("<span class='text-gray'>Dirham</span>"))
                ->color('purple'),
        ];
    }

    /**
     * @param mixed $sums
     * @return array
     */
    protected function formatSums(mixed $sums): array
    {
        $formattedSums = array_map(fn($each) => number_format($each), $sums);

        $condensedSums = [
            'Rial' => PriceFormatter::condense($sums['Rial'] ?? 0),
            'USD' => PriceFormatter::condense($sums['USD'] ?? 0),
            'EURO' => PriceFormatter::condense($sums['EURO'] ?? 0),
            'Dirham' => PriceFormatter::condense($sums['Dirham'] ?? 0),
        ];

        return [$formattedSums, $condensedSums];
    }
}
