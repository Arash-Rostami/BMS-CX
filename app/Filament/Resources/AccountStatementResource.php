<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountStatementResource\Pages;
use App\Models\AccountStatement;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountStatementResource extends Resource
{
    protected static ?string $model = AccountStatement::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_type')
                    ->badge()
                    ->colors([
                        'primary' => 'disbursement',
                        'success' => 'receipt',
                        'warning' => 'settlement',
                    ]),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('debit')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                Tables\Columns\TextColumn::make('accrued_interest')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
            ])
            ->defaultSort('transaction_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('account_id')
                    ->relationship('account', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountStatements::route('/'),
        ];
    }
}
