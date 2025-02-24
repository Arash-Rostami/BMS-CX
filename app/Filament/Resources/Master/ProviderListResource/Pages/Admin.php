<?php

namespace App\Filament\Resources\Master\ProviderListResource\Pages;

use App\Models\QuoteProvider;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class Admin
{
    /**
     * @return TextInput
     */
    public static function getName(): TextInput
    {
        return TextInput::make('name')
            ->required()
            ->maxLength(255);
    }

    /**
     * @return Select
     */
    public static function getRecipients(): Select
    {
        return Select::make('recipients')
            ->label('Quote Providers')
            ->multiple()
            ->placeholder('Choose all recipients of this list.')
            ->relationship('quoteProviders', 'name')
            ->options(QuoteProvider::orderBy('name', 'asc')->pluck('name', 'id'));
    }

    /**
     * @return TextEntry
     */
    public static function viewName(): TextEntry
    {
        return TextEntry::make('name')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public static function viewRecipients(): TextEntry
    {
        return TextEntry::make('quoteProviders.name')
            ->label('Quote Providers')
            ->color('secondary')
            ->badge();
    }

    /**
     * @return TextColumn
     */
    public static function showName(): TextColumn
    {
        return TextColumn::make('name')
            ->badge()
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showRecipients(): TextColumn
    {
        return TextColumn::make('quoteProviders.name')
            ->color('secondary')
            ->listWithLineBreaks()
            ->bulleted()
            ->limitList(3)
            ->searchable();
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

    /**
     * @param string $model
     * @param array $data
     * @return mixed
     */
    public static function createRecord(string $model, array $data)
    {
        $list = $model::create([
            'name' => $data['name'],
            'exclude' => false,
        ]);

        if (isset($data['recipients']) && is_array($data['recipients'])) {
            $list->quoteProviders()->attach($data['recipients']);
        }

        return $list;
    }


    /**
     * @param Model $record
     * @param array $data
     * @return Model
     */
    public static function updateRecord(Model $record, array $data): Model
    {
        $record->update([
            'name' => $data['name'],
            'exclude' => $data['exclude'] ?? false,
        ]);

        if (isset($data['recipients']) && is_array($data['recipients'])) {
            $record->quoteProviders()->sync($data['recipients']);
        }

        return $record;
    }
}
