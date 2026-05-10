<?php

namespace App\Filament\Resources\Financial\SettlementResource\Pages\AdminComponents;

use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;

trait Table
{
    public static function showAccount(): TextColumn
    {
        return TextColumn::make('ledgerEntry.account.name')
            ->label('Account (Ledger Account)')
            ->sortable()
            ->grow(false)
            ->searchable()
            ->badge()
            ->color('primary');
    }

    public static function showCounterInterestApplied(): TextColumn
    {
        return TextColumn::make('counter_interest_applied')
            ->label('Counter Interest')
            ->numeric(decimalPlaces: 2)
            ->default(0)
            ->color('success')
            ->sortable();
    }

    public static function showCurrencyType(): TextColumn
    {
        return TextColumn::make('currency_type')
            ->label('Currency')
            ->formatStateUsing(fn($record) => sprintf('%s → %s (Base)', $record->currency_type, config('financial.base_currency', 'USD')))
            ->sortable()
            ->grow(false)
            ->badge()
            ->color('gray');
    }

    public static function showDeductedFromInterest(): TextColumn
    {
        return TextColumn::make('deducted_from_interest')
            ->label('Interest Taken (Deducted)')
            ->numeric(decimalPlaces: 3)
            ->sortable()
            ->grow(false)
            ->badge()
            ->color('warning');
    }

    public static function showDeductedFromPrincipal(): TextColumn
    {
        return TextColumn::make('deducted_from_principal')
            ->label('Principal Taken (Deducted)')
            ->numeric(decimalPlaces: 3)
            ->sortable()
            ->grow(false)
            ->badge()
            ->color('success');
    }

    public static function showSettlementAmount(): TextColumn
    {
        return TextColumn::make('settlement_amount')
            ->label('Base (Settlement Amount)')
            ->numeric(decimalPlaces: 3)
            ->sortable()
            ->grow(false)
            ->badge()
            ->color('info');
    }

    public static function showTransactionDate(): TextColumn
    {
        return TextColumn::make('transaction_date')
            ->label('Date (Transaction Date)')
            ->date()
            ->grow(false)
            ->sortable()
            ->badge()
            ->color('secondary')
            ->icon('heroicon-s-calendar-days');
    }

    public static function viewAccount(): TextEntry
    {
        return TextEntry::make('ledgerEntry.account.name')
            ->label('Account (Ledger Account)')
            ->badge()
            ->color('primary');
    }

    public static function viewCounterInterestApplied(): TextEntry
    {
        return TextEntry::make('counter_interest_applied')
            ->label('Counter Interest')
            ->numeric(decimalPlaces: 2)
            ->default(0)
            ->color('success')
            ->helperText('Interest offset applied from surplus receipt credit — reduces borrower\'s interest burden.');
    }

    public static function viewCurrencyType(): TextEntry
    {
        return TextEntry::make('currency_type')
            ->label('Currency')
            ->badge()
            ->color('gray');
    }

    public static function viewDeductedFromInterest(): TextEntry
    {
        return TextEntry::make('deducted_from_interest')
            ->label('Interest Taken (Deducted)')
            ->numeric(decimalPlaces: 3)
            ->badge()
            ->color('warning');
    }

    public static function viewDeductedFromPrincipal(): TextEntry
    {
        return TextEntry::make('deducted_from_principal')
            ->label('Principal Taken (Deducted)')
            ->numeric(decimalPlaces: 3)
            ->badge()
            ->color('success');
    }

    public static function viewExchangeRate(): TextEntry
    {
        return TextEntry::make('settlement_exchange_rate')
            ->label('Rate (Exchange Rate)')
            ->numeric(decimalPlaces: 6)
            ->badge()
            ->color('secondary');
    }

    public static function viewForeignAmount(): TextEntry
    {
        return TextEntry::make('foreign_settlement_amount')
            ->label('Money (Foreign Amount)')
            ->numeric(decimalPlaces: 3)
            ->badge()
            ->color('secondary');
    }

    public static function viewSettlementAmount(): TextEntry
    {
        return TextEntry::make('settlement_amount')
            ->label('Base (Settlement Amount)')
            ->numeric(decimalPlaces: 3)
            ->badge()
            ->color('info');
    }

    public static function viewTransactionDate(): TextEntry
    {
        return TextEntry::make('transaction_date')
            ->label('Date (Transaction Date)')
            ->date()
            ->badge()
            ->color('secondary');
    }
}
