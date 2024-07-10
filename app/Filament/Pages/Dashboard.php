<?php

namespace App\Filament\Pages;


use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('yearlyOrders')
                    ->label('Filter Orders by Year')
                    ->options([
                        'all' => 'All',
                        '2023' => '2023',
                        '2024' => '2024'
                    ]),
            ])
            ->columns(2);
    }
}

