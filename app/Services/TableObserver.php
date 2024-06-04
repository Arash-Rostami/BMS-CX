<?php

namespace App\Services;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use function Filament\Support\is_slot_empty;

class TableObserver
{
    public static function showMissingData($number): TextColumn
    {
        return TextColumn::make('data')
            ->label('Missing Info')
            ->grow(false)
            ->state(function (Model $record) use ($number) {
                $nullCount = $number;
                foreach ($record->getAttributes() as $key => $value) {
                    if (is_null($value)) {
                        $nullCount++;
                    }
                }

                return $nullCount === 0 ? 'None' : "$nullCount Missing";
            })
            ->icon(fn($state): string => 'heroicon-s-puzzle-piece')
            ->color(fn($state) => $state == 'None' ? 'success' : 'danger')
            ->toggleable()
            ->badge();
    }


    public static function showMissingDataWithRel($number): TextColumn
    {
        return TextColumn::make('data')
            ->label('Missing Info')
            ->grow(false)
            ->state(function (Model $record) use ($number) {
                $nullCount = $number;

                // Check relations and their attributes, including JSON
                foreach ($record->getRelations() as $relation => $value) {
                    if (is_null($value)) {
                        $nullCount++;
                    } elseif ($value instanceof Model) {
                        foreach ($value->getAttributes() as $relKey => $relValue) {
                            if (is_null($relValue)) {
                                $nullCount++;
                            } elseif ($value->hasCast($relKey, ['array', 'json'])) {
                                $jsonData = json_decode($relValue, true);
                                $nullCount += self::countNullsInArray($jsonData);
                            }
                        }
                    }
                }
                return $nullCount === 0 ? 'None' : "$nullCount Missing";
            })
            ->icon(fn($state): string => 'heroicon-s-puzzle-piece')
            ->color(fn($state) => $state == 'None' ? 'success' : 'danger')
            ->toggleable()
            ->badge();
    }

    protected static function countNullsInArray($array)
    {
        $nullCount = 0;
        array_walk_recursive($array, function ($item) use (&$nullCount) {
            if (is_null($item)) {
                $nullCount++;
            }
        });
        return $nullCount;
    }
}
