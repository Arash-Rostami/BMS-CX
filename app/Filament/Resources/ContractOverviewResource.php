<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Operational\ContractOverviewResource\Pages;
use App\Filament\Resources\Operational\ContractOverviewResource\Pages\Admin;
use App\Models\ContractOverview;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContractOverviewResource extends Resource
{
    use ExposesTableToWidgets;

    protected static ?string $model = ContractOverview::class;


    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $pluralModelLabel = 'Contracts Overview';

    protected static bool $isGloballySearchable = false;
    protected static bool $canCreate = false;


    public static function getPages(): array
    {
        return ['index' => Pages\ListContractOverviews::route('/')];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordClasses(fn(Model $record) => ($record->order_deleted_at != NULL) ? 'bg-cancelled' : '')
            ->modifyQueryUsing(fn(Builder $query) => $query
                ->whereNull('pi_deleted_at')
                ->where('pi_status', '!=', 'rejected')
                ->whereNull('order_deleted_at')
            )
            ->columns([
                Admin::showPiNo(),
                Admin::showOrderPart(),
                Admin::showPiDate(),
                Admin::showCategory(),
                Admin::showPiQuantity(),
                Admin::showOrderQuantity(),
                Admin::showBlDate(),
                Admin::showShipmentSatus(),
                Admin::showOrderStatus(),
            ])
            ->filters([
                Admin::filterByCategory(),
                Admin::filterByPiDate(),
                Admin::filterByBlDate(),
                Admin::filterByOrderStatus(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->paginated(['10', '15'])
            ->groups([Admin::groupByCtNo()])
            ->defaultGroup('pi_contract', 'desc')
            ->groupingSettingsInDropdownOnDesktop()
            ->actions([])
            ->searchable(false)
            ->searchDebounce('1200ms')
            ->bulkActions([]);
    }
}
