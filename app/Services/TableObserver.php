<?php

namespace App\Services;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

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
            ->color('danger')
            ->toggleable()
            ->badge();
    }
}
