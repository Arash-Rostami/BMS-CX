<?php

namespace App\Filament\Resources\Financial\CashTransactionResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\CashTransaction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables;

class Admin
{
    /**
     * @return TextInput
     */
    public static function getAmount(): TextInput
    {
        return TextInput::make('amount')
            ->required()
            ->numeric()
            ->columnSpan(1)
            ->prefix('Amount to Add');
    }

    /**
     * @return Select
     */
    public static function getCurrency(): Select
    {
        return Select::make('currency')
            ->required()
            ->columnSpan(1)
            ->options(fn() => collect(showCurrencies())
                ->mapWithKeys(fn($html, $key) => [$key => strip_tags($html->toHtml())]));
    }

    /**
     * @return Textarea
     */
    public static function getDescription(): Textarea
    {
        return Textarea::make('description')
            ->required()
            ->label('Reason for Deposit')
            ->placeholder('Enter a brief note to help with future search or tracking')
            ->columnSpan(2);
    }

    /**
     * @return Tables\Columns\TextColumn
     */
    public static function showAmount(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('amount')
            ->alignEnd()
            ->money(fn(CashTransaction $record) => $record->currency)
            ->sortable();
    }

    /**
     * @return Tables\Columns\TextColumn
     */
    public static function showDescription(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('description')
            ->searchable();
    }

    /**
     * @return Tables\Columns\TextColumn
     */
    public static function showPayment(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('payment.reference_number')
            ->searchable()
            ->clickable(PaymentResource::class, 'payment_id')
            ->sortable();
    }

    /**
     * @return Tables\Columns\TextColumn
     */
    public static function showTimestamp(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('created_at')
            ->label('Created At')
            ->dateTime('Y-m-d H:i')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * @return Tables\Columns\TextColumn
     */
    public static function showType(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('type')
            ->badge()
            ->color(fn(string $state): string => match ($state) {
                'credit' => 'success',
                'debit' => 'danger',
            });
    }

    /**
     * @return Tables\Columns\TextColumn
     */
    public static function showUser(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('user.full_name')
            ->label('Created By')
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
