<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Dashboard\AccountStatementResource\Pages\Admin;
use App\Filament\Resources\Dashboard\AccountStatementResource\Pages\ListAccountStatements;
use App\Models\AccountStatement;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class AccountStatementResource extends Resource
{
    protected static ?string $model = AccountStatement::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static bool $isGloballySearchable = false;
    protected static bool $canCreate = false;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccountStatements::route('/'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Admin::showTransactionDate(),
                Admin::showDescription(),
                Admin::showDebit(),
                Admin::showCredit(),
                Admin::showNetMovement(),
                Admin::showAppliedCredit(),
                Admin::showAccruedInterest(),
                Admin::showEventType(),
                Admin::showRunningBalance(),
            ])
            ->defaultSort('transaction_date', 'asc')
            ->filtersFormWidth(MaxWidth::TwoExtraLarge)
            ->filtersFormColumns(2)
            ->filters([
                Admin::filterAccount(),
                Admin::filterLedger(),
                Admin::filterTransactionDate()
            ], layout: FiltersLayout::Modal)
            ->defaultGroup('account.name')
            ->actions([])
            ->bulkActions([]);
    }
}
