<?php

namespace App\Filament\Resources\Master\QuoteProviderResource\Pages;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;


class Admin
{
    /**
     * @return Radio
     */
    public static function getTitle(): Radio
    {
        return Radio::make('title')
            ->options(['Mr' => 'Mr', 'Mrs' => 'Mrs', 'Ms' => 'Ms'])
            ->inline()
            ->required();
    }


    public static function getName()
    {
        return TextInput::make('name')
            ->required()
            ->columnSpanFull()
            ->maxLength(255);
    }


    public static function getEmail()
    {
        return TextInput::make('email')
            ->email()
            ->required()
            ->columnSpanFull()
            ->unique()
            ->maxLength(255);
    }


    public static function getPhoneNumber()
    {
        return TextInput::make('phone_number')
            ->tel()
            ->maxLength(255);
    }


    public static function getCompany()
    {
        return TextInput::make('company')
            ->maxLength(255);
    }


    public static function getExtraInfo()
    {
        return Textarea::make('extra')
            ->maxLength(65535)
            ->columnSpanFull();
    }

    /**
     * @return TextColumn
     */
    public static function showName(): TextColumn
    {
        return TextColumn::make('name')
            ->icon('heroicon-c-megaphone')
            ->iconPosition(IconPosition::Before)
            ->badge()
            ->color('primary')
            ->sortable()
            ->toggleable()
            ->state(fn(Model $record) => $record->title . " " . $record->name)
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showEmail(): TextColumn
    {
        return TextColumn::make('email')
            ->badge()
            ->color('secondary')
            ->sortable()
            ->toggleable()
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showCompany(): TextColumn
    {
        return TextColumn::make('company')
            ->searchable()
            ->color('gray')
            ->weight(FontWeight::Light)
            ->state(fn(Model $record) => "@" . $record->company . " - 📞" . $record->phone_number)
            ->wrap()
            ->sortable()
            ->toggleable();
    }

    /**
     * @return TextColumn
     */
    public static function showTimeStamp(): TextColumn
    {
        return TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
