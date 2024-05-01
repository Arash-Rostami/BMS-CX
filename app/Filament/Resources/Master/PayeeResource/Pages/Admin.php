<?php

namespace App\Filament\Resources\Master\PayeeResource\Pages;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class Admin
{
    /**
     * @return Radio
     */
    public static function getType(): Radio
    {
        return Radio::make('payee_type')
            ->label('Type')
            ->options(['individual' => 'Individual Party', 'legal' => 'Legal Party'])
            ->default('individual')
            ->live()
            ->columnSpanFull()
            ->inline();
    }

    /**
     * @return Textarea
     */
    public static function getEconomicType(): Textarea
    {
        return Textarea::make('economic_code')
            ->label('Economic Code')
            ->hidden(fn(Get $get) => $get('payee_type') !== 'legal')
            ->columnSpanFull()
            ->maxLength(255);
    }

    /**
     * @return Textarea
     */
    public static function getName(): Textarea
    {
        return Textarea::make('name')
            ->label('Full Name')
            ->required()
            ->maxLength(255)
            ->dehydrateStateUsing(fn(?string $state) => strtoupper($state));
    }

    /**
     * @return Textarea
     */
    public static function getNationalId(): Textarea
    {
        return Textarea::make('national_id')
            ->label('National ID')
            ->maxLength(255);
    }

    /**
     * @return Textarea
     */
    public static function getPhoneNumber(): Textarea
    {
        return Textarea::make('phone_number')
            ->label('Phone #')
            ->maxLength(255);
    }

    /**
     * @return Textarea
     */
    public static function getPostalCode(): Textarea
    {
        return Textarea::make('postal_code')
            ->label('Zip Code')
            ->maxLength(255);
    }

    /**
     * @return Textarea
     */
    public static function getAddress(): Textarea
    {
        return Textarea::make('address')
            ->columnSpanFull()
            ->maxLength(255);
    }

    /**
     * @return Toggle
     */
    public static function getVat(): Toggle
    {
        return Toggle::make('vat')
            ->label('Value-added Tax')
            ->inline()
            ->onIcon('heroicon-m-plus')
            ->offIcon('heroicon-o-no-symbol')
            ->onColor('success')
            ->offColor('secondary');
    }


    /**
     * @return TextColumn
     */
    public static function showTimeStamp(): TextColumn
    {
        return TextColumn::make('created_at')
            ->dateTime()
            ->icon('heroicon-s-calendar-days')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * @return TextColumn
     */
    public static function showAddressZipCode(): TextColumn
    {
        return TextColumn::make('address')
            ->icon('heroicon-o-map-pin')
            ->iconPosition(IconPosition::Before)
            ->badge()
            ->formatStateUsing(fn(Model $record) => $record->address . " - " . $record->postal_code)
            ->color('info')
            ->sortable()
            ->toggleable()
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showPhoneNumber(): TextColumn
    {
        return TextColumn::make('phone_number')
            ->icon('heroicon-s-phone')
            ->iconPosition(IconPosition::Before)
            ->badge()
            ->color('secondary')
            ->sortable()
            ->toggleable()
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showEconomicCode(): TextColumn
    {
        return TextColumn::make('economic_code')
            ->tooltip('Economic Code')
            ->formatStateUsing(fn($state) => "EC: {$state}")
            ->badge()
            ->color('secondary')
            ->sortable()
            ->toggleable()
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showNationalId(): TextColumn
    {
        return TextColumn::make('national_id')
            ->tooltip('National ID')
            ->badge()
            ->grow(false)
            ->color('secondary')
            ->formatStateUsing(fn($state) => "ID: {$state}")
            ->sortable()
            ->toggleable()
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showFullName(): TextColumn
    {
        return TextColumn::make('name')
            ->icon('heroicon-o-identification')
            ->iconPosition(IconPosition::Before)
            ->badge()
            ->grow(false)
            ->color('primary')
            ->sortable()
            ->toggleable()
            ->searchable();
    }

}
