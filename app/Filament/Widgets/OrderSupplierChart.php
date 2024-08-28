<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Services\ColorTheme;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;

class OrderSupplierChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Supplier Order Frequency';

    protected static ?string $maxHeight = '250px';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $year = $this->filters['yearlyOrders'] ?? 'all';

        $orderFrequencies = Order::countOrdersBySupplier($year);

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
        return Cache::remember('widget-bg-color-supplier', 300, function () {
            return ColorTheme::getRandomColorForWidget();
        });
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
