<?php

namespace App\Filament\Resources\Operational\BalanceResource\Pages;

use App\Filament\Resources\BalanceResource;
use App\Models\Balance;
use App\Models\Department;
use App\Models\Payment;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;

class ManageBalances extends ManageRecords
{
    protected static string $resource = BalanceResource::class;

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
        $counts = Balance::getTabCounts();

        $tabs = [
            'all' => Tab::make('All')
                ->query(fn($query) => $query)
                ->badge($counts['total'])
                ->icon('heroicon-o-inbox'),
        ];

        foreach ($counts['departments'] as $departmentData) {
            $tabs[$departmentData['code']] = Tab::make($departmentData['code'])
                ->query(fn($query) => $query->where('department_id', $departmentData['department_id']))
                ->badge($departmentData['count'] ?? 0)
                ->icon('heroicon-o-building-office');
        }

        return $tabs;
    }
}
