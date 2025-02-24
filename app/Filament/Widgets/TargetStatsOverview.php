<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Trait\BaseTargetChart;
use App\Models\Category;
use App\Models\Doc;
use App\Models\ProformaInvoice;
use App\Models\Target;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;


class TargetStatsOverview extends BaseWidget
{
    use BaseTargetChart, InteractsWithPageFilters;

    protected function getStats(): array
    {
        list($year, $categoryId, $month, $monthName) = $this->fetchFilters();

        $latestBlDate = Doc::getLatestBLDate();
        $latestProformaDate = ProformaInvoice::getLatestProformaDate();
        $totalProformaQty = ProformaInvoice::getTotalQuantityByYearAndCategoryAndMonth($year, $categoryId, $month);
        $totalProformaQtyWithBlDate = ProformaInvoice::getTotalQuantityWithBLDateByFilters($year, $categoryId, $month);
        $totalTargetQty = Target::getTotalTargetQuantityByYearCategoryAndMonth($year, $categoryId, $monthName);

        $purchasePercentage = $this->calculatePercentage($totalTargetQty, $totalProformaQty);
        $salesPercentage = $this->calculatePercentage($totalTargetQty, $totalProformaQtyWithBlDate);
        $categoryName = $this->fetchCategoryName($categoryId);

        return [
            //PURCHASED PROFORMA INVOICES
            Stat::make('Latest PI Date', $latestProformaDate)
                ->icon('heroicon-s-calendar')
                ->extraAttributes(['style' => 'transform: scale(1.2); border-left: 5px solid #57D9A3;']),

            Stat::make('Realized Purchase Target', number_format($totalProformaQty) . '/' . number_format($totalTargetQty))
                ->icon('heroicon-s-chart-bar')
                ->description("For {$this->filters['yearlyOrders']} " . ($this->filters['category_id'] ? " ┆ $categoryName: {$purchasePercentage}%" : "┆ All Categories:  {$purchasePercentage}%"))
                ->descriptionIcon('heroicon-s-information-circle')
                ->color('success')
                ->extraAttributes(['style' => 'transform: scale(1.2); border-left: 5px solid #FFC402;']),

            //SHIPPED PROFORMA INVOICES
            Stat::make('Latest BL Date', $latestBlDate)
                ->icon('heroicon-s-calendar')
                ->extraAttributes(['style' => 'transform: scale(1.2); border-left: 5px solid #03C7E5;']),

            Stat::make('Realized Sales Target', number_format($totalProformaQtyWithBlDate) . '/' . number_format($totalTargetQty))
                ->icon('heroicon-s-chart-bar')
                ->description("For {$this->filters['yearlyOrders']} " . ($this->filters['category_id'] ? " ┆ $categoryName: {$salesPercentage}%" : "┆ All Categories: {$salesPercentage}%"))
                ->descriptionIcon('heroicon-s-information-circle')
                ->color('primary')
                ->extraAttributes(['style' => 'transform: scale(1.2); border-left: 5px solid #F99CDB;'])
        ];
    }

    protected function fetchCategoryName($categoryIds)
    {
        if (is_array($categoryIds)) {
            return Category::whereIn('id', $categoryIds)
                ->pluck('name')
                ->implode(', ');
        }
        return Category::find($categoryIds)?->name;
    }

    protected function calculatePercentage($total, $partial)
    {
        return $total > 0 ? round(($partial / $total) * 100, 2) : 0;
    }

    protected function fetchFilters(): array
    {
        $year = $this->filters['yearlyOrders'] ?? null;
        $categoryId = $this->filters['category_id'] ?? null;
        $month = $this->filters['monthlyOrders'] ?? null;
        $monthName = !empty($month) ? $this->generateMonthName((int)$month) : null;

        return [$year, $categoryId, $month, $monthName];
    }
}
