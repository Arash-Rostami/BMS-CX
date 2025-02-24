<?php

namespace App\Filament\Widgets;

use App\Services\Repository\BalanceRepository;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class BalanceBarChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = '📊 Share Distribution';

    protected int|string|array $columnSpan = 1;


    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $repository = new BalanceRepository();

        $repository
            ->filterByAttributes([
                'department' => $this->filters['department'] ?? null,
                'currency' => $this->filters['currency'] ?? null,
            ])
            ->filterByTimePeriod($this->filters['period'] ?? 'monthly');


        $shares = $repository->fetchCategoryShareByCurrency();


        $datasets = [];
        $labels = [];
        $backgroundColors = [];


        foreach ($shares as $currency => $categories) {
            foreach ($categories as $category => $data) {
                if ($data['total'] > 0) {
                    $displayCategory = $category === 'payees' ? 'Beneficiaries' : ucwords($category);
                    $labels[] = "$displayCategory ($currency)";
                    $datasets[] = $data['total'];

                    $backgroundColors[] = match ($category) {
                        'suppliers' => 'rgba(54, 162, 235, 0.6)',
                        'contractors' => 'rgba(255, 206, 86, 0.6)',
                        'beneficiaries', 'payees' => 'rgba(75, 192, 192, 0.6)',
                        default => 'rgba(201, 203, 207, 0.6)',
                    };
                }
            }
        }


        return [
            'datasets' => [
                [
                    'label' => 'Entity Type(s)',
                    'data' => $datasets,
                    'backgroundColor' => $backgroundColors,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
