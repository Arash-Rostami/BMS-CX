<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseOrderChart;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class OrderPackagingChart extends ChartWidget
{
    use InteractsWithPageFilters, BaseOrderChart;

    protected static ?string $heading = '📦 Packaging Types Distribution';

    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $filterType = $this->filter ?? 'quantity';
        $packagingData = $this->getPackagingData();

        return $this->prepareChartData($packagingData, $filterType, 'packaging_name');
    }

    protected function getFilters(): ?array
    {
        return ['quantity' => 'Quantity', 'percentage' => 'Percentage'];
    }

    public function getPackagingData()
    {

        $bindings = [];
        $query = "
            SELECT
                p.name AS packaging_name,
                SUM(COALESCE(od.final_quantity, od.provisional_quantity, od.buying_quantity, 0)) AS quantity
            FROM order_details od
            JOIN orders o ON od.id = o.order_detail_id
            JOIN logistics l ON l.id = o.logistic_id
            JOIN packagings p ON l.packaging_id = p.id
            WHERE 1=1
        ";

        $query = $this->buildQuery($query, $bindings);

        $query .= " GROUP BY p.name";

        $orders = DB::select($query, $bindings);

        return $this->processOrders($orders, 'packaging_name');
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
