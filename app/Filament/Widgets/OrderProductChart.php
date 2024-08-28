<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Services\ColorTheme;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;

class OrderProductChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Orders by Category or Product';

    protected static ?string $maxHeight = '250px';

    protected static ?int $sort = 2;


    protected function getData(): array
    {
        $filterType = $this->filter;

        $year = $this->filters['yearlyOrders'] ?? 'all';

        $chartData = $this->fetchChartData($filterType, $year);

        $bgColor = $this->getCachedBackgroundColor();

        return [
            'labels' => $chartData->pluck('name')->toArray(),
            'datasets' => [
                [
                    'label' => "Orders by {$filterType}",
                    'data' => $chartData->pluck('count')->toArray(),
                    'backgroundColor' => $bgColor,
                    'borderColor' => 'transparent',
                    'hoverOffset' => 4
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'animation' => [
                    'animateRotate' => true,
                    'duration' => 1000,
                    'easing' => 'easeOutBounce',
                    'delay' => 1000
                ],
                'scales' => [
                    'y' => [
                        'display' => false,
                    ]
                ]
            ]
        ];
    }

    protected function fetchChartData(?string $filterType, string $year)
    {
        return $filterType == 'product'
            ? Order::countOrdersByProduct($year)
            : Order::countOrdersByCategory($year);
    }

    protected function getCachedBackgroundColor()
    {
        return Cache::remember('widget-bg-color-product', 300, function () {
            return ColorTheme::getRandomColorForWidget();
        });
    }

    protected function getFilters(): ?array
    {
        return ['category' => 'Categories', 'product' => 'Products'];
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
