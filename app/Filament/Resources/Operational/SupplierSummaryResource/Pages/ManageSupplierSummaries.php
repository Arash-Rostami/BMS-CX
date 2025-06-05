<?php

namespace App\Filament\Resources\Operational\SupplierSummaryResource\Pages;

use App\Filament\Resources\SupplierSummaryResource;
use App\Models\SupplierSummary;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;


class ManageSupplierSummaries extends ManageRecords
{
    protected static string $resource = SupplierSummaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-sparkles')
                ->createAnother(false),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All')
                ->badge(SupplierSummary::distinct('supplier_id')->count())
                ->icon('heroicon-o-inbox')
        ];

        return array_reduce(
            array_keys(SupplierSummary::getTabCounts()),
            function ($carry, $status) {
                $supplierIds = SupplierSummary::getTabCounts()[$status];
                $carry[$status] = Tab::make($status)
                    ->query(fn(Builder $query) => $query->whereIn('supplier_id', $supplierIds))
                    ->badge(count($supplierIds))
                    ->icon(match (strtolower($status)) {
                        'overpaid' => 'heroicon-o-currency-dollar',
                        'underpaid' => 'heroicon-o-exclamation-circle',
                        'settled' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-document-text',
                    });
                return $carry;
            },
            $tabs
        );
    }
}
