<?php

namespace App\Filament\Resources\Dashboard\LoanSummaryResource\Pages;

use App\Filament\Resources\Dashboard\LoanSummaryResource\Widgets\LoanSummaryStatsWidget;
use App\Filament\Resources\LoanSummaryResource;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListLoanSummaries extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = LoanSummaryResource::class;

    public function getTableRecordKey(Model $record): string
    {
        return (string)$record->id;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LoanSummaryStatsWidget::class
        ];
    }
}
