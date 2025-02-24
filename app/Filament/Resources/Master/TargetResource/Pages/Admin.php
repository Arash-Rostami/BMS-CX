<?php

namespace App\Filament\Resources\Master\TargetResource\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Wallo\FilamentSelectify\Components\ToggleButton;

class Admin
{
    /**
     * @return Select|string|null
     */
    public static function getCategory(): Select
    {
        return Select::make('category_id')
            ->relationship('category', 'name')
            ->required()
            ->label('Category');
    }

    /**
     * @return Select|string|null
     */
    public static function getYear(): Select
    {
        return Select::make('year')
            ->options([
                (string)(date('Y') - 1) => date('Y') - 1,
                (string)(date('Y')) => date('Y'),
                (string)(date('Y') + 1) => date('Y') + 1,
            ])
            ->default((string)(date('Y')))
            ->required()
            ->label('Year');
    }

    /**
     * @return TextInput
     */
    public static function getJanuary(): TextInput
    {
        return TextInput::make('month.january')->numeric()->label('❄️ January')->required()->live()
            ->debounce('1000ms')
            ->afterStateUpdated(function (Get $get, Set $set) {
                $set('target_quantity', self::calculateTotal($get));
            });
    }

    /**
     * @return TextInput
     */
    public static function getFebruary(): TextInput
    {
        return TextInput::make('month.february')->numeric()->label('🌸 February')->required()->live()
            ->debounce('1000ms')
            ->afterStateUpdated(function (Get $get, Set $set) {
                $set('target_quantity', self::calculateTotal($get));
            });
    }

    /**
     * @return TextInput
     */
    public static function getMarch(): TextInput
    {
        return TextInput::make('month.march')->numeric()->label('🌱 March')->required()->live()
            ->debounce('1000ms')
            ->afterStateUpdated(function (Get $get, Set $set) {
                $set('target_quantity', self::calculateTotal($get));
            });
    }

    /**
     * @return TextInput
     */
    public static function getApril(): TextInput
    {
        return TextInput::make('month.april')->numeric()->label('🌷 April')->required()->live()
            ->debounce('1000ms')
            ->afterStateUpdated(function (Get $get, Set $set) {
                $set('target_quantity', self::calculateTotal($get));
            });
    }

    /**
     * @return TextInput
     */
    public static function getMay(): TextInput
    {
        return TextInput::make('month.may')->numeric()->label('🌞 May')->required()->live()
            ->debounce('1000ms')
            ->afterStateUpdated(function (Get $get, Set $set) {
                $set('target_quantity', self::calculateTotal($get));
            });
    }

    /**
     * @return TextInput
     */
    public static function getJune(): TextInput
    {
        return TextInput::make('month.june')->numeric()->label('☀️ June')->required()->live()
            ->debounce('1000ms')
            ->afterStateUpdated(function (Get $get, Set $set) {
                $set('target_quantity', self::calculateTotal($get));
            });
    }

    /**
     * @return TextInput
     */
    public static function getJuly(): TextInput
    {
        return TextInput::make('month.july')->numeric()->label('🏖️ July')->required()->live()
            ->debounce('1000ms')
            ->afterStateUpdated(function (Get $get, Set $set) {
                $set('target_quantity', self::calculateTotal($get));
            });
    }

    /**
     * @return TextInput
     */
    public static function getAugust(): TextInput
    {
        return TextInput::make('month.august')->numeric()->label('🌻 August')->required()->live()
            ->debounce('1000ms')
            ->afterStateUpdated(function (Get $get, Set $set) {
                $set('target_quantity', self::calculateTotal($get));
            });
    }

    /**
     * @return TextInput
     */
    public static function getSeptember(): TextInput
    {
        return TextInput::make('month.september')->numeric()->label('🍂 September')->required()->live()
            ->debounce('1000ms')
            ->afterStateUpdated(function (Get $get, Set $set) {
                $set('target_quantity', self::calculateTotal($get));
            });
    }

    /**
     * @return TextInput
     */
    public static function getOctober(): TextInput
    {
        return TextInput::make('month.october')->numeric()->label('🎃 October')->required()->live()
            ->debounce('1000ms')
            ->afterStateUpdated(function (Get $get, Set $set) {
                $set('target_quantity', self::calculateTotal($get));
            });
    }

    /**
     * @return TextInput
     */
    public static function getNovember(): TextInput
    {
        return TextInput::make('month.november')->numeric()->label('🍁 November')->required()->live()
            ->debounce('1000ms')
            ->afterStateUpdated(function (Get $get, Set $set) {
                $set('target_quantity', self::calculateTotal($get));
            });
    }

    /**
     * @return TextInput
     */
    public static function getDecember(): TextInput
    {
        return TextInput::make('month.december')->numeric()->label('🎄 December')->required()->live()
            ->debounce('1000ms')
            ->afterStateUpdated(function (Get $get, Set $set) {
                $set('target_quantity', self::calculateTotal($get));
            });
    }

    /**
     * @return mixed
     */
    public static function getTotalTargetQuantity()
    {
        return TextInput::make('target_quantity')
            ->label('Total yearly quantity')
            ->numeric()
            ->live()
            ->readOnly()
            ->tooltip('It is measured based on MT(metric tonnes).')
            ->required();
    }

    /**
     * @return TextInput|string|null
     */
    public static function getModifiedTargetQuantity(): TextInput
    {
        return TextInput::make('modified_target_quantity')
            ->numeric()
            ->nullable()
            ->placeholder('Use this field to override the total yearly quantity. Leave blank to use that primary value.')
            ->nullable()
            ->label('Modified Target Quantity (optional)');
    }

    /**
     * @return ToggleButton
     */
    public static function getActive(): ToggleButton
    {
        return ToggleButton::make('extra.active')
            ->label('Active')
            ->default('true');
    }

    /**
     * @return TextColumn
     */
    public static function showCategory(): TextColumn
    {
        return TextColumn::make('category.name')
            ->label('Category')
            ->searchable(['name'])
            ->grow(false)
            ->badge()
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showYear(): TextColumn
    {
        return TextColumn::make('year')
            ->grow(false)
            ->searchable()
            ->badge()
            ->color('secondary')
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showTargetQuantity(): TextColumn
    {
        return TextColumn::make('target_quantity')
            ->badge()
            ->grow(false)
            ->formatStateUsing(function ($state): string {
                if (is_numeric($state)) {
                    return number_format($state, 2) . ' mt';
                }
                if (is_string($state) && trim($state) !== '') {
                    return trim($state) . ' mt';
                }
                return 'N/A';
            })
            ->sortable();
    }

    /**
     * @return TextColumn
     */
    public static function showModifiedQuantity(): TextColumn
    {
        return TextColumn::make('modified_target_quantity')
            ->badge()
            ->grow(true)
            ->label('Modified Quantity')
            ->sortable();
    }

    /**
     * @return ToggleColumn|string|null
     */
    public static function showActive(): ToggleColumn
    {
        return ToggleColumn::make('extra.active')
            ->disabled()
            ->label('Active');
    }

    /**
     * @return TextColumn
     */
    public static function showMonth(): TextColumn
    {
        return TextColumn::make('month')
            ->formatStateUsing(function ($record): string {
                $formattedMonths = [];
                foreach ($record->month as $monthName => $value) {
                    $formattedMonths[] = ucfirst($monthName) . ": " . number_format($value, 1);
                }
                return implode(" ┆ ", $formattedMonths);
            })
            ->html();
    }

    /**
     * @return TextColumn
     */
    public static function showCreator(): TextColumn
    {
        return TextColumn::make('user.full_name')
            ->badge()
            ->searchable(['first_name', 'last_name'])
            ->label('Made by');
    }

    private static function calculateTotal(Get $get): float
    {
        $total = 0;
        $months = [
            'january', 'february', 'march', 'april', 'may', 'june',
            'july', 'august', 'september', 'october', 'november', 'december'
        ];
        foreach ($months as $monthName) {
            $value = $get("month.{$monthName}");
            if (is_numeric($value)) {
                $total += (float)$value;
            }
        }
        return $total;
    }
}
