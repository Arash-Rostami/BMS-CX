<?php

namespace App\Filament\Resources\Operational\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ExcelImportAction::make()
                ->color("success"),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return OrderResource::getWidgets();
    }

    public function getTabs(): array
    {
        return [
            null => Tab::make('All'),
            'Pending' => Tab::make()->query(fn ($query) => $query->where('purchase_status_id', 1)),
            'In Transit' => Tab::make()->query(fn ($query) => $query->where('purchase_status_id', 2)),
            'Customs' => Tab::make()->query(fn ($query) => $query->where('purchase_status_id', 3)),
            'Delivered' => Tab::make()->query(fn ($query) => $query->where('purchase_status_id', 4)),
            'Shipped' => Tab::make()->query(fn ($query) => $query->where('purchase_status_id', 5)),
        ];
    }
}
