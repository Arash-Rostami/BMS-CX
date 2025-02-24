<?php

namespace App\Filament\Widgets;

use App\Services\Repository\PaymentRequestRepository;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class PaymentRequestTotalChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = '📈 Payment Request Overview';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $repository = new PaymentRequestRepository();

        $repository
            ->filterByAttributes([
                'department' => $this->filters['department'] ?? null,
                'currency' => $this->filters['currency'] ?? null,
                'payment_type' => $this->filters['payment_type'] ?? null,
                'status' => $this->filters['status'] ?? null,

            ]);

        $selectedPeriod = $this->filters['period'] ?? 'monthly';
        $data = $repository->fetchStatisticsByPeriod($selectedPeriod);


        return [
            'datasets' => [
                [
                    'label' => 'Payment Requests',
                    'data' => array_values($data),
                    'tension' => 0.4,
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    public static function canView(): bool
    {
        return true;
    }
}
