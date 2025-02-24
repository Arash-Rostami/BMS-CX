<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseOrderChart;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class OrderDeliveryTermChart extends ChartWidget
{
    use InteractsWithPageFilters, BaseOrderChart;

    protected static ?string $heading = '🚚 Delivery Terms Distribution';

    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $filterType = $this->filter ?? 'quantity';
        $deliveryTermData = $this->getOrderDataByDeliveryTerm();

        return $this->prepareChartData($deliveryTermData, $filterType, 'delivery_term_name', 'pie' , 'pie');
    }

    protected function getFilters(): ?array
    {
        return ['quantity' => 'Quantity', 'percentage' => 'Percentage',];
    }

    public function getOrderDataByDeliveryTerm()
    {
        $bindings = [];
        $query = "
            SELECT
                dt.name AS delivery_term_name,
                SUM(COALESCE(od.final_quantity, od.provisional_quantity, od.buying_quantity, 0)) AS quantity
            FROM order_details od
            JOIN orders o ON od.id = o.order_detail_id
            JOIN logistics l ON o.logistic_id = l.id
            JOIN delivery_terms dt ON l.delivery_term_id = dt.id
            WHERE 1=1
        ";

        $query = $this->buildQuery($query, $bindings);
        $query .= " GROUP BY dt.name";

        $orders = DB::select($query, $bindings);

        return $this->processOrders($orders, 'delivery_term_name');
    }
    protected function getType(): string
    {
        return 'pie';
    }
}
