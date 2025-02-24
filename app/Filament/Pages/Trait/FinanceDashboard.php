<?php

namespace App\Filament\Pages\Trait;

use App\Filament\Resources\Operational\PaymentRequestResource\Widgets\StatsOverview;
use App\Filament\Widgets\BalanceBarChart;
use App\Filament\Widgets\ListOfBeneficiaries;
use App\Filament\Widgets\PaymentGapTime;
use App\Filament\Widgets\PaymentRequestTotalChart;


trait FinanceDashboard
{
    protected function getFinanceWidgets(): array
    {
        return [
            StatsOverview::class,
            PaymentRequestTotalChart::class,
            PaymentGapTime::class,
            BalanceBarChart::class,
            ListOfBeneficiaries::class
        ];
    }
}
