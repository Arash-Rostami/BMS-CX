<?php

namespace App\Filament\Resources\LedgerEntryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SettlementsRelationManager extends RelationManager
{
    protected static string $relationship = 'settlements';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('transaction_date')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transaction_date')
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')->date(),
                Tables\Columns\TextColumn::make('duration_days'),
                Tables\Columns\TextColumn::make('foreign_settlement_amount')->numeric(decimalPlaces: 3),
                Tables\Columns\TextColumn::make('settlement_exchange_rate')->numeric(decimalPlaces: 3),
                Tables\Columns\TextColumn::make('settlement_amount')->numeric(decimalPlaces: 3),
                Tables\Columns\TextColumn::make('accrued_interest_in_period')->numeric(decimalPlaces: 3),
                Tables\Columns\TextColumn::make('deducted_from_interest')->numeric(decimalPlaces: 3),
                Tables\Columns\TextColumn::make('deducted_from_principal')->numeric(decimalPlaces: 3),
            ])
            ->filters([

            ])
            ->headerActions([
            ])
            ->actions([
            ])
            ->bulkActions([
            ]);
    }
}
