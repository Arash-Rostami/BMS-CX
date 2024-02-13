<?php

namespace App\Filament\Resources\Operational\OrderResource\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $statusCounts = Order::getStatusCounts(); // Fetch the status counts using the static method

        return [
            Stat::make('In Transit', $statusCounts->get(2, 0))
                ->extraAttributes([
                    'class' => 'hidden md:block border-2 border-red-500',
                ])
                ->label(new HtmlString("<span class='grayscale'>🚧 In Transit</span>"))
                ->color('secondary'),

            Stat::make('Customs', $statusCounts->get(3, 0))
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->label(new HtmlString("<span class='grayscale'>👮 Customs</span>"))
                ->color('secondary'),

            Stat::make('Delivered', $statusCounts->get(4, 0))
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->label(new HtmlString("<span class='grayscale'>🚚 Delivered</span>"))
                ->color('secondary'),

            Stat::make('Shipped', $statusCounts->get(5, 0))
                ->extraAttributes([
                    'class' => 'hidden md:block',
                ])
                ->label(new HtmlString("<span class='grayscale'>🚢 Shipped</span>"))
                ->color('secondary')
        ];
    }
}
