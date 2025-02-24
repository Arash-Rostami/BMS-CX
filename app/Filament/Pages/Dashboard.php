<?php

namespace App\Filament\Pages;


use App\Filament\Pages\Trait\DashboardFilters;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\FilamentInfoWidget;
use App\Filament\Pages\Trait\CxDashboard;
use App\Filament\Pages\Trait\TargetDashboard;
use App\Filament\Pages\Trait\FinanceDashboard;
use Filament\Actions\Action; // Import the Action class
use Livewire\Livewire;     // Import the Livewire facade

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;
    use DashboardFilters;
    use CxDashboard;
    use FinanceDashboard;
    use TargetDashboard;
    use InteractsWithPageFilters;

    protected function getHeaderWidgets(): array
    {
        return [
            AccountWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            FilamentInfoWidget::class,
        ];
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema($this->loadFilterOptions());
    }

    public function getWidgets(): array
    {
        $selectedType = $this->filters['type'] ?? 'ac';

        $widgets = [];

        if ($selectedType === 'ac') {
            $widgets = array_merge($widgets, $this->getFinanceWidgets());
        }

        if ($selectedType === 'cx') {
            $widgets = array_merge($widgets, $this->getCxWidgets());
        }

        if ($selectedType === 'target') {
            $widgets = array_merge($widgets, $this->getTargetWidgets());
        }

        return $widgets;
    }

//    protected function getActions(): array
//    {
//        return [
//            Action::make('openUserDashboard')
//            ->label('My Dashboard')
//                ->button()
//                ->modalContent(fn () => view('components.livewireComponent'))
//                ->modalWidth(MaxWidth::Screen)
//                ->modalHeading('Your Dashboard')
//                ->slideOver()
//
//
//        ];
//    }
}

