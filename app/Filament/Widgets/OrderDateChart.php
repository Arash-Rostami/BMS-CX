<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseOrderChart;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class OrderDateChart extends ChartWidget
{
    use InteractsWithPageFilters, BaseOrderChart;

    protected static ?string $heading = '📆 Monthly Order Distribution';

    protected static ?string $maxHeight = '250px';


    protected function getData(): array
    {
        $filterType = $this->filter ?? 'quantity';

        $monthlyData = $this->getMonthlyOrderData();

        $data = $filterType === 'quantity' ?
            array_column($monthlyData, 'quantity') :
            array_column($monthlyData, 'percentage');


        $datasets = [];

        $label = ($filterType === 'quantity') ? 'Quantity by Month' : 'Percentage by Month';
        $color = $this->getBackgroundColor();

        $datasets[] = [
            'label' => $label,
            'data' => $data,
            'backgroundColor' => $color,
            'borderColor' => $color,
            'tension' => 0.1,
            'hoverOffset' => 4,
            'type' => 'line',
        ];

        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'datasets' => $datasets,
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'animations' => [
                    'duration' => 1000,
                    'easing' => 'linear',
                    'from' => 1,
                    'to' => 0,
                    'loop' => false,
                ],
            ],
        ];
    }

    protected function getFilters(): ?array
    {
        return ['quantity' => 'Quantity', 'percentage' => 'Percentage'];
    }

    private function getMonthlyOrderData()
    {

        $bindings = [];
        $query = "
            SELECT
                MONTH(o.proforma_date) as month,
                SUM(COALESCE(od.final_quantity, od.provisional_quantity, od.buying_quantity, 0)) AS quantity
            FROM orders o
            JOIN order_details od ON o.order_detail_id = od.id
            WHERE 1=1
        ";

        $query = $this->buildQuery($query, $bindings);

        $query .= " GROUP BY month ORDER BY month";

        $monthlyData = DB::select($query, $bindings);

        $monthlyResults = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyResults[$i] = [
                'quantity' => 0,
                'percentage' => 0,
            ];
            foreach ($monthlyData as $item) {
                if ($item->month == $i) {
                    $monthlyResults[$i]['quantity'] = $item->quantity;
                    break;
                }
            }
        }

        $totalQuantity = array_sum(array_column($monthlyResults, 'quantity'));

        foreach ($monthlyResults as &$result) {
            $result['percentage'] = $totalQuantity > 0 ? ($result['quantity'] / $totalQuantity) * 100 : 0;
        }

        return $monthlyResults;
    }


    protected function getType(): string
    {
        return 'line';
    }
}
