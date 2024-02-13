<?php

namespace App\Filament\Resources\Operational\OrderRequestResource\Widgets;

use App\Models\OrderRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $statusCounts = OrderRequest::getStatusCounts();

        return [
            Stat::make('review', $statusCounts->get('review', 0))
                ->extraAttributes([
                    'class' => 'hidden md:block border-2 border-red-500',
                ])
                ->label(new HtmlString("<span class='grayscale'>⚠ Under Review</span>"))
                ->color('secondary'),

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
                ->label(new HtmlString("<span class='grayscale'>❌ Rejected</span>"))
                ->color('secondary'),


            Stat::make('fulfilled', $statusCounts->get('fulfilled', 0))
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->label(new HtmlString("<span class='grayscale'>🏁 Fulfilled</span>"))
                ->color('secondary'),
        ];
    }
}
