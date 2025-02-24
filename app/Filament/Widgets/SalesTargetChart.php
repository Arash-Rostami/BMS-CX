<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseTargetChart;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class SalesTargetChart extends ChartWidget
{
    use InteractsWithPageFilters, BaseTargetChart;

    protected static ?string $heading = '🚢 Shipped Proformas (by BL Date)';

    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $filterType = $this->filter ?? 'quantity';
        $chartData = $this->getSalesTargetData();

        return $this->prepareChartData($chartData, $filterType, 'category_name');
    }

    protected function getFilters(): ?array
    {
        return ['quantity' => 'Quantity', 'gap' => 'Gap', 'percentage' => 'Percentage'];
    }

    public function getSalesTargetData()
    {
        $bindings = [];
        $filters = $this->getAllFilters();

        $year = $filters['year'] !== 'all' ? $filters['year'] : date('Y');

        $query = "
            SELECT
                c.name AS category_name,
                SUM(COALESCE(
                    CASE
                        WHEN YEAR(d.BL_date) = ? THEN pi.quantity
                        ELSE 0
                    END, 0)) AS realized_quantity,
                t.target_quantity,
                t.modified_target_quantity,
                t.month
            FROM targets t
            LEFT JOIN categories c ON c.id = t.category_id
            LEFT JOIN orders o ON o.category_id = c.id AND o.deleted_at IS NULL
            LEFT JOIN docs d ON d.id = o.doc_id
            LEFT JOIN proforma_invoices pi ON pi.id = o.proforma_invoice_id AND pi.deleted_at IS NULL AND pi.status != 'rejected'
    ";

        $bindings[] = $year;
        $this->addMonthFilter($query, $bindings, 'd.BL_date');
        $query = $this->buildQuery($query, $bindings, true);
        $query .= " GROUP BY c.name, t.target_quantity, t.modified_target_quantity, t.month";
        $orders = DB::select($query, $bindings);

        return $this->processChartData($orders, $filters['month']);
    }
    protected function getType(): string
    {
        return 'bar';
    }
}
