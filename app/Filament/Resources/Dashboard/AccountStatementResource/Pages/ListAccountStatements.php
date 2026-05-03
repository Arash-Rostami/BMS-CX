<?php

namespace App\Filament\Resources\Dashboard\AccountStatementResource\Pages;

use App\Filament\Resources\AccountStatementResource;
use App\Filament\Resources\Dashboard\AccountStatementResource\Widgets\AccountStatementStatsWidget;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListAccountStatements extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = AccountStatementResource::class;

    public function getTableRecordKey(Model $record): string
    {
        return $record->account_id . '-' . $record->transaction_date;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AccountStatementStatsWidget::class,
        ];
    }
}
