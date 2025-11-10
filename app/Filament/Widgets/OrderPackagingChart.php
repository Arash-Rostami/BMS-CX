<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseOrderChart;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class OrderPackagingChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use BaseOrderChart;

    protected static ?string $heading = '📦 Order Packaging Distribution';

    protected static ?string $maxHeight = '250px';

    protected static ?string $pollingInterval = null;

    public function getPackagingData()
    {
        $bindings = [];

        $query = $this->buildPackagingSql();
        $query = $this->buildQuery($query, $bindings)
            . ' GROUP BY p.name';


        return $this->processOrders(
            DB::select($query, $bindings),
            'packaging_name'
        );
    }


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

    protected function getType(): string
    {
        return 'bar';
    }

    private function buildPackagingSql(): string
    {
        return <<<SQL
SELECT
    COALESCE(p.name, 'Undefined') AS packaging_name,
    SUM(
        COALESCE(
            od.final_quantity,
            od.provisional_quantity,
            od.buying_quantity,
            0
        )
    ) AS quantity
FROM order_details od
JOIN orders o
    ON o.order_detail_id = od.id
JOIN logistics l
    ON o.logistic_id = l.id
LEFT JOIN packagings p
    ON l.packaging_id = p.id
WHERE 1=1
  AND o.deleted_at IS NULL
SQL;
    }
}
