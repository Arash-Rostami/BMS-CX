<?php

namespace App\Filament\Pages\Trait;

use App\Models\Category;
use Carbon\Carbon;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use App\Models\Department;
use App\Models\PaymentRequest;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;


trait DashboardFilters
{
    public function loadFilterOptions()
    {
        return [
            Section::make()
                ->columns(['sm' => 3, 'xl' => 6, '2xl' => 8])
                ->schema([
                    Radio::make('type')
                        ->label('')
                        ->options([
                            'ac' => 'Finance',
                            'cx' => 'CX Logistics',
                            'target' => 'CX Targets',
                        ])
                        ->default('ac')
                        ->extraAttributes(['class' => 'analytics'])
                        ->reactive(),
                    Select::make('yearlyOrders')
                        ->label('Year Filter')
                        ->columns(2)
                        ->options(function () {
                            $currentYear = Carbon::now()->year;
                            $startYear = $currentYear - 1;
                            $endYear = $currentYear + 1;

                            return ['all' => 'All'] + array_combine(
                                    range($startYear, $endYear),
                                    range($startYear, $endYear)
                                );
                        })
                        ->hidden(fn($get) => $get('type') === 'ac')
                        ->disabled(fn($get) => $get('type') === 'ac')
                        ->reactive(),
                    Select::make('monthlyOrders')
                        ->label('Month Filter')
                        ->columns(2)
                        ->options([
                            'all' => 'All',
                            '01' => 'January',
                            '02' => 'February',
                            '03' => 'March',
                            '04' => 'April',
                            '05' => 'May',
                            '06' => 'June',
                            '07' => 'July',
                            '08' => 'August',
                            '09' => 'September',
                            '10' => 'October',
                            '11' => 'November',
                            '12' => 'December',
                        ])
                        ->multiple()
                        ->reactive()
                        ->hidden(fn($get) => ($get('yearlyOrders') === 'all') ||
                            ($get('type') !== 'cx' && $get('type') !== 'target')
                        )
                        ->disabled(fn($get) => ($get('yearlyOrders') === 'all') ||
                            ($get('type') !== 'cx' && $get('type') !== 'target')
                        ),
                    Select::make('category_id')
                        ->label('Category')
                        ->columns(1)
                        ->options(Cache::remember('categories', now()->addHours(1), fn() => Category::all()->pluck('name', 'id')))
                        ->nullable()
                        ->disabled(fn(Get $get): bool => $get('type') === 'ac')
                        ->hidden(fn(Get $get): bool => $get('type') === 'ac')
                        ->multiple()
                        ->reactive(),
                    Select::make('order_status')
                        ->label('Status')
                        ->columns(1)
                        ->options([
                            'processing' => 'Processing',
                            'closed' => 'Closed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->multiple()
                        ->disabled(fn(Get $get): bool => $get('type') === 'ac')
                        ->hidden(fn(Get $get): bool => $get('type') === 'ac')
                        ->nullable()
                        ->reactive(),
                    Select::make('period')
                        ->label('Period')
                        ->columns(1)
                        ->options([
                            'daily' => 'Daily',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                        ])
                        ->disabled(fn(Get $get): bool => $get('type') !== 'ac')
                        ->hidden(fn(Get $get): bool => $get('type') !== 'ac')
                        ->reactive(),
                    Select::make('department')
                        ->label('Department')
                        ->columns(1)
                        ->options(Cache::remember('departments', now()->addHours(1), fn() => Department::all()->pluck('name', 'id')))
                        ->nullable()
                        ->disabled(fn(Get $get): bool => $get('type') !== 'ac')
                        ->hidden(fn(Get $get): bool => $get('type') !== 'ac')
                        ->reactive(),
                    Select::make('currency')
                        ->label('Currency')
                        ->options(showCurrencies())
                        ->nullable()
                        ->disabled(fn(Get $get): bool => $get('type') !== 'ac')
                        ->hidden(fn(Get $get): bool => $get('type') !== 'ac')
                        ->reactive(),
                    Select::make('payment_type')
                        ->label('Payment Type')
                        ->columns(1)
                        ->options(PaymentRequest::$typesOfPayment)
                        ->nullable()
                        ->disabled(fn(Get $get): bool => $get('type') !== 'ac')
                        ->hidden(fn(Get $get): bool => $get('type') !== 'ac')
                        ->reactive(),
                    Select::make('status')
                        ->label('Status')
                        ->columns(1)
                        ->options(PaymentRequest::$status)
                        ->nullable()
                        ->disabled(fn(Get $get): bool => $get('type') !== 'ac')
                        ->hidden(fn(Get $get): bool => $get('type') !== 'ac')
                        ->reactive(),
                    Fieldset::make('')
                        ->columnSpan(1)
                        ->schema([
                            Actions::make([
                                Action::make('clearFilters')
                                    ->label('Reset')
                                    ->tooltip('Reset all filters and re-adjust data')
                                    ->icon('heroicon-o-arrow-path-rounded-square')
                                    ->button()
                                    ->size(ActionSize::Small)
                                    ->color('primary')
                                    ->action(function (Component $livewire) {
                                        $filterKeys = [
                                            'yearlyOrders',
                                            'monthlyOrders',
                                            'category_id',
                                            'order_status',
                                            'department',
                                            'currency',
                                            'payment_type',
                                            'status',
                                        ];

                                        foreach ($filterKeys as $key) {
                                            if (isset($this->filters[$key]) && !empty($this->filters[$key])) {
                                                $this->filters[$key] = null;
                                            }
                                        }
                                    }),
                            ]),
                        ]),

                ]),
        ];
    }
}
