<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseOrderChart;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OrderGapProformaDeclarationChart extends ChartWidget
{
    use BaseOrderChart;
    use InteractsWithPageFilters;

    protected static ?string $heading = '🕒 Gap Between Proforma & Declaration Date';

    protected static ?string $maxHeight = '250px';

    protected static ?string $pollingInterval = null;


    public function getOrderGapData()
    {
        $bindings = [];
        $query = $this->buildOrderGapProformaDeclarationSql();

        return $this->processGapOrders($query, $bindings);
    }

    protected function getData(): array
    {
        $filterType = $this->filter ?? 'quantity';
        $gapData = $this->getOrderGapData();

        return $this->prepareChartData($gapData, $filterType, 'supplier_name');
    }

    protected function getFilters(): ?array
    {
        return ['quantity' => 'Days', 'percentage' => 'Percentage'];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function prepareChartData($data, $filterType, $nameCol, $chartTypeOne = 'bar', $chartTypeTwo = 'line')
    {
        return $this->prepareGapChartData($data, $filterType, $nameCol, $chartTypeOne, $chartTypeTwo);
    }

    private function buildOrderGapProformaDeclarationSql(): string
    {
        return <<<SQL
SELECT
    s.name AS supplier_name,
    AVG(DATEDIFF(d.declaration_date, o.proforma_date)) AS gap_days,
    COUNT(*) AS order_count
FROM orders o
JOIN parties p
    ON o.party_id = p.id
JOIN suppliers s
    ON p.supplier_id = s.id
JOIN docs d
    ON o.doc_id = d.id
WHERE o.proforma_date IS NOT NULL
  AND o.deleted_at IS NULL
  AND d.declaration_date IS NOT NULL
SQL;
    }
}
