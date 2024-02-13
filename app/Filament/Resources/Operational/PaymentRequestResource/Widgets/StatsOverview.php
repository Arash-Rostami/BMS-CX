<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Widgets;

use App\Models\PaymentRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $statusCounts = PaymentRequest::getStatusCounts();

        return [
            Stat::make('approved', $statusCounts->get('approved', 0))
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->label(new HtmlString("<span class='grayscale'>✅ Approved</span>"))
                ->color('secondary'),

            Stat::make('rejected', $statusCounts->get('rejected', 0))
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->label(new HtmlString("<span class='grayscale'>⛔ Rejected</span>"))
                ->color('secondary'),


            Stat::make('Completed', $statusCounts->get('completed', 0))
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->label(new HtmlString("<span class='grayscale'>🏁 Completed</span>"))
                ->color('secondary'),

            Stat::make('Cancelled', $statusCounts->get('cancelled', 0))
                ->extraAttributes([
                    'class' => 'hidden md:block border-2 border-red-500',
                ])
                ->label(new HtmlString("<span class='grayscale'>❌ Cancelled</span>"))
                ->color('secondary')
        ];
    }
}
