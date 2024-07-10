<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OrderStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Order Status Distribution';

    protected static ?string $maxHeight = '250px';

    protected static ?int $sort = 4;


    protected function getData(): array
    {
        $year = $this->filters['yearlyOrders'] ?? 'all';

        $statusCounts = Order::countOrdersByStatus($year);

        $labels = $statusCounts->keys()->all();

        $bgColors = $this->getBackgroundColor($labels);


        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Order Status',
                    'data' => $statusCounts->values()->all(),
                    'backgroundColor' => $bgColors,
                    'hoverOffset' => 4,
                    'borderColor' => 'transparent'
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
            ],
        ];
    }

    protected function getBackgroundColor(array $labels): array
    {
        return count($labels) == 3
            ? ['rgba(255, 99, 132, 0.5)', 'rgba(75, 192, 192, 0.5)', 'rgba(255, 159, 64, 0.5)']
            : ['rgba(75, 192, 192, 0.5)', 'rgba(255, 159, 64, 0.5)'];
    }


    protected function getType(): string
    {
        return 'bar';
    }
}
