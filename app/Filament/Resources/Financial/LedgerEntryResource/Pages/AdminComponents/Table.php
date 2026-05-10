<?php

namespace App\Filament\Resources\Financial\LedgerEntryResource\Pages\AdminComponents;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

trait Table
{
    public static function showAccount(): TextColumn
    {
        return TextColumn::make('account.name')
            ->label('Account (Ledger Account)')
            ->sortable()
            ->grow(false)
            ->searchable()
            ->badge()
            ->color('primary');
    }

    public static function showAppliedCreditAmount(): TextColumn
    {
        return TextColumn::make('applied_credit_amount')
            ->label('Credit (Applied Credit)')
            ->grow(false)
            ->numeric(decimalPlaces: 3)
            ->sortable()
            ->tooltip('Credit applied to this entry')
            ->badge()
            ->color('gray');
    }

    public static function showCreator(): TextColumn
    {
        return TextColumn::make('user.fullName')
            ->label('Loan Officer')
            ->sortable()
            ->searchable(['first_name', 'middle_name', 'last_name'])
            ->badge()
            ->color('secondary')
            ->toggleable(isToggledHiddenByDefault: true)
            ->visible(fn() => auth()->user()?->hasRole('admin') ?? false);
    }

    public static function showDescription(): TextColumn
    {
        return TextColumn::make('description')
            ->label('Description')
            ->sortable()
            ->grow(false)
            ->alignLeft()
            ->badge()
            ->color('secondary');
    }

    public static function showIsSettled(): IconColumn
    {
        return IconColumn::make('is_settled')
            ->label('Settled')
            ->boolean()
            ->sortable()
            ->trueColor('success')
            ->falseColor('warning')
            ->tooltip(fn($state) => $state ? '✅ Settled' : '⌛ Pending Settlement');
    }

    public static function showPrincipalAmountBase(): TextColumn
    {
        return TextColumn::make('principal_amount_base')
            ->label('Base (Principal Amount)')
            ->grow(false)
            ->numeric(decimalPlaces: 3)
            ->sortable()
            ->tooltip('Total amount transferred')
            ->badge()
            ->color('info');
    }

    public static function showRemainingPrincipal(): TextColumn
    {
        return TextColumn::make('remaining_principal')
            ->label('Balance (Remaining Principal)')
            ->grow(false)
            ->numeric(decimalPlaces: 3)
            ->tooltip('Total amount remaining')
            ->sortable()
            ->badge()
            ->color(fn($record) => ($record?->remaining_principal > 0) ? 'warning' : 'success');
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

    public static function showType(): TextColumn
    {
        return TextColumn::make('type')
            ->label('Type')
            ->searchable()
            ->grow(false)
            ->badge()
            ->color(fn(string $state): string => match ($state) {
                'disbursement' => 'warning',
                'receipt' => 'success',
                default => 'secondary',
            });
    }

    public static function showUnpaidInterest(): TextColumn
    {
        return TextColumn::make('unpaid_interest')
            ->label('Interest (Interest Due)')
            ->grow(false)
            ->numeric(decimalPlaces: 3)
            ->sortable()
            ->tooltip('Total unpaid interest')
            ->badge()
            ->color(fn($record) => ($record?->unpaid_interest > 0) ? 'danger' : 'success');
    }

    public static function viewAccount(): TextEntry
    {
        return TextEntry::make('account.name')
            ->label('Account (Ledger Account)')
            ->badge()
            ->color('primary');
    }

    public static function viewAmountForeignCurrency(): TextEntry
    {
        return TextEntry::make('amount_foreign_currency')
            ->label('Money (Foreign Amount)')
            ->numeric(decimalPlaces: 3)
            ->badge()
            ->color('info');
    }

    public static function viewAppliedCreditAmount(): TextEntry
    {
        return TextEntry::make('applied_credit_amount')
            ->label('Credit (Applied Credit)')
            ->numeric(decimalPlaces: 3)
            ->badge()
            ->color('gray');
    }

    public static function viewCommissionAmount(): TextEntry
    {
        return TextEntry::make('commission_amount')
            ->label('Fee (Bank Commission)')
            ->numeric(decimalPlaces: 3)
            ->badge()
            ->color('secondary');
    }

    public static function viewCreator(): TextEntry
    {
        return TextEntry::make('user.fullName')
            ->label('Loan Officer')
            ->badge()
            ->color('secondary');
    }

    public static function viewCurrencyType(): TextEntry
    {
        return TextEntry::make('currency_type')
            ->label('Currency')
            ->badge()
            ->color('secondary');
    }

    public static function viewDescription(): TextEntry
    {
        return TextEntry::make('description')
            ->label('Description');
    }

    public static function viewExchangeRate(): TextEntry
    {
        return TextEntry::make('exchange_rate')
            ->label('Rate (Exchange Rate)')
            ->numeric(decimalPlaces: 3)
            ->badge()
            ->color('secondary');
    }

    public static function viewIsSettled(): IconEntry
    {
        return IconEntry::make('is_settled')
            ->label('Settled')
            ->boolean()
            ->trueColor('success')
            ->falseColor('warning');
    }

    public static function viewPrincipalAmountBase(): TextEntry
    {
        return TextEntry::make('principal_amount_base')
            ->label('Base (Principal Amount)')
            ->numeric(decimalPlaces: 3)
            ->badge()
            ->color('info');
    }

    public static function viewRemainingPrincipal(): TextEntry
    {
        return TextEntry::make('remaining_principal')
            ->label('Balance (Remaining Principal)')
            ->numeric(decimalPlaces: 3)
            ->badge()
            ->color(fn($record) => ($record?->remaining_principal > 0) ? 'warning' : 'success');
    }

    public static function viewTotalDisbursedBase(): TextEntry
    {
        return TextEntry::make('total_disbursed_base')
            ->label('Base + Fee (Total Disbursed)')
            ->numeric(decimalPlaces: 3)
            ->badge()
            ->color('warning');
    }

    public static function viewTransactionDate(): TextEntry
    {
        return TextEntry::make('transaction_date')
            ->label('Date (Transaction Date)')
            ->date()
            ->badge()
            ->color('secondary');
    }

    public static function viewType(): TextEntry
    {
        return TextEntry::make('type')
            ->label('Type')
            ->badge()
            ->color(fn(string $state): string => match ($state) {
                'disbursement' => 'warning',
                'receipt' => 'success',
                default => 'secondary',
            });
    }

    public static function viewUnpaidInterest(): TextEntry
    {
        return TextEntry::make('unpaid_interest')
            ->label('Interest (Interest Due)')
            ->numeric(decimalPlaces: 3)
            ->badge()
            ->color(fn($record) => ($record?->unpaid_interest > 0) ? 'danger' : 'success');
    }
}
