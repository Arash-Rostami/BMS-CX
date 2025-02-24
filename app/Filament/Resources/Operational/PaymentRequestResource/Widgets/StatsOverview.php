<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Widgets;

use App\Models\PaymentRequest;
use App\Services\IconMaker;
use App\Services\Repository\PaymentRequestRepository;
use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $repository = new PaymentRequestRepository();
        $repository
            ->filterByAttributes([
                'department' => $this->filters['department'] ?? null,
                'currency' => $this->filters['currency'] ?? null,
                'payment_type' => $this->filters['payment_type'] ?? null,
                'status' => $this->filters['status'] ?? null,

            ]);
        $stats = $repository->retrieveFilteredSummary();

        return [
            Stat::make('Total Requested Amount', formatCurrency($stats['total_requested_amount']))
                ->color('success')
                ->extraAttributes([
                    'style' => 'transform: scale(1.2); border-left: 5px solid #57D9A3;'])
                ->icon('heroicon-o-currency-dollar')
                ->description('Sum of all requested amounts'),

            Stat::make('Total Rial Beneficiaries', $stats['total_beneficiaries'])
                ->color('primary')
                ->extraAttributes(['style' => 'transform: scale(1.2); border-left: 5px solid #FFC402;'])
                ->icon('heroicon-o-identification')
                ->description('Distinct beneficiaries receiving payments in Rial'),

            Stat::make('Total Contractors', $stats['total_contractors'])
                ->color('secondary')
                ->extraAttributes(['style' => 'transform: scale(1.2); border-left: 5px solid #03C7E5;'])
                ->icon('heroicon-m-arrow-up-on-square-stack')
                ->description('Number of unique contractors involved'),

            Stat::make('Total Suppliers', $stats['total_suppliers'])
                ->color('info')
                ->extraAttributes(['style' => 'transform: scale(1.2); border-left: 5px solid #F99CDB;'])
                ->icon('heroicon-o-arrow-up-on-square-stack')
                ->description('Total unique suppliers providing goods or services'),
        ];
    }
}
