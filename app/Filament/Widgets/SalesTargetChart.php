<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseTargetChart;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class SalesTargetChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use BaseTargetChart;

    protected static ?string $heading = '🚢 Shipped or Released Orders';

    protected static ?string $maxHeight = '350px';

    protected static ?string $pollingInterval = null;

    public function getSalesTargetData()
    {
        $filters = $this->getAllFilters();
        $monthClause = $this->buildMonthClause($filters['month']);
        $year = $this->normalizeYear($filters['year']);

        $sql = $this->buildSalesTargetSql($monthClause);
        $bindings = $this->buildSalesTargetBindings($year, $filters['month']);
        $this->addMonthFilter($sql, $bindings, 'd.BL_date');
        $fullQuery = $this->buildQuery($sql, $bindings, true)
            . ' GROUP BY c.name, t.target_quantity, t.modified_target_quantity, t.month';

        return $this->processChartData(
            DB::select($fullQuery, $bindings),
            $filters['month']
        );
    }

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

    protected function getType(): string
    {
        return 'bar';
    }

    private function buildSalesTargetBindings(int $year, array|string $months): array
    {
        $bindings = [$year];

        if (!empty($months)) {
            $months = is_array($months) ? $months : [$months];
            $bindings = array_merge($bindings, $months);
        }

        return $bindings;
    }

    private function buildSalesTargetSql(string $monthClause): string
    {
        return <<<SQL
SELECT
    c.name AS category_name,
    SUM(
      COALESCE(
        CASE
          WHEN YEAR(d.BL_date) = ? THEN COALESCE(
            od.final_quantity,
            od.provisional_quantity,
            od.buying_quantity,
            0
          )
          ELSE 0
        END,
        0
      )
    ) AS realized_quantity,
    t.target_quantity,
    t.modified_target_quantity,
    t.month
FROM targets t
LEFT JOIN categories c
  ON c.id = t.category_id
LEFT JOIN orders o
  ON o.category_id = c.id
  AND o.deleted_at IS NULL
  AND o.purchase_status_id IN (6,2)
LEFT JOIN order_details od
  ON od.id = o.order_detail_id
LEFT JOIN docs d
  ON d.id = o.doc_id{$monthClause}
LEFT JOIN proforma_invoices pi
  ON pi.id = o.proforma_invoice_id
  AND pi.deleted_at IS NULL
  AND pi.status != 'rejected'
SQL;
    }
}
