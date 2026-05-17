<?php

namespace App\Filament\Resources\Financial\LedgerEntryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class SettlementsRelationManager extends RelationManager
{
    protected static string $relationship = 'settlements';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';
    protected static ?string $navigationLabel = 'Loan Settlement';
    protected static ?string $modelLabel = 'Loan Settlement';
    protected static ?string $pluralModelLabel = 'Loan Settlements';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('transaction_date')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transaction_date')
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_days')
                    ->numeric()
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total Days')),

                Tables\Columns\TextColumn::make('foreign_settlement_amount')
                    ->numeric(decimalPlaces: 3)
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->numeric(decimalPlaces: 3)),

                Tables\Columns\TextColumn::make('settlement_exchange_rate')
                    ->numeric(decimalPlaces: 3)
                    ->summarize(Tables\Columns\Summarizers\Average::make()->numeric(decimalPlaces: 3)->label('Avg Rate')),

                Tables\Columns\TextColumn::make('settlement_amount')
                    ->numeric(decimalPlaces: 3)
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()->numeric(decimalPlaces: 3)->label('Total'),
                        Tables\Columns\Summarizers\Count::make()->label('# Settlements'),
                    ]),

                Tables\Columns\TextColumn::make('accrued_interest_in_period')
                    ->numeric(decimalPlaces: 3)
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->numeric(decimalPlaces: 3)->label('Total Interest')),

//                Tables\Columns\TextColumn::make('deducted_from_interest')
//                    ->numeric(decimalPlaces: 3)
//                    ->summarize(Tables\Columns\Summarizers\Sum::make()->numeric(decimalPlaces: 3)),

                Tables\Columns\TextColumn::make('deducted_from_principal')
                    ->numeric(decimalPlaces: 3)
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->numeric(decimalPlaces: 3)),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->filters([
                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Grid::make(2)->schema([
                            DatePicker::make('from')->native(false)->label('From'),
                            DatePicker::make('until')->native(false)->label('Until'),
                        ])
                    ])
                    ->query(fn(Builder $query, array $data) => $query
                        ->when($data['from'], fn($q) => $q->whereDate('transaction_date', '>=', $data['from']))
                        ->when($data['until'], fn($q) => $q->whereDate('transaction_date', '<=', $data['until']))
                    ),

                Tables\Filters\Filter::make('has_principal_deduction')
                    ->label('Has Principal Deduction')
                    ->query(fn(Builder $query) => $query->where('deducted_from_principal', '>', 0)),

                Tables\Filters\Filter::make('has_interest_deduction')
                    ->label('Has Interest Deduction')
                    ->query(fn(Builder $query) => $query->where('deducted_from_interest', '>', 0)),
            ], FiltersLayout::AboveContentCollapsible)
            ->filtersFormWidth(MaxWidth::Full)
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()->exports([ExcelExport::make()->fromTable()])
                ])
            ]);
    }
}
