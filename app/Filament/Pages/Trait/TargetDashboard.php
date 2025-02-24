<?php

namespace App\Filament\Pages\Trait;

use App\Filament\Widgets\OrderGapProformaBlChart;
use App\Filament\Widgets\OrderGapProformaDeclarationChart;
use App\Filament\Widgets\PurchaseTargetChart;
use App\Filament\Widgets\SalesTargetChart;
use App\Filament\Widgets\TargetStatsOverview;

trait TargetDashboard
{
    protected function getTargetWidgets(): array
    {
        return [
            TargetStatsOverview::class,
            PurchaseTargetChart::class,
            SalesTargetChart::class,
            OrderGapProformaBlChart::class,
            OrderGapProformaDeclarationChart::class,
        ];
    }
}
