<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    public $shipmentStatusFilter;

    protected $listeners = ['setShipmentStatusFilter'];


    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-sparkles'),
            ExcelImportAction::make()
                ->color("success"),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return OrderResource::getWidgets();
    }

    public function setShipmentStatusFilter($filter)
    {
        $this->shipmentStatusFilter = $filter === 'total' ? null : $filter;
        $this->resetPage();
    }

    protected function getTableQuery(): Builder
    {
        $query = self::getOriginalTable();

        if ($this->shipmentStatusFilter) {
            $query->where('purchase_status_id', $this->shipmentStatusFilter);
        }

        return $query;
    }

    public function getTabs(): array
    {
        return [
            null => Tab::make('All')->query(fn($query) => $query),
            'Pending' => Tab::make()->query(fn($query) => $query->where('purchase_status_id', 1)),
            'In Transit' => Tab::make()->query(fn($query) => $query->where('purchase_status_id', 3)),
            'Customs' => Tab::make()->query(fn($query) => $query->where('purchase_status_id', 4)),
            'Delivered' => Tab::make()->query(fn($query) => $query->where('purchase_status_id', 5)),
            'Shipped' => Tab::make()->query(fn($query) => $query->where('purchase_status_id', 6)),
            'Released' => Tab::make()->query(fn($query) => $query->where('purchase_status_id', 2)),
        ];
    }

    private static function getOriginalTable()
    {
        return static::getResource()::getEloquentQuery();
    }
}
