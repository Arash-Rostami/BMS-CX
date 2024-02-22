<?php

namespace App\Filament\Resources\Master\PurchaseStatusResource\Pages;

use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;

class Admin
{
    /**
     * @return Textarea
     */
    public static function getTitle(): Textarea
    {
        return Textarea::make('name')
            ->label('Title')
            ->required()
            ->columnSpanFull()
            ->maxLength(255);
    }

    /**
     * @return Textarea
     */
    public static function getDescription(): Textarea
    {
        return Textarea::make('description')
            ->maxLength(65535)
            ->placeholder('optional for extra details')
            ->columnSpanFull();
    }

    /**
     * @return TextColumn
     */
    public static function showTitle(): TextColumn
    {
        return TextColumn::make('name')
            ->badge()
            ->color('primary')
            ->searchable()
            ->sortable()
            ->toggleable()
            ->color('primary');
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
            ->icon('heroicon-s-calendar-days')
            ->sortable()
            ->toggleable()
            ->alignRight();
    }
}
