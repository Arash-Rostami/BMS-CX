<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseOrderChart;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OrderGapProformaBlChart extends ChartWidget
{
    use InteractsWithPageFilters, BaseOrderChart;

    protected static ?string $heading = '🕒 Gap Between Proforma & BL Date';

    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $filterType = $this->filter ?? 'quantity';
        $gapData = $this->getOrderGapData();

        return $this->prepareGapChartData($gapData, $filterType, 'supplier_name');
    }

    protected function getFilters(): ?array
    {
        return ['quantity' => 'Days', 'percentage' => 'Percentage'];
    }

    public function getOrderGapData()
    {
        $bindings = [];
        $query = "
            SELECT
                s.name AS supplier_name,
                AVG(DATEDIFF(d.BL_date, o.proforma_date)) AS gap_days,
                COUNT(*) AS order_count
            FROM orders o
            JOIN parties p ON o.party_id = p.id
            JOIN suppliers s ON p.supplier_id = s.id
            JOIN docs d ON o.doc_id = d.id
            WHERE o.proforma_date IS NOT NULL
              AND d.BL_date IS NOT NULL
        ";

        return $this->processGapOrders($query, $bindings);
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

