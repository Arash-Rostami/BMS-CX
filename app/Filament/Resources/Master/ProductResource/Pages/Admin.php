<?php

namespace App\Filament\Resources\Master\ProductResource\Pages;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;

class Admin
{
    /**
     * @return Select
     */
    public static function getCategory(): Select
    {
        return Select::make('category_id')
            ->relationship('category', 'name')
            ->required()
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn(?string $state) => ucwords($state)),

                MarkdownEditor::make('description')
                    ->maxLength(65535)
                    ->disableAllToolbarButtons()
                    ->unique()
            ])
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('Create new category')
                    ->modalButton('Create')
                    ->modalWidth('lg');
            });

    }

    /**
     * @return TextInput
     */
    public static function getName(): TextInput
    {
        return TextInput::make('name')
            ->required()
            ->maxLength(255)
            ->dehydrateStateUsing(fn(?string $state) => strtoupper($state));
    }

    /**
     * @return Textarea
     */
    public static function getDesription(): Textarea
    {
        return Textarea::make('description')
            ->maxLength(65535)
            ->placeholder('optional for extra details')
            ->columnSpanFull();
    }

    /**
     * @return TextColumn
     */
    public static function showName(): TextColumn
    {
        return TextColumn::make('name')
            ->icon('heroicon-o-squares-2x2')
            ->iconPosition(IconPosition::Before)
            ->sortable()
            ->toggleable()
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showCategory(): TextColumn
    {
        return TextColumn::make('category.name')
            ->icon('heroicon-o-rectangle-stack')
            ->iconPosition(IconPosition::Before)
            ->badge()
            ->searchable()
            ->toggleable()
            ->sortable();
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
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
