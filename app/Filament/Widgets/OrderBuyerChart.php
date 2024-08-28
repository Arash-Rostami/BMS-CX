<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Services\ColorTheme;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;

class OrderBuyerChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Buyer Order Frequency';

    protected static ?string $maxHeight = '250px';

    protected static ?int $sort = 3;


    protected function getData(): array
    {
        $year = $this->filters['yearlyOrders'] ?? 'all';

        $orderFrequencies = Order::countOrdersByBuyer($year);

        $bgColor = $this->getBackgroundColor();

        return [
            'labels' => $orderFrequencies->keys()->all(),
            'datasets' => [
                [
                    'label' => 'Order Count',
                    'data' => $orderFrequencies->values()->all(),
                    'backgroundColor' => $bgColor,
                    'borderColor' => 'transparent',
                    'hoverOffset' => 4
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
            ],
        ];
    }

    protected function getBackgroundColor(): mixed
    {
        return Cache::remember('widget-bg-color-buyer', 300, function () {
            return ColorTheme::getRandomColorForWidget();
        });
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
        {
         scales: {
            y: {
                ticks: {
                    display: false
                }
            },
             x: {
                ticks: {
                    display: false
                    }
                }
             }
        }
    JS);
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
