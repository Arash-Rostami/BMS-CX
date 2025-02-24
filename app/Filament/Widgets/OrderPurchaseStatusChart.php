<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseOrderChart;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class OrderPurchaseStatusChart extends ChartWidget
{
    use InteractsWithPageFilters, BaseOrderChart;

    protected static ?string $heading = '🚢 Cargo Status Distribution';

    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $filterType = $this->filter ?? 'quantity'; // Default to 'quantity'

        $purchaseStatusData = $this->getOrderDataByPurchaseStatus();

        return $this->prepareChartData($purchaseStatusData, $filterType, 'purchase_status_name');
    }

    protected function getFilters(): ?array
    {
        return ['quantity' => 'Quantity', 'percentage' => 'Percentage'];
    }

    public function getOrderDataByPurchaseStatus()
    {
        $bindings = [];
        $query = "
            SELECT
                ps.name AS purchase_status_name,
                SUM(COALESCE(od.final_quantity, od.provisional_quantity, od.buying_quantity, 0)) AS quantity
            FROM order_details od
            JOIN orders o ON od.id = o.order_detail_id
            JOIN purchase_statuses ps ON o.purchase_status_id = ps.id
            WHERE 1=1
        ";

        $query = $this->buildQuery($query, $bindings);

        $query .= " GROUP BY ps.name";

        $orders = DB::select($query, $bindings);

        return $this->processOrders($orders, 'purchase_status_name');
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
