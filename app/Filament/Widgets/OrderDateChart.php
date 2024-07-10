<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget\Stat;


class OrderDateChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Yearly Order Trend';

    protected static ?int $sort = 1;


    protected function getData(): array
    {
        $year = $this->filters['yearlyOrders'] ?? 'all';

        $monthlyCounts = $this->separateByMonth(Order::countOrdersByMonth($year));


        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'datasets' => [
                [
                    'label' => 'Orders by Month',
                    'data' => array_values($monthlyCounts),
                    'backgroundColor' => '#4F46E5',
                    'borderColor' => 'rgba(37, 99, 235, 1)',
                    'tension' => 0.1,
                    'hoverOffset' => 4
                ]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'animations' => [
                    'duration' => 1000,
                    'easing' => 'linear',
                    'from' => 1,
                    'to' => 0,
                    'loop' => true
                ]
                ,
            ]
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function separateByMonth(\Illuminate\Support\Collection $ordersByMonth): array
    {
        $monthlyCounts = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyCounts[] = $ordersByMonth[$i] ?? 0;
        }
        return $monthlyCounts;
    }
}
