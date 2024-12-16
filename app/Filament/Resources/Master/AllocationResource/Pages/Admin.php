<?php

namespace App\Filament\Resources\Master\AllocationResource\Pages;

use App\Models\Department;
use App\Services\DepartmentDetails;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;

class Admin
{
    /**
     * @return Select
     */
    public static function getDepartment(): Select
    {
        return Select::make('department')
            ->options(Department::getAllDepartmentCodes())
            ->required();
    }

    /**
     * @return Textarea
     */
    public static function getReason(): Textarea
    {
        return Textarea::make('reason')
            ->required()
            ->columnSpanFull()
            ->maxLength(255)
            ->dehydrateStateUsing(fn(?string $state) => capitalizeFirstLetters($state ?? ''));
    }

    /**
     * @return TextColumn
     */
    public static function showDepartment(): TextColumn
    {
        return TextColumn::make('department')
            ->searchable()
            ->color('gray')
            ->wrap()
            ->badge()
            ->tooltip(fn($state) => ($state && $state != 'all') ? DepartmentDetails::getName($state) : null)
            ->formatStateUsing(fn($state) => ucwords($state))
            ->sortable()
            ->toggleable();
    }

    /**
     * @return TextColumn
     */
    public static function showReason(): TextColumn
    {
        return TextColumn::make('reason')
            ->icon('heroicon-c-cube-transparent')
            ->wrap()
            ->color('primary')
            ->iconPosition(IconPosition::Before)
            ->sortable()
            ->toggleable()
            ->searchable();
    }

    /**
     * @return TextColumn
     */
    public static function showTimeStamp(): TextColumn
    {
        return TextColumn::make('created_at')
            ->dateTime()
            ->icon('heroicon-s-calendar-days')
            ->alignEnd()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
