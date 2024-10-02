<?php

namespace App\Filament\Resources\Core\NotificationResource\Pages;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Admin
{
    /**
     * @return Select
     */
    public static function getRecipient(): Select
    {
        return Select::make('notifiable_id')
            ->label('Recipient')
            ->options(User::all()->pluck('fullName', 'id'))
            ->required();
    }

    /**
     * @return Select
     */
    public static function getPriority(): Select
    {
        return Select::make('priority')
            ->label('Priority')
            ->options(['high' => '⬆ Email & In-app', 'low' => '⬇ In-app'])
            ->required();
    }

    /**
     * @return Textarea
     */
    public static function getMessage(): Textarea
    {
        return Textarea::make('data')
            ->label('Message')
            ->required()
            ->maxLength(65535)
            ->columnSpanFull();
    }

    /**
     * @return TextColumn
     */
    public static function showRecipient(): TextColumn
    {
        return TextColumn::make('user.fullName')
            ->badge()
            ->searchable(['first_name', 'middle_name', 'last_name'])
            ->color('primary');
    }

    /**
     * @return TextColumn
     */
    public static function showMessage(): TextColumn
    {
        return TextColumn::make('data')
            ->color('secondary')
            ->words(6)
            ->size(TextColumnSize::Small)
            ->tooltip(function (string $state) {
                $data = json_decode($state, true);
                return strip_tags($data['body'] ?? '');
            })
            ->formatStateUsing(function (string $state) {
                $data = json_decode($state, true);
                return strip_tags($data['body'] ?? '');
            });
    }

    /**
     * @return TextColumn
     */
    public static function showCreatedTime(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label('Sent ✔ ')
            ->icon(fn(Model $record) => $record->created_at != 'Unsent' ? 'heroicon-c-shield-check' : 'heroicon-c-shield-exclamation')
            ->iconColor(fn(Model $record) => $record->created_at != 'Unsent' ? 'success' : 'warning')
            ->sortable()
            ->formatStateUsing(fn($state) => getTableDesign() == "modern" ? "Delivered: {$state}" : $state)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    /**
     * @return TextColumn
     */
    public static function showReadTime(): TextColumn
    {
        return TextColumn::make('read_at')
            ->label('Read ✔  ✔ ')
            ->icon(fn(Model $record) => $record->read_at != 'Unread' ? 'heroicon-c-shield-check' : 'heroicon-c-shield-exclamation')
            ->iconColor(fn(Model $record) => $record->read_at != 'Unread' ? 'success' : 'danger')
            ->sortable()
            ->formatStateUsing(fn($state) => getTableDesign() == "modern" && $state != "Unread" ? "Read: {$state}" : $state)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    /**
     * @return TextColumn
     */
    public static function showClearingTime(): TextColumn
    {
        return TextColumn::make('deleted_at')
            ->label('Cleared ☑️')
            ->icon(fn(Model $record) => $record->deleted_at != 'Uncleared' ? 'heroicon-c-shield-check' : 'heroicon-c-shield-exclamation')
            ->iconColor(fn(Model $record) => $record->deleted_at != 'Uncleared' ? 'success' : 'warning')
            ->sortable()
            ->formatStateUsing(fn($state) => getTableDesign() == "modern" && $state != "Uncleared" ? "Cleared: {$state}" : $state)
            ->toggleable(isToggledHiddenByDefault: false);
    }

    /**
     * @return Group
     */
    public static function groupByName(): Group
    {
        return Group::make('user.first_name')
            ->label('Recipient')
            ->getTitleFromRecordUsing(fn(Model $record): string => optional($record->user)->fullName ?? 'N/A')
            ->getDescriptionFromRecordUsing(fn(Model $record): string => "Acting as " . (optional($record->user)->role ?? 'N/A'))
            ->collapsible();
    }

    /**
     * @return Group
     */
    public static function groupByType(): Group
    {
        return Group::make('data')
            ->label('Type')
            ->getTitleFromRecordUsing(function (Model $record) {
                $jsonData = json_decode($record->data, true);
                return strip_tags($jsonData['title']) ?? '';
            })
            ->groupQueryUsing(fn(Builder $query) => $query->groupBy(json_decode('data.title')));
    }


    /**
     * @return SelectFilter
     * @throws \Exception
     */
    public static function filterByRecipient(): SelectFilter
    {
        return SelectFilter::make('notifiable_id')
            ->label('User')
            ->getOptionLabelFromRecordUsing(fn(Model $record) => "{$record->first_name} {$record->last_name}")
            ->relationship(
                'user',
                'first_name',
                modifyQueryUsing: fn(Builder $query) => $query->orderBy('first_name')->orderBy('last_name'),
            );
    }
}
