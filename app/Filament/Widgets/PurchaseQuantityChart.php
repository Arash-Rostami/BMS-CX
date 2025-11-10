<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseOrderChart;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class PurchaseQuantityChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use BaseOrderChart;

    protected static ?string $heading = '🛒 Total Order Quantities';

    protected static ?string $maxHeight = '250px';

    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $filterType = $this->filter ?? 'category';
        $chartData = $this->getPurchaseQuantityData($filterType);

        $labels = array_column($chartData, 'name');
        $data = array_column($chartData, 'total_quantity');

        $datasets = [];

        $label = ucfirst($filterType);
        $color = $this->getBackgroundColor();

        $datasets[] = [
            'label' => $label,
            'data' => $data,
            'backgroundColor' => $color,
            'borderColor' => $color,
            'hoverOffset' => 4,
            'type' => 'doughnut',
        ];

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
            ],
        ];
    }

    protected function getFilters(): ?array
    {
        return ['category' => 'Categories', 'product' => 'Products'];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    private function buildPurchaseQuantitySql(string $filterType): string
    {
        $nameColumn = $filterType === 'category' ? 'c.name' : 'p.name';

        return <<<SQL
SELECT
    {$nameColumn} AS name,
    SUM(
        COALESCE(
            od.final_quantity,
            od.provisional_quantity,
            od.buying_quantity,
            0
        )
    ) AS total_quantity
FROM order_details od
JOIN orders o
    ON o.order_detail_id = od.id
JOIN products p
    ON o.product_id = p.id
JOIN categories c
    ON p.category_id = c.id
WHERE 1=1
  AND o.deleted_at IS NULL
SQL;
    }

    private function getPurchaseQuantityData(string $filterType): array
    {
        $bindings = [];

        $query = $this->buildPurchaseQuantitySql($filterType);
        $query = $this->buildQuery($query, $bindings);

        if ($filterType === 'category') {
            $query .= ' GROUP BY c.name';
        } else {
            $query .= ' GROUP BY p.name';
        }

        return DB::select($query, $bindings);
    }
}
