<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseOrderChart;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class OrderPurchaseStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use BaseOrderChart;

    protected static ?string $heading = '🚢 Order Shipment Distribution';

    protected static ?string $maxHeight = '250px';

    protected static ?string $pollingInterval = null;

    public function getOrderDataByPurchaseStatus()
    {
        $bindings = [];

        $query = $this->buildPurchaseStatusSql();
        $query = $this->buildQuery($query, $bindings)
            . ' GROUP BY ps.name';

        return $this->processOrders(
            DB::select($query, $bindings),
            'purchase_status_name'
        );
    }

    protected function getData(): array
    {
        $filterType = $this->filter ?? 'quantity';
        $purchaseStatusData = $this->getOrderDataByPurchaseStatus();

        return $this->prepareChartData(
            $purchaseStatusData,
            $filterType,
            'purchase_status_name'
        );
    }

    protected function getFilters(): ?array
    {
        return [
            'quantity' => 'Quantity',
            'percentage' => 'Percentage',
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    private function buildPurchaseStatusSql(): string
    {
        return <<<SQL
SELECT
    ps.name AS purchase_status_name,
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
JOIN purchase_statuses ps
    ON o.purchase_status_id = ps.id
WHERE 1=1
SQL;
    }
}
