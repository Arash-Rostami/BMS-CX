<?php

namespace App\Filament\Widgets;

use App\Models\Logistic;
use App\Services\ColorTheme;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;

class OrderPackagingChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Packaging Types Distribution';

    protected static ?string $maxHeight = '250px';

    protected static ?int $sort = 8;

    protected function getData(): array
    {

        $year = $this->filters['yearlyOrders'] ?? 'all';

        $packagingData = Logistic::countByPackagingType($year);

        $bgColor = $this->getCachedBackgroundColor();


        return [
            'labels' => $packagingData->pluck('name')->toArray(),
            'datasets' => [
                [
                    'label' => "Types of Packaging",
                    'data' => $packagingData->pluck('total')->toArray(),
                    'backgroundColor' => $bgColor,
                    'borderColor' => 'transparent',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    protected function getCachedBackgroundColor()
    {
        return Cache::remember('widget-bg-color-packaging', 300, function () {
            return ColorTheme::getRandomColorForWidget();
        });
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
