<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseOrderChart;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class PurchaseQuantityChart extends ChartWidget
{
    use InteractsWithPageFilters, BaseOrderChart;

    protected static ?string $heading = '🛒 Total Purchase Quantities';

    protected static ?string $maxHeight = '250px';

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

    private function getPurchaseQuantityData($filterType)
    {

        $bindings = [];
        $query = "SELECT ";

        if ($filterType === 'category') {
            $query .= "c.name AS name, ";
        } else {
            $query .= "p.name AS name, ";
        }

        $query .= "
        SUM(COALESCE(od.final_quantity, od.provisional_quantity, od.buying_quantity, 0)) AS total_quantity
            FROM order_details od
            JOIN orders o ON o.order_detail_id = od.id
            JOIN products p ON o.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            WHERE 1=1";


        $query = $this->buildQuery($query, $bindings);

        if ($filterType === 'category') {
            $query .= " GROUP BY c.name";
        } else {
            $query .= " GROUP BY p.name";
        }

        return DB::select($query, $bindings);
    }


    protected function getType(): string
    {
        return 'doughnut';
    }
}
