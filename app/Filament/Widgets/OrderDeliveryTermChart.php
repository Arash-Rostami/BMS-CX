<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseOrderChart;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class OrderDeliveryTermChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use BaseOrderChart;

    protected static ?string $heading = '🚚 Delivery Terms Distribution';

    protected static ?string $maxHeight = '250px';

    protected static ?string $pollingInterval = null;

    public function getOrderDataByDeliveryTerm()
    {
        $bindings = [];

        $query = $this->buildDeliveryTermSql();
        $query = $this->buildQuery($query, $bindings)
            . ' GROUP BY dt.name';

        return $this->processOrders(
            DB::select($query, $bindings),
            'delivery_term_name'
        );
    }

    protected function getData(): array
    {
        $filterType       = $this->filter ?? 'quantity';
        $deliveryTermData = $this->getOrderDataByDeliveryTerm();

        return $this->prepareChartData(
            $deliveryTermData,
            $filterType,
            'delivery_term_name',
            'pie',
            'pie'
        );
    }

    protected function getFilters(): ?array
    {
        return [
            'quantity'   => 'Quantity',
            'percentage' => 'Percentage',
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    private function buildDeliveryTermSql(): string
    {
        return <<<SQL
SELECT
    COALESCE(dt.name, 'Undefined') AS delivery_term_name,
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
LEFT JOIN delivery_terms dt
    ON l.delivery_term_id = dt.id
WHERE 1=1
  AND o.deleted_at IS NULL
SQL;
    }
}
