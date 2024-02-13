<?php

namespace App\Filament\Resources\Master\BuyerResource\Pages;

use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;

class Admin
{
    /**
     * @return Textarea
     */
    public static function getName(): Textarea
    {
        return Textarea::make('name')
            ->required()
            ->columnSpanFull()
            ->maxLength(255)
            ->dehydrateStateUsing(fn(?string $state) => strtoupper($state));
    }

    /**
     * @return Textarea
     */
    public static function getDescription(): Textarea
    {
        return Textarea::make('description')
            ->placeholder('optional for extra details')
            ->maxLength(65535)
            ->columnSpanFull();
    }

    /**
     * @return TextColumn
     */
    public static function showName(): TextColumn
    {
        return TextColumn::make('name')
            ->icon('heroicon-o-arrow-down-on-square-stack')
            ->color('secondary')
            ->badge()
            ->color('primary')
            ->iconPosition(IconPosition::Before)
            ->sortable()
            ->toggleable()
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showDescription(): TextColumn
    {
        return TextColumn::make('description')
            ->searchable()
            ->color('gray')
            ->weight(FontWeight::Light)
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
