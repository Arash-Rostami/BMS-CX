<?php

namespace App\Filament\Widgets;

use App\Models\OrderDetail;
use App\Services\ColorTheme;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;
use Filament\Support\RawJs;


class PurchasePriceChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Total Purchase Price';

    protected static ?string $maxHeight = '250px';

    protected static ?int $sort = 6;

    protected function getData(): array
    {
        $filterType = $this->filter ?? 'category';

        $year = $this->filters['yearlyOrders'] ?? 'all';

        $chartData = $this->fetchChartData($filterType, $year);

        $bgColor = $this->getCachedBackgroundColor();


        return [
            'labels' => $chartData->pluck('name')->toArray(),
            'datasets' => [
                [
                    'label' => "Orders by {$filterType}",
                    'data' => $chartData->pluck('totalPrice')->toArray(),
                    'backgroundColor' => $bgColor,
                    'borderColor' => 'transparent',
                    'hoverOffset' => 4,
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
            ],
        ];
    }

    protected function fetchChartData($filterType, string $year)
    {

        return $filterType == 'product'
            ? OrderDetail::fetchChartDataByProduct($year)
            : OrderDetail::fetchChartDataByCategory($year);
    }


    protected function getCachedBackgroundColor()
    {
        return Cache::remember('widget-bg-color-price', 300, function () {
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
                        callback: (value) => {
                            if (value >= 1000000) {
                                return '$' + (value / 1000000) + 'M';
                            } else if (value >= 1000) {
                                return '$' + (value / 1000) + 'k';
                            } else {
                                return '$' + value;
                            }
                        }
                    }
                }
           }
        }
    JS);
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
