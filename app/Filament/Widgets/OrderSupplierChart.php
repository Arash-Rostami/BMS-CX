<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseOrderChart;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class OrderSupplierChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use BaseOrderChart;

    protected static ?string $heading = '🏢 Supplier Distribution';

    protected static ?string $maxHeight = '250px';

    protected static ?string $pollingInterval = null;


    public function getOrderDataBySupplier()
    {
        $bindings = [];
        $query = $this->buildOrderSupplierSql();

        $query = $this->buildQuery($query, $bindings)
            . ' GROUP BY s.name';


        return $this->processOrders(
            DB::select($query, $bindings),
            'supplier_name');
    }

    protected function getData(): array
    {
        $filterType = $this->filter ?? 'quantity';
        $supplierData = $this->getOrderDataBySupplier();

        return $this->prepareChartData($supplierData, $filterType, 'supplier_name');
    }

    protected function getFilters(): ?array
    {
        return ['quantity' => 'Quantity', 'percentage' => 'Percentage',];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    private function buildOrderSupplierSql(): string
    {
        return <<<SQL
SELECT
    s.name AS supplier_name,
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
    ON od.id = o.order_detail_id
JOIN parties p
    ON o.party_id = p.id
JOIN suppliers s
    ON p.supplier_id = s.id
WHERE 1=1
SQL;
    }
}
