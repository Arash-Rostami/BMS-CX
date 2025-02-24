<?php

namespace App\Filament\Pages\Trait;

use App\Filament\Widgets\OrderDateChart;
use App\Filament\Widgets\OrderDeliveryTermChart;
use App\Filament\Widgets\OrderPackagingChart;
use App\Filament\Widgets\OrderPurchaseStatusChart;
use App\Filament\Widgets\OrderSupplierChart;
use App\Filament\Widgets\PurchaseQuantityChart;

trait CxDashboard
{
    protected function getCxWidgets(): array
    {
        return [
            OrderSupplierChart::class,
            OrderDeliveryTermChart::class,
            OrderPurchaseStatusChart::class,
            OrderDateChart::class,
            PurchaseQuantityChart::class,
            OrderPackagingChart::class,
        ];
    }
}
