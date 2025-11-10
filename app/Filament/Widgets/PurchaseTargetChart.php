<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseTargetChart;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class PurchaseTargetChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use BaseTargetChart;

    protected static ?string $heading = '🛍️ Procured Proformas (measured by PI)';

    protected static ?string $maxHeight = '350px';

    protected static ?string $pollingInterval = null;

    public function getPurchaseTargetData()
    {
        $filters = $this->getAllFilters();
        $year = $this->normalizeYear($filters['year']);

        $query = $this->buildPurchaseTargetSql();
        $bindings = [$year];
        $this->addMonthFilter($query, $bindings, 'pi.proforma_date');
        $query = $this->buildQuery($query, $bindings)
            . ' GROUP BY c.name, t.target_quantity, t.modified_target_quantity, t.month';

        return $this->processChartData(
            DB::select($query, $bindings),
            $filters['month']
        );
    }

    protected function getData(): array
    {
        $filterType = $this->filter ?? 'quantity';
        $chartData = $this->getPurchaseTargetData();

        return $this->prepareChartData($chartData, $filterType, 'category_name');
    }

    protected function getFilters(): ?array
    {
        return ['quantity' => 'Quantity', 'gap' => 'Gap', 'percentage' => 'Percentage'];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    private function buildPurchaseTargetSql(): string
    {
        return <<<SQL
SELECT
    c.name AS category_name,
    SUM(COALESCE(pi.quantity, 0)) AS realized_quantity,
    t.target_quantity,
    t.modified_target_quantity,
    t.month
FROM targets t
LEFT JOIN categories c
    ON c.id = t.category_id
LEFT JOIN proforma_invoices pi
    ON pi.category_id = c.id
    AND pi.status != 'rejected'
    AND pi.deleted_at IS NULL
    AND YEAR(pi.proforma_date) = ?
SQL;
    }
}
