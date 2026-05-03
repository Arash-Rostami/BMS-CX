<?php

namespace App\Filament\Resources\Dashboard\ContractOverviewResource\Pages;

use App\Filament\Resources\ContractOverviewResource;
use App\Filament\Resources\Dashboard\ContractOverviewResource\Widgets\ContractStatsWidget;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListContractOverviews extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ContractOverviewResource::class;


    protected function getHeaderWidgets(): array
    {

        return [
            ContractStatsWidget::class
        ];
    }

}
